<?php
/** Prospective immutable consent events. No legacy migration or bootstrap writes. */
defined('ABSPATH') || exit;

final class SiteEngine_Consent_Authority {
    private $config;
    public function __construct(array $config) { $this->config = $config; }

    public static function google(array $c): array {
        return [
            'ad_storage' => $c['advertising'] ? 'granted' : 'denied',
            'analytics_storage' => $c['analytics'] ? 'granted' : 'denied',
            'ad_user_data' => $c['advertising'] ? 'granted' : 'denied',
            'ad_personalization' => $c['personalization'] ? 'granted' : 'denied',
            'personalization_storage' => $c['personalization'] ? 'granted' : 'denied',
            'functionality_storage' => 'granted', 'security_storage' => 'granted',
        ];
    }

    private function failure(string $state): array {
        $c = ['necessary' => true, 'analytics' => false, 'advertising' => false, 'personalization' => false];
        return ['contract_version' => 1, 'state' => $state, 'receipt_id' => '', 'receipt_row_id' => 0,
            'consent' => $c, 'google_consent' => self::google($c), 'captured_at' => gmdate('Y-m-d H:i:s')];
    }

    public static function receipt_digest(array $row): string {
        $bound = [];
        foreach (['id','receipt_id','method','policy_version','banner_version','consent_json','google_consent_json','page_url','referrer_url','ip_hash','user_agent_hash','created_at','server_created_at'] as $key) {
            $bound[$key] = (string) ($row[$key] ?? '');
        }
        return hash('sha256', wp_json_encode($bound));
    }

    private function acknowledgement(array $row, string $event): array {
        $expires = strtotime($row['server_created_at'] . ' UTC') + (int) $this->config['cookie_days'] * DAY_IN_SECONDS;
        $body = base64_encode(wp_json_encode(['v' => 1, 'event_id' => $event, 'row_id' => (int) $row['id'], 'receipt_id' => $row['receipt_id'], 'expires' => $expires, 'digest' => self::receipt_digest($row)]));
        return ['contract_version' => 1, 'event_id' => $event, 'event_acknowledged' => true, 'receipt_id' => $row['receipt_id'],
            'row_id' => (int) $row['id'], 'expires_at' => $expires,
            'audit_token' => $body . '.' . hash_hmac('sha256', $body, wp_salt('auth')),
            'method' => $row['method'], 'policy_version' => $row['policy_version'], 'banner_version' => $row['banner_version'],
            'created_at' => gmdate('c', strtotime($row['server_created_at'] . ' UTC')),
            'consent' => json_decode($row['consent_json'], true), 'google_consent' => json_decode($row['google_consent_json'], true)];
    }

