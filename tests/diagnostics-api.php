<?php
/** Provider absence/signature failure fixtures in independent PHP processes. */
define('ABSPATH', __DIR__ . '/'); define('ARRAY_A', 'ARRAY_A'); define('SE_CONSENT_VERSION', '0.6.2-rc.1');
function current_user_can($cap) { return $cap === 'manage_options'; }
function wp_die($text) { throw new RuntimeException($text); }
function wp_next_scheduled($hook) { return false; }
$mode = $argv[1] ?? '';
if ($mode === 'wrong') {
    class SiteEngine_Consent_Authority { public static function current_eligibility(array $stamp): array { return []; } }
} elseif ($mode === 'real') {
    require dirname(__DIR__) . '/includes/class-siteengine-consent-continuity.php';
    require dirname(__DIR__) . '/includes/class-siteengine-consent-authority.php';
} elseif ($mode !== 'missing') { throw new RuntimeException('Expected missing|wrong|real'); }
require dirname(__DIR__) . '/includes/class-siteengine-consent-diagnostics.php';
$wpdb = new class {
    public $prefix='synthetic_', $last_error='';
    public function suppress_errors($v) { return false; }
    public function prepare($s,...$args) { return $s; }
    public function get_var($s) { return null; }
};
$result = SiteEngine_Consent_Diagnostics::collect([]);
$expected = $mode === 'real' ? 'verified' : 'failed';
if ($result['checks']['consumer_api']['status'] !== $expected) throw new RuntimeException('API availability incorrect');
echo "PASS API {$mode}: {$expected}; signature only, no semantic enforcement claim\n";
