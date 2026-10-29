<?php
define('CONTINUITY_FIXTURE_ONLY', true);
require __DIR__ . '/authority.php';
function chain_event($n, $grant, $ack = null, $key = 'browser-A') {
    $p = event_payload($n, $grant); $p['continuity_key'] = hash('sha256', $key);
    $p['continuity_token'] = $ack['continuity']['token'] ?? ''; return $p;
}
function evidence($ack) { global $authority; return SiteEngine_Consent_Authority::seal_stamp($authority->resolve($ack), 7); }
function eligibility($stamp) { global $config; return SiteEngine_Consent_Authority::current_eligibility($stamp, 7, $config['policy_version']); }
$a = save_fixture(chain_event(101, true)); $stamp = evidence($a); $snapshot = $wpdb->rows;
ok(eligibility($stamp)['allowed'] === true, 'grant and authenticated submission initially eligible');
$reject_payload = chain_event(102, false, $a); $reject = save_fixture($reject_payload);
ok(eligibility($stamp)['reason'] === 'withdrawn' && !eligibility($stamp)['allowed'], 'grant -> submission -> withdrawal invalidates submission without browser cookie');
ok($authority->resolve($a)['state'] === 'withdrawn', 'old granting browser acknowledgement cannot produce a new verified stamp');
ok($wpdb->rows[1] === $snapshot[1] && SiteEngine_Consent_Authority::verify_stamp($stamp, 7), 'withdrawal preserves original receipt bytes and historical stamp signature');
$regrant = save_fixture(chain_event(103, true, $reject)); $new_stamp = evidence($regrant);
ok(eligibility($new_stamp)['allowed'] && !eligibility($stamp)['allowed'], 'new grant allows new evidence without reviving withdrawn submission');
ok(SiteEngine_Consent_Authority::current_eligibility($new_stamp, 8, $config['policy_version'])['allowed'] === false, 'canonical form mismatch cannot gain eligibility');
$before = count($wpdb->rows);
$old_grant = save_fixture(chain_event(104, true, $a));
ok(is_wp_error($old_grant) && $old_grant->get_error_code() === 'continuity_stale' && count($wpdb->rows) === $before, 'delayed old grant cannot overwrite acknowledged withdrawal or regrant');
$old_retry = save_fixture(chain_event(101, true));
ok(is_wp_error($old_retry) && $old_retry->get_error_code() === 'superseded', 'superseded identical grant retry cannot restore an old acknowledgement');
$retry = save_fixture(chain_event(103, true, $reject));
ok($retry['row_id'] === $regrant['row_id'] && count($wpdb->rows) === $before, 'identical current retry retains immutable event and revision');
$conflict = save_fixture(chain_event(103, false, $reject));
ok($conflict->get_error_code() === 'event_conflict' && count($wpdb->rows) === $before, 'conflicting same-event reuse cannot append or change authority');
$stale_reject = save_fixture(chain_event(105, false, $a));
ok(!is_wp_error($stale_reject) && !eligibility($new_stamp)['allowed'], 'concurrent stale complete rejection can withdraw the latest grant');
$other = save_fixture(chain_event(106, true, null, 'browser-B')); $other_stamp = evidence($other);
ok(eligibility($other_stamp)['allowed'] && !eligibility($new_stamp)['allowed'], 'independent browser chain remains eligible');
$forged = chain_event(107, false, $other); $forged['continuity_key'] = hash('sha256', 'attacker');
ok(save_fixture($forged)->get_error_code() === 'continuity_invalid' && eligibility($other_stamp)['allowed'], 'another browser token cannot modify a target chain');
$missing = chain_event(108, true); unset($missing['continuity_key']);
ok(save_fixture($missing)->get_error_code() === 'continuity_missing', 'missing bearer key cannot create authority');
$forged_stamp = $other_stamp; $forged_stamp['chain_id'] = str_repeat('0', 64);
ok(!eligibility($forged_stamp)['allowed'], 'forged signed continuity extension fails historical authentication');
$legacy = $other_stamp; foreach (['chain_id','event_revision','continuity_version','continuity_digest'] as $k) unset($legacy[$k]);
$legacy = SiteEngine_Consent_Authority::seal_stamp($legacy, 7);
ok(eligibility($legacy)['reason'] === 'continuity_unavailable' && !eligibility($legacy)['available'], 'signed historical v1 evidence without continuity remains unresolved');
ok(!eligibility([])['allowed'] && !eligibility([])['available'], 'missing stamp never means allowed');
$wpdb->fail_select = true;
ok(!eligibility($other_stamp)['allowed'] && !eligibility($other_stamp)['available'], 'read failure fails closed'); $wpdb->fail_select = false;
$state = [$wpdb->rows, $wpdb->links, $wpdb->heads];
$wpdb->fail_head = true; $failed = save_fixture(chain_event(109, false, $other, 'browser-B')); $wpdb->fail_head = false;
ok($failed->get_error_code() === 'audit_failed' && [$wpdb->rows,$wpdb->links,$wpdb->heads] === $state && eligibility($other_stamp)['allowed'], 'failed withdrawal write rolls back and does not claim server success');
$wpdb->fail_commit = true; $failed = save_fixture(chain_event(110, false, $other, 'browser-B')); $wpdb->fail_commit = false;
ok(is_wp_error($failed) && [$wpdb->rows,$wpdb->links,$wpdb->heads] === $state, 'failed transaction commit creates no acknowledged authority in isolated rollback model');
$wpdb->engine = 'MyISAM';
$engine_error = save_fixture(chain_event(111, false, $other, 'browser-B'));
ok($engine_error->get_error_code() === 'transaction_storage_unavailable' /* actual */, 'nontransactional engine cannot mint continuity authority: ' . $engine_error->get_error_code()); $wpdb->engine = 'InnoDB';
$head_key = $other_stamp['chain_id']; $original = $wpdb->heads[$head_key]; $wpdb->heads[$head_key]['advertising_barrier'] = 0;
// The original barrier is already zero: alter revision as well to test integrity.
$wpdb->heads[$head_key]['revision']++;
ok(!eligibility($other_stamp)['available'] && !eligibility($other_stamp)['allowed'], 'tampered head cannot produce permission'); $wpdb->heads[$head_key] = $original;
$duplicate = $wpdb->rows[$other['row_id']]; $duplicate['id'] = 999; $wpdb->rows[999] = $duplicate;
ok(eligibility($other_stamp)['reason'] === 'missing_or_ambiguous_receipt', 'ambiguous original receipt identity stays blocked without repair'); unset($wpdb->rows[999]);
ok(!SiteEngine_Consent_Authority::current_eligibility($other_stamp, 7, 'new-policy')['allowed'], 'required policy change blocks historical eligibility');
$original = $wpdb->heads[$head_key]; $expired = $original; $expired['expires_at'] = time() - 1;
$sign = new ReflectionMethod('SiteEngine_Consent_Continuity', 'mac'); $sign->setAccessible(true); $expired['integrity'] = $sign->invoke(null, $expired);
$wpdb->heads[$head_key] = $expired;
ok(eligibility($other_stamp)['reason'] === 'expired' && !eligibility($other_stamp)['allowed'], 'expired current preference cannot authorize export even with intact historical evidence');
$wpdb->heads[$head_key] = $original;
$saved_head = $wpdb->heads[$head_key]; unset($wpdb->heads[$head_key]);
ok(!eligibility($other_stamp)['available'] && !eligibility($other_stamp)['allowed'], 'missing chain head cannot produce permission');
$before_missing = count($wpdb->rows);
$reset = save_fixture(chain_event(112, true, null, 'browser-B'));
ok($reset->get_error_code() === 'continuity_unavailable' && count($wpdb->rows) === $before_missing, 'retained event links forbid recreating a missing head with reset withdrawal barriers');
$wpdb->heads[$head_key] = $saved_head;
$writes = count($wpdb->queries); eligibility($other_stamp);
ok(count($wpdb->queries) === $writes, 'current eligibility executes no transaction or mutation');
$response = $authority->stamp(['business' => 'unchanged', 'se_consent_receipt_id' => 'forged', '_se_consent_server' => ['state' => 'verified']], $new_stamp);
ok($response['business'] === 'unchanged' && $response['_se_consent_server'] === $new_stamp, 'extended stamp preserves unrelated form fields and replaces client authority');

