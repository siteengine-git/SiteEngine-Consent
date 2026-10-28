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
class Concurrent_Receipt_Store {
    public $prefix = 'fixture_'; public $last_error = ''; public $insert_id = 0; public $wait_ms = 0;
    private $root; private $held;
    public function __construct($root) { $this->root = $root; }
    public function prepare($sql, ...$args) { return [$sql, $args]; }
    public function get_var($query) {
        if (strpos($query[0], 'GET_LOCK') !== false) {
            $this->held = fopen($this->root . '/' . hash('sha256', $query[1][0]) . '.lock', 'c+');
            $start = microtime(true);
            while (!flock($this->held, LOCK_EX | LOCK_NB)) {
                if (microtime(true) - $start > 3) { fclose($this->held); $this->held = null; return null; }
                usleep(10000);
            }
            $this->wait_ms = (int) ((microtime(true) - $start) * 1000);
            return '1';
        }
        if (strpos($query[0], 'RELEASE_LOCK') !== false) {
            if (!$this->held) throw new RuntimeException('Releasing absent lock');
            flock($this->held, LOCK_UN); fclose($this->held); $this->held = null; return '1';
        }
        throw new RuntimeException('Unexpected query');
    }
    private function store($callback, $write = false) {
        $handle = fopen($this->root . '/rows.json', 'c+');
        try {
            if (!flock($handle, LOCK_EX)) throw new RuntimeException('Fixture storage unavailable');
            $rows = json_decode(stream_get_contents($handle), true) ?: [];
            $result = $callback($rows);
            if ($write) { rewind($handle); ftruncate($handle, 0); fwrite($handle, wp_json_encode($rows)); fflush($handle); }
            return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }
    public function get_results($query, $format) {
        return $this->store(function ($rows) use ($query) { return array_slice(array_values(array_filter($rows, function ($r) use ($query) { return $r['receipt_id'] === $query[1][0]; })), 0, 2); });
    }
    public function get_row($query, $format) { return $this->store(function ($rows) use ($query) { return $rows[$query[1][0] - 1] ?? null; }); }
    public function insert($table, $row) {
        if (!$this->held) throw new RuntimeException('Authority wrote before acquiring event lock');
        // Hold the critical section long enough for the barrier-released second worker to contend.
        usleep(350000);
        return $this->store(function (&$rows) use ($row) { $this->insert_id = count($rows) + 1; $row['id'] = $this->insert_id; $rows[] = $row; return 1; }, true);
    }
}
require dirname(__DIR__) . '/includes/class-siteengine-consent-authority.php';
[$program, $root, $worker, $number, $choice] = $argv;
$wpdb = new Concurrent_Receipt_Store($root);
$authority = new SiteEngine_Consent_Authority(['policy_version' => 'fixture-policy', 'banner_version' => 'fixture-banner', 'cookie_days' => 390]);
file_put_contents($root . '/ready-' . (int) $worker, (string) getmypid());
$deadline = microtime(true) + 10;
while (!is_file($root . '/start')) { if (microtime(true) > $deadline) throw new RuntimeException('Fixture barrier timeout'); usleep(10000); }
$result = $authority->save(['event_id' => sprintf('00000000-0000-4000-8000-%012d', (int) $number), 'method' => 'save_preferences', 'policy_version' => 'fixture-policy', 'banner_version' => 'fixture-banner', 'page_url' => 'https://example.invalid', 'referrer_url' => '',
    'consent' => ['necessary' => true, 'analytics' => false, 'advertising' => $choice === 'grant', 'personalization' => false]], '', 'fixture');
$output = ['pid' => getmypid(), 'wait_ms' => $wpdb->wait_ms];
if ($result instanceof WP_Error) $output['error'] = $result->get_error_code();
else $output += ['row_id' => $result['row_id'], 'receipt_id' => $result['receipt_id'], 'state' => $authority->resolve($result)['state']];
echo wp_json_encode($output);
