<?php
/** Changed-behavior fixtures only. No installed WP, SQL connection or HTTP. */
define('CONTINUITY_FIXTURE_ONLY', true);
require __DIR__ . '/authority.php';
$admin = true; $nonce_ok = true; $nonce_calls = 0; $scheduled = true; $wp_version = '6.5.5';
function current_user_can($cap) { return $cap === 'manage_options' && $GLOBALS['admin']; }
function wp_die($message) { throw new RuntimeException($message); }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_textarea($text) { return esc_html($text); }
function check_admin_referer($action) { $GLOBALS['nonce_calls']++; if ($action !== 'se_consent_support' || !$GLOBALS['nonce_ok']) wp_die('Invalid nonce'); }
function wp_nonce_field($action) { echo '<input name="_wpnonce" value="synthetic-only">'; }
function wp_next_scheduled($name) { return $GLOBALS['scheduled'] ? 123456 : false; }

final class Diagnostics_Metadata_DB {
    public $prefix='synthetic_', $last_error='', $queries=[], $tables=[], $unavailable='', $throw=false, $suppressed=false, $primary='0', $auto='1';
    public function __construct() {
        $source = file_get_contents(dirname(__DIR__) . '/siteengine-consent.php');
        preg_match('/\$sql = "(CREATE TABLE \{\$table\} .*?)";/s', $source, $match);
        $ddls = array_merge([str_replace('{$table}', 'synthetic_se_consent_receipts', $match[1])], SiteEngine_Consent_Continuity::schema('synthetic_', ''));
        foreach ($ddls as $ddl) {
            preg_match('/CREATE TABLE (\S+)/', $ddl, $name); $table = $name[1];
            $this->tables[$table] = ['engine'=>'InnoDB','columns'=>[],'indexes'=>[]];
            foreach (explode("\n", $ddl) as $line) {
                if (preg_match('/^\s+(\w+) ((?:bigint\(20\) unsigned)|(?:varchar\(64\))|(?:char\(64\))|longtext|text|datetime) (.*)/', $line, $m)) {
                    $this->tables[$table]['columns'][] = ['Field'=>$m[1],'Type'=>$m[2], 'Null'=>strpos($m[3], 'NOT NULL') !== false ? 'NO':'YES', 'Extra'=>strpos($m[3], 'AUTO_INCREMENT') !== false ? 'auto_increment':''];
                }
                if (preg_match('/^\s+(PRIMARY KEY|UNIQUE KEY \w+|KEY \w+)\s+\(([^)]+)\)/', $line, $m)) {
                    $parts = explode(' ', $m[1]); $key = $parts[0] === 'PRIMARY' ? 'PRIMARY' : end($parts);
                    foreach (explode(',', $m[2]) as $i=>$col) $this->tables[$table]['indexes'][] = ['Key_name'=>$key,'Non_unique'=>$parts[0] === 'KEY' ? 1:0,'Column_name'=>trim($col),'Seq_in_index'=>$i+1,'Sub_part'=>null];
                }
            }
        }
    }
    public function prepare($sql, ...$args) { foreach ($args as $arg) $sql = preg_replace('/%s/', "'" . $arg . "'", $sql, 1); return $sql; }
    public function suppress_errors($state=true) { $old=$this->suppressed; $this->suppressed=$state; return $old; }
    private function observe($sql) {
        if (!preg_match('/^(SELECT ENGINE FROM information_schema\.TABLES|SELECT @@(?:read_only|autocommit)$|SHOW FULL COLUMNS FROM|SHOW INDEX FROM)/', $sql)) throw new RuntimeException('Forbidden nonmetadata read or write');
        $this->queries[]=$sql; $this->last_error='';
        if ($this->throw) throw new RuntimeException('SENTINEL_SECRET_SQL_FAILURE');
        if ($this->unavailable !== '' && strpos($sql, $this->unavailable) !== false) { $this->last_error='SENTINEL_SECRET_SQL_FAILURE'; return false; } return true;
    }
    public function get_var($sql) {
        if (!$this->observe($sql)) return null;
        if ($sql === 'SELECT @@read_only') return $this->primary;
        if ($sql === 'SELECT @@autocommit') return $this->auto;
        preg_match("/TABLE_NAME = '([^']+)'/", $sql, $m);
        return $this->tables[$m[1]]['engine'] ?? null;
    }
    public function get_results($sql, $format) {
        if (!$this->observe($sql)) return null;
        preg_match('/`([^`]+)`/', $sql, $m);
        return $this->tables[$m[1]][strpos($sql,'COLUMNS') !== false ? 'columns':'indexes'];
    }
}
$wpdb = new Diagnostics_Metadata_DB(); $original_tables = $wpdb->tables;
$facts = ['cookie_days'=>390,'retention_days'=>730,'rate_limit_per_min'=>20,'policy_version'=>'2026-05-08-v1',
    'banner_version'=>'0.6.1-rc.2-fixture','company_configured'=>true,'privacy_configured'=>true,'privacy_https'=>true,
    'company_source'=>'base','privacy_source'=>'explicit_constant','debug'=>false,
    'company_name'=>'SENTINEL_PERSON','privacy_url'=>'https://user:SENTINEL_PASSWORD@example.invalid/path?token=SECRET',
    'audit_token'=>'SENTINEL_SIGNING','continuity_key'=>'SENTINEL_CONTINUITY','arbitrary'=>'SENTINEL_RAW_RECORD'];
