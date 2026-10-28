<?php
// Isolated WordPress boundary doubles. No site, network or database connection.
define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');
define('DAY_IN_SECONDS', 86400);
define('MINUTE_IN_SECONDS', 60);
define('WP_CLI', true);
class WP_CLI { public static function add_command(...$args) {} }
set_error_handler(function ($severity, $message) { throw new ErrorException($message, 0, $severity); });
$hooks = [];
function add_filter($name, $callback, $priority = 10, $accepted = 1) { global $hooks; $hooks[$name][$priority][] = [$callback, $accepted]; }
function add_action($name, $callback, $priority = 10, $accepted = 1) { add_filter($name, $callback, $priority, $accepted); }
function apply_filters($name, $value, ...$args) {
    global $hooks; $all = $hooks[$name] ?? []; ksort($all);
    foreach ($all as $list) foreach ($list as $item) $value = call_user_func_array($item[0], array_slice(array_merge([$value], $args), 0, $item[1]));
    return $value;
}
function register_activation_hook(...$args) {}
function register_deactivation_hook(...$args) {}
function get_bloginfo($key) { return 'Synthetic fixture site'; }
function home_url($path = '') { return 'https://example.invalid' . $path; }
function wp_salt($scheme) { return 'synthetic-test-only-not-a-live-secret-' . $scheme; }
function wp_json_encode($value) { return json_encode($value, JSON_UNESCAPED_SLASHES); }
function sanitize_text_field($s) { return trim(strip_tags($s)); }
function esc_url_raw($s) { return $s; }
function wp_unslash($s) { return $s; }
class Fixture_Json_Response extends RuntimeException {
    public $data; public $status; public $success;
    public function __construct($data, $status, $success) { parent::__construct('fixture response'); $this->data = $data; $this->status = $status; $this->success = $success; }
}
function wp_send_json_error($data, $status = 400) { throw new Fixture_Json_Response($data, $status, false); }
function wp_send_json_success($data) { throw new Fixture_Json_Response($data, 200, true); }
function check_ajax_referer($action, $key) { if (($_POST[$key] ?? '') !== 'synthetic-valid-nonce') wp_send_json_error(['code' => 'invalid_nonce'], 403); }
$fixture_transients = [];
function get_transient($key) { global $fixture_transients; return $fixture_transients[$key] ?? false; }
function set_transient($key, $value, $seconds) { global $fixture_transients; $fixture_transients[$key] = $value; }
class WP_Error {
    private $code; private $message; private $data;
    public function __construct($code, $message, $data = []) { $this->code = $code; $this->message = $message; $this->data = $data; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($x) { return $x instanceof WP_Error; }
class Receipt_Fixture_DB {
    public $prefix = 'fixture_'; public $last_error = ''; public $insert_id = 0;
    public $rows = []; public $lock = true; public $fail_select = false; public $fail_insert = false; public $fail_readback = false;
    public $releases = 0;
    public function prepare($sql, ...$args) { return [$sql, $args]; }
    public function get_var($q) { if (strpos($q[0], 'RELEASE_LOCK') !== false) { $this->releases++; return '1'; } return $this->lock ? '1' : null; }
    public function get_results($q, $format) {
        $this->last_error = $this->fail_select ? 'fixture unavailable' : '';
        if ($this->fail_select) return null;
        return array_slice(array_values(array_filter($this->rows, function ($r) use ($q) { return $r['receipt_id'] === $q[1][0]; })), 0, 2);
    }
    public function get_row($q, $format) { return $this->fail_readback ? null : ($this->rows[$q[1][0]] ?? null); }
    public function insert($table, $row) {
        if ($this->fail_insert) return false;
        $this->insert_id++; $row['id'] = $this->insert_id; $this->rows[$this->insert_id] = $row; return 1;
    }
}
require dirname(__DIR__) . '/siteengine-consent.php';
$config = ['policy_version' => '2026-05-08-v1', 'banner_version' => 'fixture-banner', 'cookie_days' => 390];
$wpdb = new Receipt_Fixture_DB();
$authority = new SiteEngine_Consent_Authority($config);
$count = 0;
function ok($condition, $name) { global $count; if (!$condition) throw new RuntimeException($name); $count++; echo "PASS $name\n"; }
function event_payload($number, $grant = true) {
    global $config;
    return ['event_id' => sprintf('00000000-0000-4000-8000-%012d', $number), 'method' => 'save_preferences', 'policy_version' => $config['policy_version'], 'banner_version' => $config['banner_version'],
        'consent' => ['necessary' => true, 'analytics' => false, 'advertising' => $grant, 'personalization' => false], 'created_at' => '1900-01-01', 'page_url' => 'https://example.invalid/form', 'referrer_url' => '',
        'google_consent' => ['ad_user_data' => $grant ? 'denied' : 'granted'], 'receipt_id' => 'synthetic-browser-claim'];
}
function save_fixture($payload) { global $authority; return $authority->save($payload, '', 'fixture'); }
$grant = save_fixture(event_payload(1));
ok(!is_wp_error($grant) && strlen($grant['receipt_id']) === 64, 'server issues genuine namespaced acknowledged receipt');
ok(count($wpdb->rows) === 1 && $wpdb->rows[1]['created_at'] !== '1900-01-01', 'client time cannot age stored event into retention deletion');
ok($grant['google_consent']['ad_user_data'] === 'granted' && $grant['google_consent']['ad_personalization'] === 'denied', 'server mapping ignores contradictory Google flags and preserves independent personalization');
$again = save_fixture(event_payload(1));
ok($again['row_id'] === $grant['row_id'] && count($wpdb->rows) === 1, 'identical retry is idempotent');
ok(save_fixture(event_payload(1, false))->get_error_code() === 'event_conflict' && count($wpdb->rows) === 1, 'conflicting replay cannot append a later choice');
$reject = save_fixture(event_payload(2, false));
ok($grant['receipt_id'] !== $reject['receipt_id'] && $authority->resolve($grant)['google_consent']['ad_user_data'] === 'granted' && $authority->resolve($reject)['google_consent']['ad_user_data'] === 'denied', 'accept-to-reject retains both distinct immutable choices');
$grant2 = save_fixture(event_payload(3));
ok($authority->resolve($reject)['google_consent']['ad_user_data'] === 'denied' && $authority->resolve($grant2)['google_consent']['ad_user_data'] === 'granted', 'reject-to-accept does not upgrade prior rejected binding');
$cookie = $grant; $cookie['consent']['advertising'] = false; $cookie['receipt_id'] = 'forged';
ok($authority->resolve($cookie)['state'] === 'conflicting_cookie', 'contradictory unsigned cookie state cannot reuse a granting acknowledgement');
$cookie = $grant; $cookie['event_id'] = event_payload(2)['event_id'];
ok($authority->resolve($cookie)['state'] === 'conflicting_cookie', 'another interaction cannot reuse a prior signed acknowledgement');
ok($authority->resolve(null)['receipt_id'] === '' && $authority->resolve(['receipt_id' => $grant['receipt_id']])['state'] === 'unsigned_legacy', 'missing and unsigned legacy cookies never acquire authority');
foreach (['pending','failed'] as $state) ok($authority->resolve(['contract_version' => 1, 'audit_status' => $state])['state'] === $state, $state . ' audit remains negative');
$tampered = $grant; $tampered['audit_token'] .= 'a';
ok($authority->resolve($tampered)['state'] === 'tampered', 'modified proof signature blocks');
$old = $wpdb->rows[1]; $wpdb->rows[1]['method'] = 'changed';
ok($authority->resolve($grant)['state'] === 'tampered', 'signed acknowledgement detects mutated persisted row'); $wpdb->rows[1] = $old;
$stale = new SiteEngine_Consent_Authority(array_merge($config, ['policy_version' => 'fixture-new-policy']));
ok($stale->resolve($grant)['state'] === 'stale', 'policy change requires new acknowledged interaction without rewriting history');
$stale = new SiteEngine_Consent_Authority(array_merge($config, ['banner_version' => 'fixture-new-banner']));
ok($stale->resolve($grant)['state'] === 'stale', 'banner mismatch fails closed');
$body = base64_encode(wp_json_encode(['v' => 1, 'event_id' => $grant['event_id'], 'row_id' => 1, 'receipt_id' => $grant['receipt_id'], 'expires' => time() - 10, 'digest' => 'fixture']));
ok($authority->resolve(['audit_token' => $body . '.' . hash_hmac('sha256', $body, wp_salt('auth'))])['state'] === 'expired', 'expired validly signed proof blocks');
$duplicate = $old; $duplicate['id'] = 4; $wpdb->rows[4] = $duplicate;
ok($authority->resolve($grant)['state'] === 'ambiguous' && save_fixture(event_payload(1))->get_error_code() === 'ambiguous', 'duplicate rows fail closed without deduplication'); unset($wpdb->rows[4]);
unset($wpdb->rows[1]); ok($authority->resolve($grant)['state'] === 'missing_receipt', 'deleted or missing receipt stays unresolved'); $wpdb->rows[1] = $old;
$wpdb->fail_select = true;
ok($authority->resolve($grant)['state'] === 'unavailable' && save_fixture(event_payload(4))->get_error_code() === 'audit_unavailable', 'lookup failure cannot produce authority'); $wpdb->fail_select = false;
$wpdb->lock = false;
ok(save_fixture(event_payload(4))->get_error_code() === 'audit_busy', 'contending worker without lock cannot insert'); $wpdb->lock = true;
$wpdb->fail_insert = true;
ok(save_fixture(event_payload(4))->get_error_code() === 'audit_failed' && count($wpdb->rows) === 3, 'insert failure yields no acknowledgement or row'); $wpdb->fail_insert = false;
$wpdb->fail_readback = true;
ok(save_fixture(event_payload(4))->get_error_code() === 'audit_unavailable', 'failed readback cannot acknowledge a write'); $wpdb->fail_readback = false;
ok(!is_wp_error(save_fixture(event_payload(4))) && count($wpdb->rows) === 4, 'retry after readback failure recovers exact event without duplicate');
$invalid = event_payload(5); $invalid['consent']['advertising'] = 'false';
ok(save_fixture($invalid)->get_error_code() === 'invalid_consent', 'truthy strings cannot mint grants');
$invalid = event_payload(5); $invalid['policy_version'] = 'stale';
ok(save_fixture($invalid)->get_error_code() === 'stale', 'stale capture cannot append under current policy');
ok($wpdb->releases > 0, 'capture releases locks on success and error paths');

// Exercise actual plugin callback registration and its array data shapes.
$reflection = new ReflectionClass('SiteEngine_Consent'); $plugin = $reflection->newInstanceWithoutConstructor();
$property = $reflection->getProperty('config'); $property->setAccessible(true); $property->setValue($plugin, $config);
$register = $reflection->getMethod('hooks'); $register->setAccessible(true); $register->invoke($plugin);
ok(!isset($hooks['fluentform/before_insert_submission']) && isset($hooks['fluentform/insert_response_data'][PHP_INT_MAX]), 'mutation filter replaces discarded action return');
add_filter('fluentform/insert_response_data', function ($data, $id, $inputs) { $data['component_value'] = $inputs['fixture'] ?? 'default'; return $data; }, 10, 3);
$_COOKIE[SE_CONSENT_COOKIE_NAME] = wp_json_encode($grant);
$response = apply_filters('fluentform/insert_response_data', ['business_field' => 'untouched', 'se_consent_receipt_id' => $grant['receipt_id'], ' se_consent_receipt_id ' => 'forged', '_se_consent_server' => ['receipt_id' => 'forged'], 'se_google_ad_user_data' => 'denied'], 8, ['fixture' => 'transformed']);
ok($response['business_field'] === 'untouched' && $response['component_value'] === 'transformed' && $response['_se_consent_server']['state'] === 'verified' && $response['se_consent_receipt_id'] === $grant['receipt_id'] && !isset($response[' se_consent_receipt_id ']), 'real response callback preserves component/business data and replaces forged authority');
ok(SiteEngine_Consent_Authority::verify_stamp($response['_se_consent_server'], 8) && !SiteEngine_Consent_Authority::verify_stamp($response['_se_consent_server'], 9), 'stored stamp signature is bound to canonical form ID');
$forgedStamp = $response['_se_consent_server']; $forgedStamp['receipt_row_id'] = 999;
ok(!SiteEngine_Consent_Authority::verify_stamp($forgedStamp, 8), 'forged stored row binding fails signature verification');
$conflict = apply_filters('fluentform/insert_response_data', ['se_consent_receipt_id' => ''], 11, []);
ok($conflict['_se_consent_server']['state'] === 'conflicting_claim' && $conflict['se_consent_receipt_id'] === '', 'pending blank form echo cannot fall back to older signed granting cookie');
$conflict = apply_filters('fluentform/insert_response_data', [], 12, []);
ok($conflict['_se_consent_server']['state'] === 'conflicting_claim', 'missing receipt echo fails closed even when a previous cookie exists');
unset($_COOKIE[SE_CONSENT_COOKIE_NAME]);
$details = apply_filters('fluentform/submission_form_data', array_merge($response, ['se_consent_receipt_id' => 'forged']), 100, (object) ['id' => 8]);
ok($details['se_consent_receipt_id'] === $grant['receipt_id'], 'details/integrations reuse frozen capture even if cookie disappears');
$negative = apply_filters('fluentform/insert_response_data', ['se_consent_receipt_id' => $grant['receipt_id'], '_se_consent_server' => ['state' => 'verified', 'receipt_id' => $grant['receipt_id']], 'se_google_ad_user_data' => 'granted'], 9, []);
$negativeDetails = apply_filters('fluentform/submission_form_data', array_merge($negative, [' se_consent_receipt_id ' => $grant['receipt_id']]), 101, (object) ['id' => 9]);
ok($negative['_se_consent_server']['state'] === 'missing' && $negative['se_consent_receipt_id'] === '' && $negativeDetails['se_consent_receipt_id'] === '' && !isset($negativeDetails[' se_consent_receipt_id ']), 'missing-cookie response/details remove all trimmed fallback keys without fake identity');
$uncaptured = apply_filters('fluentform/submission_form_data', ['se_consent_receipt_id' => $grant['receipt_id']], 102, (object) ['id' => 10]);
ok($uncaptured['_se_consent_server']['state'] === 'not_captured' && $uncaptured['se_consent_receipt_id'] === '', 'alternate uncaptured details path stays negative');
echo "Authority/callback checks: $count passed. Synthetic storage; no live WordPress acceptance.\n";
require __DIR__ . '/fixtures/fluentforms-details.php';
$serializer = new ConsentFixture\SubmissionService();
$serializer->recordEntryDetails(101, 9, $negativeDetails);
$ids = array_filter(ConsentFixture\EntryDetails::$rows, function ($r) { return $r['field_name'] === 'se_consent_receipt_id'; });
ok(count($ids) === 0, 'actual Fluent Forms serializer omits negative blank receipt field and trimmed aliases');
$serializer->recordEntryDetails(100, 8, $details);
$ids = array_values(array_filter(ConsentFixture\EntryDetails::$rows, function ($r) { return $r['field_name'] === 'se_consent_receipt_id'; }));
ok(count($ids) === 1 && $ids[0]['field_value'] === $grant['receipt_id'], 'actual serializer persists genuine acknowledged flat identity once');
echo "Total authority/persistence checks: $count passed.\n";
function ajax_fixture($post) {
    global $plugin;
    $_POST = $post;
    try { $plugin->ajax_save_receipt(); } catch (Fixture_Json_Response $r) { return $r; }
    throw new RuntimeException('Missing AJAX response');
}
$validPost = ['nonce' => 'synthetic-valid-nonce', 'payload' => wp_json_encode(event_payload(6, false))];
ok(ajax_fixture(array_merge($validPost, ['nonce' => 'expired']))->status === 403, 'AJAX nonce boundary rejects before audit write (core nonce API doubled)');
$ajax = ajax_fixture($validPost);
ok($ajax->success && $ajax->data['google_consent']['ad_user_data'] === 'denied', 'actual AJAX callback returns signed server-mapped rejection after persisted readback');
ok(ajax_fixture(['nonce' => 'synthetic-valid-nonce', 'payload' => []])->status === 400, 'array-shaped AJAX payload fails without PHP warning');
ok(ajax_fixture(['nonce' => 'synthetic-valid-nonce', 'payload' => '{broken'])->status === 400, 'malformed JSON cannot create receipt');
ok(ajax_fixture(['nonce' => 'synthetic-valid-nonce', 'payload' => str_repeat('x', 65537)])->status === 400, 'oversized payload rejected before decode');
$fixture_transients['se_consent_rl_' . substr(hash('sha256', 'unknown'), 0, 16)] = 20;
// Empty fixture IP uses the retained rate-limit key literal 'unknown'.
$fixture_transients['se_consent_rl_unknown'] = 20;
ok(ajax_fixture($validPost)->status === 429, 'actual AJAX rate-limit branch returns 429 without acknowledgement');
echo "Total PHP checks: $count passed.\n";
if (isset($argv[1])) {
    $consent_cases = ['response' => $response, 'negative' => $negative, 'details' => ConsentFixture\EntryDetails::$rows, 'rows' => array_values($wpdb->rows)];
    $fixture_policy = $config['policy_version'];
    require __DIR__ . '/consumer-contract.php';
}
