<?php
/** Administrator diagnostics. Metadata reads only; never inspects consent records. */
defined('ABSPATH') || exit;

final class SiteEngine_Consent_Diagnostics {
    private static function authorize(): void {
        if (!current_user_can('manage_options')) wp_die('Insufficient permissions.');
    }

    /** Expected schema, independent of the installed metadata being inspected. */
    public static function requirements(): array {
        $receipt = ['id'=>'bigint unsigned','receipt_id'=>'varchar(64)','method'=>'varchar(64)',
            'policy_version'=>'varchar(64)','banner_version'=>'varchar(64)','consent_json'=>'longtext',
            'google_consent_json'=>'longtext','page_url'=>'text','referrer_url'=>'text','ip_hash'=>'char(64)',
            'user_agent_hash'=>'char(64)','created_at'=>'datetime','server_created_at'=>'datetime'];
        return [
            'receipts'=>['columns'=>$receipt, 'nullable'=>['page_url','referrer_url','ip_hash','user_agent_hash'],
                'indexes'=>['PRIMARY'=>[0,['id']], 'receipt_id'=>[1,['receipt_id']],
                    'policy_version'=>[1,['policy_version']], 'created_at'=>[1,['created_at']]]],
            'events'=>['columns'=>['receipt_row_id'=>'bigint unsigned','receipt_id'=>'varchar(64)',
                'chain_id'=>'char(64)','revision'=>'bigint unsigned','intent_hash'=>'char(64)',
                'receipt_digest'=>'char(64)','integrity'=>'char(64)'], 'nullable'=>[],
                'indexes'=>['PRIMARY'=>[0,['receipt_row_id']], 'receipt_id'=>[0,['receipt_id']],
                    'chain_revision'=>[0,['chain_id','revision']]]],
            'chains'=>['columns'=>['chain_id'=>'char(64)','revision'=>'bigint unsigned',
                'expires_at'=>'bigint unsigned','consent_json'=>'longtext','policy_version'=>'varchar(64)',
                'banner_version'=>'varchar(64)','analytics_barrier'=>'bigint unsigned',
                'advertising_barrier'=>'bigint unsigned','personalization_barrier'=>'bigint unsigned',
                'integrity'=>'char(64)'], 'nullable'=>[], 'indexes'=>['PRIMARY'=>[0,['chain_id']]]],
        ];
    }