$_COOKIE=['se_consent'=>'SENTINEL_COOKIE']; $_POST=['submission'=>'SENTINEL_SUBMISSION'];
$count=0;
function diagnostic_report() { global $facts; return SiteEngine_Consent_Diagnostics::collect($facts); }
function diagnostic_refused($fn, $text) { try { $fn(); } catch (RuntimeException $e) { return strpos($e->getMessage(),$text)!==false; } return false; }
$r=diagnostic_report();
foreach (['storage_receipts','storage_events','storage_chains','connection','consumer_api','configuration','cleanup_schedule'] as $check) ok($r['checks'][$check]['status']==='verified', 'actual DDL fixture passes '.$check);
ok($r['checks']['end_to_end']['status']==='untested' && strpos($r['summary'],'untested')!==false,'prerequisites never certify end-to-end operation');
$encoded=json_encode($r); ok(strpos($encoded,'SENTINEL')===false && strpos($encoded,'user:')===false && strpos($encoded,'SECRET')===false,'fixed allowlist excludes secret config, records, cookies and request data');
ok(!isset($r['configuration']['policy_version']) && $r['configuration']['policy_version_sha256']===hash('sha256',$facts['policy_version']),'only nonsecret identity fingerprint emitted');
foreach (['receipts','events','chains'] as $kind) {
    $table='synthetic_se_consent_'.($kind==='events'?'event_links':$kind);
    unset($wpdb->tables[$table]); ok(diagnostic_report()['checks']['storage_'.$kind]['status']==='failed','missing '.$kind.' table fails'); $wpdb->tables=$original_tables;
    $wpdb->tables[$table]['engine']='MyISAM'; ok(diagnostic_report()['checks']['storage_'.$kind]['status']==='failed','incompatible '.$kind.' engine fails'); $wpdb->tables=$original_tables;
    array_pop($wpdb->tables[$table]['columns']); ok(diagnostic_report()['checks']['storage_'.$kind]['status']==='failed','missing '.$kind.' column fails'); $wpdb->tables=$original_tables;
    array_pop($wpdb->tables[$table]['indexes']); ok(diagnostic_report()['checks']['storage_'.$kind]['status']==='failed','missing '.$kind.' index fails'); $wpdb->tables=$original_tables;
}
$wpdb->tables['synthetic_se_consent_receipts']['columns'][0]['Extra']=''; ok(diagnostic_report()['checks']['storage_receipts']['status']==='failed','receipt auto-increment required'); $wpdb->tables=$original_tables;
$wpdb->tables['synthetic_se_consent_event_links']['columns'][0]['Null']='YES'; ok(diagnostic_report()['checks']['storage_events']['status']==='failed','nullable identity fails'); $wpdb->tables=$original_tables;
$wpdb->tables['synthetic_se_consent_chains']['columns'][1]['Type']='varchar(64)'; ok(diagnostic_report()['checks']['storage_chains']['status']==='failed','wrong revision type fails'); $wpdb->tables=$original_tables;
$wpdb->tables['synthetic_se_consent_receipts']['indexes'][1]['Non_unique']=0; ok(diagnostic_report()['checks']['storage_receipts']['status']==='failed','unique legacy receipt_id fails'); $wpdb->tables=$original_tables;
$wpdb->tables['synthetic_se_consent_event_links']['indexes'][2]['Column_name']='revision'; ok(diagnostic_report()['checks']['storage_events']['status']==='failed','incorrect immutable compound index order fails'); $wpdb->tables=$original_tables;
$wpdb->unavailable='information_schema'; $r=diagnostic_report(); ok($r['checks']['storage_receipts']['status']==='untested' && strpos($r['summary'],'incomplete')!==false,'metadata permission failure is incomplete, not pass/missing');
ok(strpos(json_encode($r),'SENTINEL')===false,'database error redacted'); $wpdb->unavailable='';
$wpdb->throw=true; $r=diagnostic_report(); ok($r['checks']['metadata_read']['status']==='untested' && !$wpdb->suppressed,'exception restores database error mode and is untested'); $wpdb->throw=false;
$wpdb->primary='1'; ok(diagnostic_report()['checks']['connection']['status']==='failed','read-only connection flagged'); $wpdb->primary='0';
$wpdb->auto='0'; ok(diagnostic_report()['checks']['connection']['status']==='failed','nonautocommit connection flagged'); $wpdb->auto='1';
$facts['privacy_configured']=false; ok(diagnostic_report()['checks']['configuration']['status']==='failed','missing explicit/shared configuration flagged'); $facts['privacy_configured']=true;
$facts['cookie_days']=-1; ok(diagnostic_report()['checks']['configuration']['status']==='failed','invalid effective lifetime flagged'); $facts['cookie_days']=390;
$facts['policy_version']=''; ok(diagnostic_report()['checks']['configuration']['status']==='failed','empty policy identity flagged'); $facts['policy_version']='2026-05-08-v1';
$wpdb->unavailable='SHOW INDEX'; ok(diagnostic_report()['checks']['storage_events']['status']==='untested','unreadable indexes are untested'); $wpdb->unavailable='';
$wpdb->unavailable='SHOW FULL'; ok(diagnostic_report()['checks']['storage_chains']['status']==='untested','unreadable columns are untested'); $wpdb->unavailable='';
$wpdb->tables['synthetic_se_consent_receipts']['indexes'][]=['Key_name'=>'unexpected','Non_unique'=>0,'Column_name'=>'method','Seq_in_index'=>1,'Sub_part'=>null];
ok(diagnostic_report()['checks']['storage_receipts']['status']==='failed','additional uniqueness restriction is not certified'); $wpdb->tables=$original_tables;
$scheduled=false; ok(diagnostic_report()['checks']['cleanup_schedule']['status']==='failed','missing schedule reported without running cleanup'); $scheduled=true;
$before=count($wpdb->queries); $admin=false;
ok(diagnostic_refused(function(){diagnostic_report();},'permissions') && count($wpdb->queries)===$before,'nonadministrator report refuses before metadata reads');
ok(diagnostic_refused(function()use($facts){SiteEngine_Consent_Diagnostics::render($facts);},'permissions'),'nonadministrator UI refuses');
$instance=(new ReflectionClass('SiteEngine_Consent'))->newInstanceWithoutConstructor();
ok(diagnostic_refused(function()use($instance){$instance->render_admin_page();},'permissions'),'existing admin route enforces capability'); $admin=true;
$_SERVER['REQUEST_METHOD']='POST'; $_POST=['se_consent_support'=>'1']; $nonce_ok=false; $before=count($wpdb->queries);
ok(diagnostic_refused(function()use($facts){SiteEngine_Consent_Diagnostics::render($facts);},'Invalid nonce') && count($wpdb->queries)===$before,'invalid nonce refuses before report collection'); $nonce_ok=true;
ob_start(); SiteEngine_Consent_Diagnostics::render($facts); $html=ob_get_clean();
ok(strpos($html,'textarea')!==false && strpos($html,'SENTINEL')===false && $nonce_calls===2,'valid administrator nonce generates redacted copyable report');
$wp_version='</textarea><script>alert(1)</script>'; ob_start(); SiteEngine_Consent_Diagnostics::render($facts); $html=ob_get_clean();
ok(strpos($html,'<script>')===false && strpos($html,'&quot;versions&quot;')!==false,'report textarea is escaped and unexpected version strings cannot inject markup'); $wp_version='6.5.5';
$_SERVER['REQUEST_METHOD']='GET'; $_POST=[];
$property=new ReflectionProperty($instance,'config'); $property->setAccessible(true); $property->setValue($instance,$facts);
$_GET=['view'=>'diagnostics']; ob_start(); $instance->render_admin_page(); $html=ob_get_clean();
ok(strpos($html,'Consent deployment diagnostics')!==false && strpos($html,'<textarea')===false,'existing Base/standalone admin callback routes to metadata-only view without receipt viewer');
$legend=json_encode(SiteEngine_Consent_Diagnostics::explanations());
foreach (['eligible','withdrawn','denied','invalid_evidence','storage_unavailable','missing_or_ambiguous_receipt','continuity_unavailable','expired','policy_mismatch','banner_mismatch','conflicting_claim','not_captured','audit_failed','pending','superseded'] as $code) ok(strpos($legend,$code)!==false,'explanation includes '.$code);
ok($wpdb->tables===$original_tables,'all metadata and audit-storage fixtures unchanged');
echo "Diagnostics checks: {$count} passed; isolated metadata/WP boundaries, no installed execution.\n";