// Run the actual reviewed consumer code, not a fabricated permissive verifier.
$consumer_path = $argv[1] ?? '';
if (!is_file($consumer_path) || hash_file('sha256', $consumer_path) !== '057c59f58bb55905b2b142339626abf1485dbdb9e5702e0f59712d7fca056798') throw new RuntimeException('Supply reviewed Ads consumer source with pinned hash');
require $consumer_path;
class Reviewed_Consumer_DB {
    public $prefix = 'fixture_', $last_error = '', $stamp;
    public function prepare($sql, ...$args) { return [$sql, $args]; }
    public function get_col($sql) { return strpos($sql, 'receipts') !== false ? SiteEngine_Ads_Bridge_Consent::RECEIPT_COLUMNS : ['id','form_id','response']; }
    public function get_row($q, $format) { return ['id' => 1, 'form_id' => 7, 'response' => wp_json_encode(['_se_consent_server' => $this->stamp])]; }
    public function get_results($q, $format) { global $wpdb; return $wpdb->get_results($q, $format); }
}
$consumer = new Reviewed_Consumer_DB(); $consumer->stamp = $other_stamp;
$resolved = SiteEngine_Ads_Bridge_Consent::resolve(1, 7, 'fixture_submissions', $consumer);
ok($resolved['verified'] === true && $resolved['revocation_check'] === 'unavailable', 'reviewed Ads resolver accepts extended v1 historical stamp but does not enforce withdrawal API');
$consumer->stamp = $stamp;
ok(SiteEngine_Ads_Bridge_Consent::resolve(1, 7, 'fixture_submissions', $consumer)['verified'] === true && !eligibility($stamp)['allowed'], 'real historical resolver still accepts withdrawn evidence: Ads dispatch enforcement is required');
$consumer->stamp['chain_id'] = 'tampered';
ok(!SiteEngine_Ads_Bridge_Consent::resolve(1, 7, 'fixture_submissions', $consumer)['verified'], 'real consumer detects tampering of signed extension');
echo "Withdrawal checks: {$count} passed. SQL boundary model and actual pinned historical consumer; no MySQL/WordPress acceptance.\n";
