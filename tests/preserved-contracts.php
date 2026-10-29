<?php
// Static preservation evidence, separate from runtime/integration acceptance.
function named_functions($source) {
    $tokens = token_get_all($source); $functions = [];
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $next = $i + 1;
        while (is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) $next++;
        if (!is_array($tokens[$next]) || $tokens[$next][0] !== T_STRING) continue;
        $name = $tokens[$next][1]; $text = ''; $depth = 0; $started = false;
        for ($j = $i; $j < count($tokens); $j++) {
            $token = $tokens[$j]; $text .= is_array($token) ? $token[1] : $token;
            if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) { $depth++; $started = true; }
            if ($token === '}' && --$depth === 0 && $started) break;
        }
        $functions[$name] = str_replace("\r\n", "\n", $text);
    }
    return $functions;
}
$baseline = stream_get_contents(STDIN);
if (!$baseline || strpos($baseline, 'final class SiteEngine_Consent') === false) { fwrite(STDERR, "Pipe retained baseline PHP from Git into this test.\n"); exit(2); }
$current = file_get_contents(dirname(__DIR__) . '/siteengine-consent.php');
$before = named_functions($baseline); $after = named_functions($current);
$names = ['default_config','configured_company_name','configured_privacy_url','has_configured_company_name','has_configured_privacy_url','resolve_privacy_url','build_fallback_url',
    'siteengine_consent_deactivate','maybe_upgrade','maybe_schedule_cleanup','run_cleanup',
    'register_admin_page','register_siteengine_module','siteengine_module_status','admin_page_url','render_admin_page','register_rest_routes','rest_health','cli_export','build_export_rows','stream_csv_rows','normalize_export_date','normalize_google_value'];
foreach ($names as $name) {
    if (!isset($before[$name], $after[$name]) || $before[$name] !== $after[$name]) throw new RuntimeException('Preserved contract changed: ' . $name);
    echo 'PASS unchanged baseline body: ' . $name . "\n";
}
if (strpos($current, "define('SE_CONSENT_POLICY_VERSION', '2026-05-08-v1')") === false) throw new RuntimeException('Policy default changed');
if (strpos($after['ajax_save_receipt'], "check_ajax_referer('se_consent_save_receipt', 'nonce')") === false) throw new RuntimeException('AJAX nonce boundary changed');
echo 'Preservation checks: ' . (count($names) + 2) . " passed (23 unchanged function bodies, policy default, capture nonce boundary). Static evidence only.\n";

foreach (['siteengine_consent_activate','create_or_update_tables'] as $name) {
    preg_match('/\\$sql = "CREATE TABLE .*?";/s', $before[$name], $old_sql);
    preg_match('/\\$sql = "CREATE TABLE .*?";/s', $after[$name], $new_sql);
    if (!$old_sql || $old_sql !== $new_sql) throw new RuntimeException('Original receipts schema changed');
    echo "PASS unchanged original receipts DDL: {$name}\n";
}
$config_without_extension = preg_replace('/^.*continuityScope.*\n/m', '', $after['print_config']);
if ($config_without_extension !== $before['print_config']) throw new RuntimeException('Config changes exceed new continuity storage scope');
echo "PASS print_config adds only site-scoped continuity name\n";