    private static function schema_check(string $kind, array $spec): array {
        global $wpdb;
        // WP prefixes are trusted configuration, but never interpolate arbitrary identifiers.
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $wpdb->prefix)) return ['status'=>'failed','detail'=>'Unsupported table prefix.'];
        $table = $wpdb->prefix . 'se_consent_' . $kind;
        $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table));
        if ($wpdb->last_error) return ['status'=>'untested','detail'=>'Table metadata unavailable. Check database metadata-read permissions.'];
        if ($engine === null) return ['status'=>'failed','detail'=>'Required table not visible (missing or inaccessible). Check permissions and the supported upgrade procedure; diagnostics do not repair storage.'];
        if (strtoupper((string) $engine) !== 'INNODB') return ['status'=>'failed','detail'=>'InnoDB required. Capture cannot safely persist across incompatible engines. Escalate a backed-up storage migration.'];
        $columns = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$table}`", ARRAY_A);
        if (!is_array($columns) || $wpdb->last_error) return ['status'=>'untested','detail'=>'Column metadata unavailable.'];
        $actual = [];
        foreach ($columns as $column) $actual[$column['Field']] = $column;
        foreach ($spec['columns'] as $name=>$type) {
            $column = $actual[$name] ?? [];
            $normalized = preg_replace('/bigint\(\d+\)/', 'bigint', strtolower($column['Type'] ?? ''));
            $nullable = in_array($name, $spec['nullable'], true) ? 'YES' : 'NO';
            if ($normalized !== $type || ($column['Null'] ?? '') !== $nullable
                || ($kind === 'receipts' && $name === 'id' && strpos($column['Extra'] ?? '', 'auto_increment') === false)) {
                return ['status'=>'failed','detail'=>'Required column type/nullability or receipt auto-increment differs. Review schema against the packaged DDL; do not reset history.'];
            }
        }
        $indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_A);
        if (!is_array($indexes) || $wpdb->last_error) return ['status'=>'untested','detail'=>'Index metadata unavailable.'];
        $keys = [];
        foreach ($indexes as $index) {
            $name = $index['Key_name'];
            $keys[$name]['unique'] = (int) $index['Non_unique'];
            $keys[$name]['columns'][(int) $index['Seq_in_index']] = $index['Column_name'];
            if (!empty($index['Sub_part'])) return ['status'=>'failed','detail'=>'Unexpected partial index; exact immutable identity indexes required.'];
        }
        foreach ($keys as &$key) { ksort($key['columns']); $key['columns'] = array_values($key['columns']); }
        unset($key);
        foreach ($spec['indexes'] as $name=>$expected) {
            if (!isset($keys[$name]) || $keys[$name]['unique'] !== $expected[0] || $keys[$name]['columns'] !== $expected[1]) {
                return ['status'=>'failed','detail'=>'Required index differs or is missing. Preserve nonunique legacy receipt IDs and immutable event uniqueness.'];
            }
        }
        // An extra uniqueness restriction may reject valid history/new events; do not certify it.
        foreach ($keys as $name=>$key) if (!isset($spec['indexes'][$name]) && $key['unique'] === 0) {
            return ['status'=>'failed','detail'=>'Unexpected unique index. Review without altering legacy records.'];
        }
        return ['status'=>'verified','detail'=>'Required columns, indexes and InnoDB engine verified from metadata; no record integrity or write test performed.'];
    }

    private static function version($value): string {
        return is_string($value) && preg_match('/^[0-9][0-9A-Za-z.+_-]{0,39}$/D', $value) ? $value : 'unknown';
    }

    /** Allowlist output: never merge raw config, SQL errors, headers or request data. */
    public static function collect(array $config): array {
        self::authorize();
        global $wpdb, $wp_version;
        $report = ['report_version'=>1, 'versions'=>['consent'=>self::version(SE_CONSENT_VERSION),
            'wordpress'=>self::version($wp_version ?? ''), 'php'=>self::version(PHP_VERSION)],
            'configuration'=>[], 'checks'=>[], 'limits'=>[
                'Read-only metadata snapshot after ordinary WordPress bootstrap. Bootstrap can update version/schema options, cron and privacy transients or perform the existing privacy URL probe.',
                'No receipts, submissions, cookies, raw records, secrets, business names, URLs, database names or server paths are included.',
                'No capture, browser storage, nonce/cache lifecycle, record integrity, transaction durability, concurrent access, Ads enforcement or external delivery is tested.',
                'A signature or receipt proves neither person identity, consent intent nor permission. Historical verification and current eligibility are separate.',
            ]];
        foreach (['base'=>'SEBASE_VERSION','fluent_forms'=>'FLUENTFORM_VERSION','ads_bridge'=>'SEAB_VERSION'] as $component=>$constant) {
            $report['versions'][$component] = defined($constant) ? self::version(constant($constant)) : 'not detected';
        }
        foreach (['cookie_days','retention_days','rate_limit_per_min'] as $key) {
            $n = $config[$key] ?? null;
            $report['configuration'][$key] = is_int($n) && $n >= 0 && $n <= 1000000 ? $n : 'invalid';
        }
        foreach (['company_configured','privacy_configured','privacy_https','debug'] as $key) $report['configuration'][$key] = ($config[$key] ?? null) === true;
        foreach (['company_source','privacy_source'] as $key) $report['configuration'][$key] = in_array($config[$key] ?? '', ['explicit_constant','base','wordpress'], true) ? $config[$key] : 'unknown';
        foreach (['policy_version','banner_version'] as $key) $report['configuration'][$key . '_sha256'] = hash('sha256', (string) ($config[$key] ?? ''));
        $report['configuration']['personalization_independent'] = true;
        $report['configuration']['base_available'] = function_exists('siteengine_get');
        $report['configuration']['fluent_forms_available'] = defined('FLUENTFORM');
        $old = $wpdb->suppress_errors(true);
        try {
            foreach (self::requirements() as $kind=>$spec) $report['checks']['storage_' . $kind] = self::schema_check($kind, $spec);
            $primary = $wpdb->get_var('SELECT @@read_only'); $primary_error = $wpdb->last_error;
            $auto = $wpdb->get_var('SELECT @@autocommit');
            $report['checks']['connection'] = $primary_error || $wpdb->last_error || $primary === null || $auto === null
                ? ['status'=>'untested','detail'=>'Connection mode unavailable.']
                : ['status'=>(string) $primary === '0' && (string) $auto === '1' ? 'verified' : 'failed',
                    'detail'=>'Capture expects a writable primary connection with autocommit. A metadata check does not establish routing or lock/durability behavior.'];
        } catch (Throwable $e) {
            $report['checks']['metadata_read'] = ['status'=>'untested','detail'=>'Metadata read interrupted. No raw database error is included.'];
        } finally { $wpdb->suppress_errors($old); }
        $api = false;
        if (is_callable(['SiteEngine_Consent_Authority','current_eligibility'])) {
            $method = new ReflectionMethod('SiteEngine_Consent_Authority','current_eligibility');
            $parameters = $method->getParameters();
            $api = $method->isPublic() && $method->isStatic() && count($parameters) === 3
                && (string) $parameters[0]->getType() === 'array' && (string) $parameters[1]->getType() === 'int'
                && (string) $parameters[2]->getType() === 'string' && (string) $method->getReturnType() === 'array';
        }
        $report['checks']['consumer_api'] = ['status'=>$api ? 'verified' : 'failed',
            'detail'=>'Expected contract 2 provider: current_eligibility(array stamp, int canonical_form_id, string required_policy): array. Signature availability only; consumer enforcement and real result semantics are untested here.'];
        $report['checks']['configuration'] = ['status'=>!in_array('invalid', $report['configuration'], true)
            && $report['configuration']['cookie_days'] !== 0 && !empty($config['policy_version']) && !empty($config['banner_version'])
            && $report['configuration']['company_configured'] && $report['configuration']['privacy_configured'] ? 'verified' : 'failed',
            'detail'=>'Effective configuration presence/range only. Check Business Data or explicit overrides; policy content, reachability and legal suitability are not verified.'];
        $report['checks']['cleanup_schedule'] = ['status'=>wp_next_scheduled('se_consent_daily_cleanup') ? 'verified' : 'failed',
            'detail'=>'Schedule presence only; no cleanup run or cron delivery verified. Do not run cleanup as a diagnostic.'];
        $report['checks']['end_to_end'] = ['status'=>'untested','detail'=>'Capture and Ads dispatch require separately authorized installed acceptance. API availability is not enforcement or successful external delivery.'];
        $statuses = array_column($report['checks'], 'status');
        $metadata_incomplete = false;
        foreach ($report['checks'] as $name=>$check) if ($name !== 'end_to_end' && $check['status'] === 'untested') $metadata_incomplete = true;
        $report['summary'] = in_array('failed', $statuses, true) ? 'Action required'
            : ($metadata_incomplete ? 'Metadata checks incomplete; operation untested' : 'Inspected prerequisites pass; end-to-end operation untested');
        return $report;
    }

    public static function explanations(): array {
        return [
            'Capture / acknowledgement'=>[
                'pending / failed'=>'No acknowledged authority. Forms remain usable but that submission stays negative; a delayed acknowledgement cannot upgrade it. Use the existing same-event retry control after a transport failure.',
                'invalid_event / invalid_consent / invalid_metadata'=>'Malformed interaction, category types or metadata rejected. Check the supported banner integration; do not manufacture IDs or override submitted flags.',
                'stale / expired'=>'Policy/banner changed or the preference expired. Reload the notice and make a new explicit choice.',
                'continuity_missing / continuity_invalid'=>'Browser/site continuity absent or invalid. Check secure randomness, durable storage and Web Locks support; do not transfer keys between browsers.',
                'continuity_stale / superseded'=>'A newer interaction exists. Older acknowledgements cannot restore it; refresh and explicitly choose again. Rejection applies locally immediately.',
                'transaction_storage_unavailable / audit_busy'=>'Storage engine incompatible or capture lock busy. Check storage diagnostics; retry the same event for temporary contention. Never reset chain heads or barriers.',
                'audit_unavailable / audit_failed / continuity_unavailable'=>'Persistence/readback/chain verification failed; no acknowledged authority is created. Check database access and storage without editing receipts.',
                'ambiguous / event_conflict'=>'Duplicate or conflicting immutable event identity. Stop and preserve evidence; do not deduplicate or rewrite receipts.',
                'HTTP 403 / 429 / transport failure'=>'Nonce/security check, rate limit or network failure. Refresh a stale page/nonce, respect the rate limit and inspect cache/network behavior; no grant is inferred.',
                'missing / unsigned_legacy / unverified / tampered'=>'Missing or unauthenticated browser evidence cannot authorize use. Renew via the supported notice; never backfill grants from legacy state.',
                'missing_receipt / invalid_receipt / conflicting_cookie / unavailable'=>'Stored evidence missing/inconsistent, browser claims conflict or storage cannot be read. Leave submission negative and investigate without raw-record exports.',
                'conflicting_claim / not_captured'=>'Form receipt claim differs from acknowledged evidence, or the supported capture filter did not freeze a stamp. Keep the submission negative and check the installed form/filter integration.',
                'storage refusal / multiple tabs'=>'Without required durable storage or browser APIs no acknowledgement is available. A newer tab choice takes precedence; clearing storage cannot identify an older chain.',
            ],
            'Current eligibility (result contract 2)'=>[
                'invalid_evidence'=>'Historical stamp/form/category/digest validation failed. Not eligible; a signature or receipt alone is insufficient.',
                'storage_unavailable / missing_or_ambiguous_receipt'=>'Authority cannot be resolved reliably. Fail closed; preserve historical records.',
                'continuity_unavailable'=>'Historical evidence may verify, but current chain/link/head cannot. Not eligible; do not rebuild barriers.',
                'expired / policy_mismatch / banner_mismatch'=>'Historical evidence does not satisfy current validity requirements. Check current authority at dispatch; never rewrite evidence or assume a later choice authorizes an older submission.',
                'denied'=>'The historical event did not grant advertising. Later re-grant does not upgrade this submission.',
                'withdrawn'=>'Historical advertising grant no longer clears current preferences/permanent withdrawal barriers. Re-grant cannot restore the old submission.',
                'eligible'=>'Current advertising category passes Consent only when available === true AND allowed === true. Ads must still enforce required policy, deterministic identity, DNC and dispatch gates; personalization remains independent.',
            ],
        ];
    }

    public static function render(array $config): void {
        self::authorize();
        $download = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['se_consent_support']);
        if ($download) check_admin_referer('se_consent_support');
        $report = self::collect($config);
        echo '<div class="wrap"><h1>Consent deployment diagnostics</h1><p>' . esc_html($report['summary']) . '</p>';
        echo '<p>Historical evidence is separate from current eligibility. These checks do not grant consent or prove Ads enforcement.</p>';
        echo '<table class="widefat striped"><thead><tr><th>Check</th><th>Result</th><th>Meaning / recovery</th></tr></thead><tbody>';
        foreach ($report['checks'] as $name=>$check) echo '<tr><td>' . esc_html($name) . '</td><td>' . esc_html($check['status']) . '</td><td>' . esc_html($check['detail']) . '</td></tr>';
        echo '</tbody></table><h2>Effective nonsecret configuration</h2><pre>' . esc_html(wp_json_encode($report['configuration'], JSON_PRETTY_PRINT)) . '</pre>';
        foreach ($report['limits'] as $limit) echo '<p>' . esc_html($limit) . '</p>';
        echo '<h2>Redacted support report</h2><p>Generate JSON below, then copy it for your support contact. No report is stored or sent.</p><form method="post">';
        wp_nonce_field('se_consent_support');
        echo '<button class="button button-primary" name="se_consent_support" value="1">Generate redacted report</button></form>';
        if ($download) echo '<label for="se-consent-report">Support report JSON</label><textarea id="se-consent-report" readonly rows="18" class="large-text code">' . esc_textarea(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea>';
        foreach (self::explanations() as $heading=>$items) {
            echo '<h2>' . esc_html($heading) . '</h2><p>Reference explanations, not observed failures or a history log.</p><dl>';
            foreach ($items as $code=>$meaning) echo '<dt><strong>' . esc_html($code) . '</strong></dt><dd>' . esc_html($meaning) . '</dd>';
            echo '</dl>';
        }
        echo '</div>';
    }
}