    /** Returns WP_Error or an acknowledged, read-back receipt. Called only by explicit capture. */
    public function save(array $payload, string $ip, string $user_agent) {
        global $wpdb;
        $event = $payload['event_id'] ?? null;
        if (!is_string($event) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $event)) {
            return new WP_Error('invalid_event', 'Invalid consent interaction.', ['status' => 400]);
        }
        if (($payload['policy_version'] ?? null) !== $this->config['policy_version'] || ($payload['banner_version'] ?? null) !== $this->config['banner_version']) {
            return new WP_Error('stale', 'The consent notice changed. Reload and choose again.', ['status' => 409]);
        }
        $input = $payload['consent'] ?? null;
        if (!is_array($input)) return new WP_Error('invalid_consent', 'Invalid consent choice.', ['status' => 400]);
        $consent = ['necessary' => true];
        foreach (['analytics','advertising','personalization'] as $key) {
            if (!isset($input[$key]) || !is_bool($input[$key])) return new WP_Error('invalid_consent', 'Consent categories must be booleans.', ['status' => 400]);
            $consent[$key] = $input[$key];
        }
        foreach (['method','page_url','referrer_url'] as $key) {
            if (isset($payload[$key]) && !is_string($payload[$key])) return new WP_Error('invalid_metadata', 'Invalid consent metadata.', ['status' => 400]);
        }
        $table = $wpdb->prefix . 'se_consent_receipts';
        // Namespace new events so arbitrary legacy receipt IDs can never be appended.
        $receipt_id = 'sc1_' . substr(hash_hmac('sha256', $event, wp_salt('auth')), 0, 60);
        $data = ['receipt_id' => $receipt_id, 'method' => substr(sanitize_text_field($payload['method'] ?? 'unknown'), 0, 64),
            'policy_version' => $this->config['policy_version'], 'banner_version' => $this->config['banner_version'],
            'consent_json' => wp_json_encode($consent), 'google_consent_json' => wp_json_encode(self::google($consent)),
            'page_url' => esc_url_raw($payload['page_url'] ?? ''), 'referrer_url' => esc_url_raw($payload['referrer_url'] ?? '')];
        // No schema change: serialize per-event retries across workers on the same database.
        $lock = 'se_consent_' . substr(hash('sha256', $table . ':' . $receipt_id), 0, 50);
        if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 3)', $lock)) !== '1') {
            return new WP_Error('audit_busy', 'Consent confirmation is busy. Retry the same interaction.', ['status' => 503]);
        }
        try {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE receipt_id = %s ORDER BY id ASC LIMIT 2", $receipt_id), ARRAY_A);
            if (!is_array($rows) || $wpdb->last_error) return new WP_Error('audit_unavailable', 'Consent confirmation unavailable.', ['status' => 503]);
            if (count($rows) > 1) return new WP_Error('ambiguous', 'Ambiguous consent interaction.', ['status' => 409]);
            if ($rows) {
                foreach ($data as $key => $value) {
                    if ((string) ($rows[0][$key] ?? '') !== $value) return new WP_Error('event_conflict', 'This interaction already records a different choice.', ['status' => 409]);
                }
                if (strtotime($rows[0]['server_created_at'] . ' UTC') + (int) $this->config['cookie_days'] * DAY_IN_SECONDS <= time()) {
                    return new WP_Error('expired', 'This interaction expired. Choose again.', ['status' => 409]);
                }
                return $this->acknowledgement($rows[0], $event);
            }
            $now = gmdate('Y-m-d H:i:s');
            $data += ['ip_hash' => $ip !== '' ? hash('sha256', $ip . wp_salt('nonce')) : null,
                'user_agent_hash' => $user_agent !== '' ? hash('sha256', $user_agent . wp_salt('nonce')) : null,
                'created_at' => $now, 'server_created_at' => $now];
            if (!$wpdb->insert($table, $data)) return new WP_Error('audit_failed', 'Consent confirmation could not be saved.', ['status' => 503]);
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $wpdb->insert_id), ARRAY_A);
            if (!is_array($row) || $wpdb->last_error) return new WP_Error('audit_unavailable', 'Consent confirmation unavailable.', ['status' => 503]);
            foreach ($data as $key => $value) {
                if (($row[$key] ?? null) !== $value) return new WP_Error('audit_unavailable', 'Consent confirmation unavailable.', ['status' => 503]);
            }
            return $this->acknowledgement($row, $event);
        } finally {
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    /** Read-only resolution. Cookie fields other than the signed acknowledgement are untrusted. */
    public function resolve($cookie): array {
        global $wpdb;
        if (!is_array($cookie)) return $this->failure('missing');
        if (empty($cookie['audit_token'])) {
            $state = ($cookie['contract_version'] ?? null) === 1 ? ($cookie['audit_status'] ?? 'pending') : 'unsigned_legacy';
            return $this->failure(in_array($state, ['pending','failed','unsigned_legacy'], true) ? $state : 'unverified');
        }
        if (!is_string($cookie['audit_token']) || strlen($cookie['audit_token']) > 2048) return $this->failure('tampered');
        $parts = explode('.', $cookie['audit_token']);
        if (count($parts) !== 2 || !hash_equals(hash_hmac('sha256', $parts[0], wp_salt('auth')), $parts[1])) return $this->failure('tampered');
        $proof = json_decode((string) base64_decode($parts[0], true), true);
        if (!is_array($proof) || ($proof['v'] ?? null) !== 1 || !is_int($proof['row_id'] ?? null) || $proof['row_id'] < 1 || !is_int($proof['expires'] ?? null)
            || !is_string($proof['event_id'] ?? null)
            || !is_string($proof['receipt_id'] ?? null) || !preg_match('/^sc1_[a-f0-9]{60}$/D', $proof['receipt_id']) || !is_string($proof['digest'] ?? null)) return $this->failure('tampered');
        if ($proof['expires'] <= time()) return $this->failure('expired');
        $table = $wpdb->prefix . 'se_consent_receipts';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE receipt_id = %s ORDER BY id ASC LIMIT 2", $proof['receipt_id']), ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error) return $this->failure('unavailable');
        if (count($rows) > 1) return $this->failure('ambiguous');
        if (!$rows) return $this->failure('missing_receipt');
        $row = $rows[0];
        if ((int) $row['id'] !== $proof['row_id'] || !hash_equals(self::receipt_digest($row), $proof['digest'])) return $this->failure('tampered');
        if ($row['policy_version'] !== $this->config['policy_version'] || $row['banner_version'] !== $this->config['banner_version']) return $this->failure('stale');
        $c = json_decode($row['consent_json'], true);
        foreach (['necessary','analytics','advertising','personalization'] as $key) {
            if (!is_array($c) || !isset($c[$key]) || !is_bool($c[$key])) return $this->failure('invalid_receipt');
        }
        if ($c['necessary'] !== true || json_decode($row['google_consent_json'], true) !== self::google($c)) return $this->failure('invalid_receipt');
        // A retained ACK must not override contradictory/missing current browser state.
        if (($cookie['contract_version'] ?? null) !== 1 || ($cookie['event_id'] ?? null) !== $proof['event_id'] || ($cookie['receipt_id'] ?? null) !== $row['receipt_id']
            || ($cookie['policy_version'] ?? null) !== $row['policy_version'] || ($cookie['banner_version'] ?? null) !== $row['banner_version']
            || ($cookie['consent'] ?? null) !== $c || ($cookie['google_consent'] ?? null) !== self::google($c)) return $this->failure('conflicting_cookie');
        return ['contract_version' => 1, 'state' => 'verified', 'receipt_id' => $row['receipt_id'], 'receipt_row_id' => (int) $row['id'],
            'method' => $row['method'], 'policy_version' => $row['policy_version'], 'banner_version' => $row['banner_version'],
            'receipt_digest' => self::receipt_digest($row), 'created_at' => $row['server_created_at'], 'consent' => $c, 'google_consent' => self::google($c), 'captured_at' => gmdate('Y-m-d H:i:s')];
    }

    public function negative(string $state): array { return $this->failure($state); }

    private static function canonical(array $data): array {
        ksort($data);
        foreach ($data as &$value) if (is_array($value)) $value = self::canonical($value);
        return $data;
    }

    public static function seal_stamp(array $stamp, int $form_id): array {
        unset($stamp['signature']);
        $stamp['form_id'] = $form_id;
        $stamp['signature'] = hash_hmac('sha256', wp_json_encode(self::canonical($stamp)), wp_salt('auth'));
        return $stamp;
    }

    /** Historical signature verification only; callers still enforce row/policy/export gates. */
    public static function verify_stamp(array $stamp, int $form_id): bool {
        if (($stamp['contract_version'] ?? null) !== 1 || ($stamp['form_id'] ?? null) !== $form_id || !is_string($stamp['signature'] ?? null)) return false;
        $signature = $stamp['signature']; unset($stamp['signature']);
        return hash_equals(hash_hmac('sha256', wp_json_encode(self::canonical($stamp)), wp_salt('auth')), $signature);
    }

    public function stamp(array $response, array $stamp): array {
        foreach (array_keys($response) as $key) {
            if (preg_match('/^(?:_se_consent_server$|se_consent_|se_google_)/', trim((string) $key))) unset($response[$key]);
        }
        $response['_se_consent_server'] = $stamp;
        $response['se_consent_receipt_id'] = $stamp['receipt_id'];
        foreach (['method','policy_version','banner_version'] as $key) $response['se_consent_' . $key] = $stamp[$key] ?? '';
        $response['se_consent_timestamp'] = $stamp['created_at'] ?? '';
        foreach (['analytics','advertising','personalization'] as $key) $response['se_consent_' . $key] = $stamp['consent'][$key] ? 'granted' : 'denied';
        foreach (['ad_storage','analytics_storage','ad_user_data','ad_personalization'] as $key) $response['se_google_' . $key] = $stamp['google_consent'][$key];
        return $response;
    }
}
