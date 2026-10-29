<?php
/** Prospective same-browser continuity. No constructor/bootstrap writes. */
defined('ABSPATH') || exit;

final class SiteEngine_Consent_Continuity {
    private static function mac(array $row): string {
        unset($row['integrity']); ksort($row);
        foreach ($row as &$value) $value = (string) $value;
        return hash_hmac('sha256', wp_json_encode($row), wp_salt('auth'));
    }
    private static function valid($row): bool {
        return is_array($row) && is_string($row['integrity'] ?? null) && hash_equals(self::mac($row), $row['integrity']);
    }
    public static function schema(string $prefix, string $charset): array {
        return ["CREATE TABLE {$prefix}se_consent_chains (
            chain_id char(64) NOT NULL,
            revision bigint(20) unsigned NOT NULL,
            expires_at bigint(20) unsigned NOT NULL,
            consent_json longtext NOT NULL,
            policy_version varchar(64) NOT NULL,
            banner_version varchar(64) NOT NULL,
            analytics_barrier bigint(20) unsigned NOT NULL,
            advertising_barrier bigint(20) unsigned NOT NULL,
            personalization_barrier bigint(20) unsigned NOT NULL,
            integrity char(64) NOT NULL,
            PRIMARY KEY  (chain_id)
        ) ENGINE=InnoDB {$charset};", "CREATE TABLE {$prefix}se_consent_events (
            receipt_row_id bigint(20) unsigned NOT NULL,
            receipt_id varchar(64) NOT NULL,
            chain_id char(64) NOT NULL,
            revision bigint(20) unsigned NOT NULL,
            intent_hash char(64) NOT NULL,
            receipt_digest char(64) NOT NULL,
            integrity char(64) NOT NULL,
            PRIMARY KEY  (receipt_row_id),
            UNIQUE KEY receipt_id (receipt_id),
            UNIQUE KEY chain_revision (chain_id, revision)
        ) ENGINE=InnoDB {$charset};"];
    }
    private static function context(string $chain, int $revision): array {
        $body = $chain . ':' . $revision;
        return ['revision' => $revision, 'token' => $body . ':' . hash_hmac('sha256', 'continuity:' . $body, wp_salt('auth'))];
    }
    private static function error(string $code, $head = null) {
        $data = ['status' => 409];
        if (self::valid($head)) $data['continuity'] = self::context($head['chain_id'], (int) $head['revision']);
        return new WP_Error($code, 'Consent confirmation failed. Retry rejection, or refresh and choose again.', $data);
    }
    /** Capture is the only writer. A transaction and chain lock order all browser interactions. */
    public static function capture(array $payload, array $data, int $lifetime) {
        global $wpdb;
        $key = $payload['continuity_key'] ?? '';
        if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D', $key)) return self::error('continuity_missing');
        $chain = hash_hmac('sha256', home_url('/') . ':' . $wpdb->prefix . ':' . $key, wp_salt('auth'));
        $token = $payload['continuity_token'] ?? '';
        if (!is_string($token)) return self::error('continuity_invalid');
        $base = 0;
        if ($token !== '') {
            $parts = explode(':', $token);
            if (count($parts) !== 3 || $parts[0] !== $chain || !ctype_digit($parts[1]) || strlen($parts[1]) > 15
                || !hash_equals(self::context($chain, (int) $parts[1])['token'], $token)) return self::error('continuity_invalid');
            $base = (int) $parts[1];
        }
        $receipts = $wpdb->prefix . 'se_consent_receipts';
        $chains = $wpdb->prefix . 'se_consent_chains'; $events = $wpdb->prefix . 'se_consent_events';
        // Never rely on rollback across a nontransactional legacy receipts table. No engine conversion here.
        foreach ([$receipts, $chains, $events] as $table) {
            $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table));
            if (strtoupper((string) $engine) !== 'INNODB' || $wpdb->last_error) return self::error('transaction_storage_unavailable');
        }
        $lock = 'se_consent_' . substr(hash('sha256', $chains . $chain), 0, 50);
        if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 3)', $lock)) !== '1') return self::error('audit_busy');
        $committed = false;
        try {
            if ($wpdb->query('START TRANSACTION') === false) return self::error('audit_unavailable');
            $head = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$chains} WHERE chain_id = %s FOR UPDATE", $chain), ARRAY_A);
            if ($wpdb->last_error || ($head && !self::valid($head))) return self::error('continuity_unavailable');
            if (!$head) {
                $prior = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$events} WHERE chain_id = %s LIMIT 1", $chain), ARRAY_A);
                // Missing head is corruption, not permission to reset a retained chain's barriers.
                if ($wpdb->last_error || $prior) return self::error('continuity_unavailable');
            }
            // Bind identity to this browser/site, not a visitor-supplied receipt ID.
            $data['receipt_id'] = 'sc1_' . substr(hash_hmac('sha256', $chain . ':' . $payload['event_id'], wp_salt('auth')), 0, 60);
            $intent_data = $data; unset($intent_data['ip_hash'], $intent_data['user_agent_hash']);
            $intent = hash('sha256', wp_json_encode([$intent_data, $token]));
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$receipts} WHERE receipt_id = %s ORDER BY id ASC LIMIT 2", $data['receipt_id']), ARRAY_A);
            if (!is_array($rows) || $wpdb->last_error) return self::error('audit_unavailable');
            if (count($rows) > 1) return self::error('ambiguous');
            if ($rows) {
                $link = self::link((int) $rows[0]['id']);
                if (!self::valid($link) || $link['chain_id'] !== $chain || $link['intent_hash'] !== $intent
                    || $link['receipt_digest'] !== SiteEngine_Consent_Authority::receipt_digest($rows[0])) return self::error('event_conflict');
                if (!$head || (int) $link['revision'] !== (int) $head['revision']) return self::error('superseded', $head);
                return ['row' => $rows[0], 'link' => $link, 'continuity' => self::context($chain, (int) $head['revision'])];
            }
            $revision = $head ? (int) $head['revision'] : 0;
            $c = json_decode($data['consent_json'], true);
            $reject_all = !$c['analytics'] && !$c['advertising'] && !$c['personalization'];
            // Stale complete rejection is safe; stale grants/partial choices must be made again explicitly.
            if ($base > $revision || ($base !== $revision && !$reject_all)) return self::error('continuity_stale', $head);
            $next = ['chain_id' => $chain, 'revision' => $revision + 1, 'expires_at' => time() + $lifetime, 'consent_json' => $data['consent_json'],
                'policy_version' => $data['policy_version'], 'banner_version' => $data['banner_version']];
            foreach (['analytics','advertising','personalization'] as $category) {
                $next[$category . '_barrier'] = $c[$category] ? (int) ($head[$category . '_barrier'] ?? 0) : $revision + 1;
            }
            $now = gmdate('Y-m-d H:i:s');
            $row = $data + ['created_at' => $now, 'server_created_at' => $now];
            if (!$wpdb->insert($receipts, $row)) return self::error('audit_failed');
            $id = (int) $wpdb->insert_id;
            $stored = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$receipts} WHERE id = %d", $id), ARRAY_A);
            if (!is_array($stored) || $wpdb->last_error) return self::error('audit_unavailable');
            foreach ($row as $field => $value) if (($stored[$field] ?? null) !== $value) return self::error('audit_unavailable');
            $link = ['receipt_row_id' => $id, 'receipt_id' => $stored['receipt_id'], 'chain_id' => $chain, 'revision' => $revision + 1,
                'intent_hash' => $intent, 'receipt_digest' => SiteEngine_Consent_Authority::receipt_digest($stored)];
            $link['integrity'] = self::mac($link); $next['integrity'] = self::mac($next);
            if (!$wpdb->insert($events, $link) || !$wpdb->replace($chains, $next)) return self::error('audit_failed');
            $read = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$chains} WHERE chain_id = %s", $chain), ARRAY_A);
            if (!self::valid($read) || $read['integrity'] !== $next['integrity'] || (self::link($id)['integrity'] ?? '') !== $link['integrity'] || $wpdb->last_error) return self::error('audit_unavailable');
            if ($wpdb->query('COMMIT') === false) return self::error('audit_unavailable');
            $committed = true;
            return ['row' => $stored, 'link' => $link, 'continuity' => self::context($chain, $revision + 1)];
        } catch (Throwable $e) {
            return self::error('audit_unavailable');
        } finally {
            if (!$committed) $wpdb->query('ROLLBACK');
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }
    public static function link(int $row_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'se_consent_events';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE receipt_row_id = %d", $row_id), ARRAY_A);
        return !$wpdb->last_error && self::valid($row) ? $row : null;
    }
    public static function extension(array $link): array {
        return ['continuity_version' => 1, 'chain_id' => $link['chain_id'], 'event_revision' => (int) $link['revision'], 'continuity_digest' => $link['integrity']];
    }
    /** Read only; the last head SELECT is the decision's linearization point. No cached decisions. */
    public static function eligibility(array $stamp, int $form_id, string $required_policy): array {
        global $wpdb;
        $out = ['contract_version' => 2, 'available' => false, 'allowed' => false, 'reason' => 'invalid_evidence', 'historical_verified' => false,
            'checked_at' => gmdate('c'), 'event_revision' => 0, 'current_revision' => 0,
            'consent' => ['necessary' => true, 'analytics' => false, 'advertising' => false, 'personalization' => false]];
        try {
            if (!SiteEngine_Consent_Authority::verify_stamp($stamp, $form_id) || ($stamp['state'] ?? '') !== 'verified') return $out;
            $table = $wpdb->prefix . 'se_consent_receipts';
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE receipt_id = %s OR id = %d ORDER BY id ASC LIMIT 2", $stamp['receipt_id'] ?? '', $stamp['receipt_row_id'] ?? 0), ARRAY_A);
            if (!is_array($rows) || $wpdb->last_error) { $out['reason'] = 'storage_unavailable'; return $out; }
            if (count($rows) !== 1) { $out['reason'] = 'missing_or_ambiguous_receipt'; return $out; }
            $row = $rows[0];
            foreach (['id','receipt_id','method','policy_version','banner_version','consent_json','google_consent_json','page_url','referrer_url','ip_hash','user_agent_hash','created_at','server_created_at'] as $column) {
                if (!array_key_exists($column, $row) || (!is_scalar($row[$column]) && $row[$column] !== null)) return $out;
            }
            foreach (['method','policy_version','banner_version'] as $column) if (($stamp[$column] ?? null) !== $row[$column]) return $out;
            if (($stamp['created_at'] ?? null) !== $row['server_created_at']) return $out;
            if ((int) $row['id'] !== ($stamp['receipt_row_id'] ?? null) || $row['receipt_id'] !== ($stamp['receipt_id'] ?? null)
                || !hash_equals(SiteEngine_Consent_Authority::receipt_digest($row), $stamp['receipt_digest'] ?? '')) return $out;
            $c = json_decode($row['consent_json'], true);
            foreach (['necessary','analytics','advertising','personalization'] as $k) if (!is_bool($c[$k] ?? null)) return $out;
            if (!$c['necessary'] || $c !== ($stamp['consent'] ?? null) || SiteEngine_Consent_Authority::google($c) !== json_decode($row['google_consent_json'], true)) return $out;
            if (($stamp['google_consent'] ?? null) !== SiteEngine_Consent_Authority::google($c)) return $out;
            $out['historical_verified'] = true;
            $out['reason'] = 'continuity_unavailable';
            $link = self::link((int) $row['id']);
            if (!$link || $link['receipt_id'] !== $row['receipt_id'] || $link['receipt_digest'] !== SiteEngine_Consent_Authority::receipt_digest($row)) return $out;
            foreach (self::extension($link) as $k => $v) if (($stamp[$k] ?? null) !== $v) return $out;
            $chains = $wpdb->prefix . 'se_consent_chains';
            $head = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$chains} WHERE chain_id = %s LOCK IN SHARE MODE", $link['chain_id']), ARRAY_A);
            if ($wpdb->last_error || !self::valid($head) || (int) $head['revision'] < (int) $link['revision']) return $out;
            $current = json_decode($head['consent_json'], true);
            foreach (['necessary','analytics','advertising','personalization'] as $k) if (!is_bool($current[$k] ?? null)) return $out;
            $out['available'] = true; $out['event_revision'] = (int) $link['revision']; $out['current_revision'] = (int) $head['revision'];
            if ((int) ($head['expires_at'] ?? 0) <= time()) { $out['reason'] = 'expired'; return $out; }
            if ($required_policy === '' || $row['policy_version'] !== $required_policy || $head['policy_version'] !== $required_policy) { $out['reason'] = 'policy_mismatch'; return $out; }
            if ($row['banner_version'] !== $head['banner_version']) { $out['reason'] = 'banner_mismatch'; return $out; }
            foreach (['analytics','advertising','personalization'] as $k) $out['consent'][$k] = $c[$k] && $current[$k] && (int) $link['revision'] > (int) $head[$k . '_barrier'];
            $out['allowed'] = $out['consent']['advertising'];
            $out['reason'] = $out['allowed'] ? 'eligible' : ($c['advertising'] ? 'withdrawn' : 'denied');
            return $out;
        } catch (Throwable $e) { $out['available'] = false; $out['allowed'] = false; $out['reason'] = 'storage_unavailable'; return $out; }
    }
}
