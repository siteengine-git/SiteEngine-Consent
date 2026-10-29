<?php
// Actual authority algorithm; only the WPDB adapter is replaced with isolated file storage.
define('ABSPATH', __DIR__ . '/'); define('ARRAY_A', 'ARRAY_A'); define('DAY_IN_SECONDS', 86400);
set_error_handler(function ($severity, $message) { throw new ErrorException($message, 0, $severity); });
function wp_json_encode($x) { return json_encode($x, JSON_UNESCAPED_SLASHES); }
function wp_salt($scheme) { return 'synthetic-concurrency-fixture-' . $scheme; }
function sanitize_text_field($s) { return trim(strip_tags($s)); }
function esc_url_raw($s) { return $s; }
class WP_Error {
    private $code;
    public function __construct($code, $message, $data = []) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
function home_url($path = '') { return 'https://example.invalid' . $path; }
function is_wp_error($value) { return $value instanceof WP_Error; }
require __DIR__ . '/fixtures/continuity-db.php';
class Concurrent_Receipt_Store extends Receipt_Fixture_DB {
    public $wait_ms = 0;
    private $root, $held;
    public function __construct($root) { $this->root = $root; }
    public function get_var($query) {
        if (strpos($query[0], 'GET_LOCK') !== false) {
            // Fixture serializes the tiny synthetic database. Production uses a per-chain MySQL named lock.
            $this->held = fopen($this->root . '/storage.lock', 'c+'); $start = microtime(true);
            if (!flock($this->held, LOCK_EX)) throw new RuntimeException('Fixture lock unavailable');
            $this->wait_ms = (int) ((microtime(true) - $start) * 1000);
            $state = is_file($this->root . '/rows.json') ? json_decode(file_get_contents($this->root . '/rows.json'), true) : null;
            if ($state) { $this->rows = $state['rows']; $this->heads = $state['heads']; $this->links = $state['links']; $this->insert_id = $state['insert_id']; }
            return '1';
        }
        if (strpos($query[0], 'RELEASE_LOCK') !== false) {
            flock($this->held, LOCK_UN); fclose($this->held); return '1';
        }
        return parent::get_var($query);
    }
    public function query($query) {
        $result = parent::query($query);
        if ($query === 'COMMIT') file_put_contents($this->root . '/rows.json', wp_json_encode(['rows' => $this->rows, 'heads' => $this->heads, 'links' => $this->links, 'insert_id' => $this->insert_id]));
        return $result;
    }
    public function insert($table, $row) {
        if (strpos($table, 'se_consent_receipts') !== false) usleep(350000);
        return parent::insert($table, $row);
    }
}
require dirname(__DIR__) . '/includes/class-siteengine-consent-continuity.php';
require dirname(__DIR__) . '/includes/class-siteengine-consent-authority.php';
[$program, $root, $worker, $number, $choice] = $argv;
$wpdb = new Concurrent_Receipt_Store($root);
$authority = new SiteEngine_Consent_Authority(['policy_version' => 'fixture-policy', 'banner_version' => 'fixture-banner', 'cookie_days' => 390]);
file_put_contents($root . '/ready-' . (int) $worker, (string) getmypid());
$deadline = microtime(true) + 10;
while (!is_file($root . '/start')) { if (microtime(true) > $deadline) throw new RuntimeException('Fixture barrier timeout'); usleep(10000); }
if ($choice === 'delayed-grant') usleep(700000);
$result = $authority->save(['continuity_key' => hash('sha256', 'shared-browser'), 'continuity_token' => '', 'event_id' => sprintf('00000000-0000-4000-8000-%012d', (int) $number), 'method' => 'save_preferences', 'policy_version' => 'fixture-policy', 'banner_version' => 'fixture-banner', 'page_url' => 'https://example.invalid', 'referrer_url' => '',
    'consent' => ['necessary' => true, 'analytics' => false, 'advertising' => in_array($choice, ['grant','delayed-grant'], true), 'personalization' => false]], '', 'fixture');
$output = ['pid' => getmypid(), 'wait_ms' => $wpdb->wait_ms];
if ($result instanceof WP_Error) $output['error'] = $result->get_error_code();
else $output += ['row_id' => $result['row_id'], 'receipt_id' => $result['receipt_id'], 'state' => $authority->resolve($result)['state']];
echo wp_json_encode($output);
