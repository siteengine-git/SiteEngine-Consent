<?php
/** Preservation checks against the retained pre-Milestone source, not a WP upgrade claim. */
$baseline = file_get_contents($argv[1] ?? '');
$current = file_get_contents(dirname(__DIR__) . '/siteengine-consent.php');
function diagnostic_functions(string $source): array {
    $tokens=token_get_all($source); $functions=[];
    for ($i=0;$i<count($tokens);$i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j=$i+1; while (is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE) $j++;
        if (!is_array($tokens[$j]) || $tokens[$j][0]!==T_STRING) continue;
        $name=$tokens[$j][1]; $body=''; $depth=0; $started=false;
        for ($k=$i;$k<count($tokens);$k++) {
            $token=$tokens[$k]; $body.=is_array($token)?$token[1]:$token;
            if ($token==='{' || (is_array($token) && in_array($token[0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES],true))) { $depth++;$started=true; }
            if ($token==='}' && --$depth===0 && $started) break;
        }
        $functions[$name]=str_replace("\r\n","\n",$body);
    }
    return $functions;
}
$old=diagnostic_functions($baseline);$new=diagnostic_functions($current);$count=0;
foreach ($old as $name=>$body) if ($name!=='render_admin_page') {
    if (($new[$name]??null)!==$body) throw new RuntimeException('Preservation failure: '.$name);
    $count++;
}
foreach (['includes/class-siteengine-consent-authority.php'=>'6d49bfe9006fc108d198dea7309b6dae88e123466d2e0767a2aa74391e4488c9',
    'includes/class-siteengine-consent-continuity.php'=>'660c83315e50ae6f159ddcb3f97f28b1f960c726bb5c9be62a0cdccd2507fe3d',
    'uninstall.php'=>'65e849d8f22f98a00c50163bb4e2a645065b47371b0c40bed47ae0296648150b'] as $file=>$hash) {
    if (hash_file('sha256',dirname(__DIR__).'/'.$file)!==$hash) throw new RuntimeException('Immutable implementation changed: '.$file);
    $count++;
}
if (strpos($current,"define('SE_CONSENT_BANNER_VERSION', '0.6.1-rc.2')")===false
    || strpos($current,"define('SE_CONSENT_POLICY_VERSION', '2026-05-08-v1')")===false) throw new RuntimeException('Policy/banner identity changed');
$count++;
echo "Upgrade preservation: {$count} checks passed; all preexisting named bodies except admin rendering preserved, immutable source hashes and banner/policy defaults unchanged. Installed upgrade NOT RUN.\n";
