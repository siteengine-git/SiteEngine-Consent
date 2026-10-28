<?php
// Run the unchanged, hash-pinned Ads resolver against synthetic in-memory storage.
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('WP_CLI')) define('WP_CLI', true);
if (!defined('ARRAY_A')) define('ARRAY_A', 'ARRAY_A');
function get_option($key, $default = '') { return $default; }
class SiteEngine_Ads_Bridge_Config {
    public function get($key, $default = '') { global $fixture_policy; return $key === 'consent.required_policy_version' ? ($fixture_policy ?? 'fixture-policy') : $default; }
}
class Consent_Consumer_Fixture_DB {
    public $prefix = 'fixture_';
    public $response = [];
    public $details = '';
    public $rows = [];
    public function prepare($sql, ...$args) { return [$sql, $args]; }
    public function get_col($sql) {
        if (strpos($sql, 'fluentform_submissions') !== false) return ['response'];
        if (strpos($sql, 'entry_details') !== false) return ['submission_id', 'field_name', 'field_value'];
        return ['receipt_id', 'google_consent_json'];
    }
    public function get_var($q) { return strpos($q[0], 'SELECT response') !== false ? json_encode($this->response) : $this->details; }
    public function get_row($q, $format) {
        $matching = array_values(array_filter($this->rows, function ($r) use ($q) { return $r['receipt_id'] === $q[1][0]; }));
        usort($matching, function ($a, $b) { return $b['id'] <=> $a['id']; });
        return $matching[0] ?? null;
    }
}
$path = $argv[1] ?? getenv('SE_ADS_CLI_SOURCE');
if (!$path || !is_file($path)) { fwrite(STDERR, "Pass the original Ads Bridge 0.6.3 CLI source path. No live site is loaded.\n"); exit(2); }
if (hash_file('sha256', $path) !== '7aa5f4501a237b3ec640f76a8befc9fc400a892461ae2bd73c4b3c6f149324da') {
    fwrite(STDERR, "Consumer source changed; review the contract before repinning.\n"); exit(2);
}
require $path;
$wpdb = new Consent_Consumer_Fixture_DB();
$method = new ReflectionMethod('SiteEngine_Ads_Bridge_CLI', 'resolve_consent_provenance');
$method->setAccessible(true);
$consumer = new SiteEngine_Ads_Bridge_CLI();
function resolve_fixture() {
    global $method, $consumer;
    return $method->invoke($consumer, ['deterministic' => true, 'entry_id' => '1', 'form_id' => '1', 'submissions_table' => 'fixture_fluentform_submissions'], new SiteEngine_Ads_Bridge_Config());
}
function check_contract($condition, $name) { if (!$condition) throw new RuntimeException($name); echo "PASS $name\n"; }
$grant = ['id' => 1, 'receipt_id' => 'synthetic-existing-grant', 'policy_version' => 'fixture-policy', 'banner_version' => 'fixture-banner', 'google_consent_json' => '{"ad_user_data":"granted","ad_personalization":"denied"}'];
if (isset($fixture_policy)) $grant['policy_version'] = $fixture_policy;
$wpdb->rows = [$grant];
$wpdb->response = ['_se_consent_server' => ['contract_version' => 1, 'state' => 'missing', 'receipt_id' => ''], 'se_consent_receipt_id' => ''];
check_contract(resolve_fixture()['decision'] === 'blocked', 'fresh negative stamp with empty response/details blocks without invented receipt');
$wpdb->details = $grant['receipt_id'];
check_contract(resolve_fixture()['decision'] === 'exportable', 'COMPATIBILITY BLOCKER: real legacy resolver ignores negative stamp and accepts preexisting details grant');
$wpdb->details = '';
$wpdb->response['se_consent_receipt_id'] = $grant['receipt_id'];
check_contract(resolve_fixture()['decision'] === 'exportable', 'COMPATIBILITY BLOCKER: real legacy resolver ignores negative stamp and accepts flat grant');
$wpdb->response = ['_se_consent_server' => ['receipt_id' => $grant['receipt_id']]];
$reject = $grant; $reject['id'] = 2; $reject['google_consent_json'] = '{"ad_user_data":"denied"}';
$wpdb->rows[] = $reject;
check_contract(resolve_fixture()['decision'] === 'blocked', 'older binding resolves later rejection when receipt ID reused');
$grant['id'] = 3; $wpdb->rows[] = $grant;
check_contract(resolve_fixture()['decision'] === 'exportable', 'COMPATIBILITY BLOCKER: older binding resolves later grant when receipt ID reused');
echo "Consumer fixture checks complete. Expected unsafe outcomes above are blockers, not integration acceptance.\n";
if (isset($consent_cases)) {
    $wpdb->rows = $consent_cases['rows']; $wpdb->details = '';
    $wpdb->response = $consent_cases['response'];
    check_contract(resolve_fixture()['decision'] === 'exportable', 'actual Consent positive stamp resolves genuine synthetic recorded event through unchanged consumer');
    $wpdb->response = $consent_cases['negative'];
    check_contract(resolve_fixture()['decision'] === 'blocked', 'actual Consent negative response with fresh serialized details blocks unchanged consumer');
    $persisted = array_values(array_filter($consent_cases['details'], function ($r) { return $r['field_name'] === 'se_consent_receipt_id'; }));
    $wpdb->details = $persisted[0]['field_value'];
    check_contract(resolve_fixture()['decision'] === 'exportable', 'COMPATIBILITY BLOCKER: real consumer accepts genuine preexisting serialized grant despite actual Consent negative stamp');
}
