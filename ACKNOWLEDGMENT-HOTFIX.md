# Acknowledgment storage hotfix — 0.6.5-rc.2

November 11, 2026. Candidate implementation and isolated/native verification complete. This is an upgrade defect in Consent: the continuity implementation reused `se_consent_events`, a name already used by older event-log schemas. Clean-install acceptance did not cover that older schema. The controls-polish JavaScript did not cause this failure.

Hotfix ZIP: `siteengine-consent-0.6.5-rc.2.zip`, **52,036 bytes**, SHA-256 `83d8e3d80db332ba5482605666be7a18cd1750c24991dd9bbb5d97d0dd0f856e`. All six payload hashes are in PACKAGE-MANIFEST.json. Named source checkpoint: `candidate-0.6.5-rc.2-ack-hotfix-20261111`; this is not a public Stable release or an installed-production acceptance claim.

## Confirmed failure and affected installations

The actual failing capture returned **HTTP409**, `success:false`, code **`audit_unavailable`**. The corresponding inspected server schema was InnoDB for receipts/chains/events, with the expected new continuity columns added into an older events table whose primary key remained `id`, alongside legacy columns such as `event_type` and `consent_id`. An upgrade log recorded **Multiple primary key defined** while attempting to add the continuity `receipt_row_id` primary key. Native reproduction using that exact hybrid schema returned the same409/error and rolled back all capture records.

The capture-time failure can occur without a SQL error: the old code inserts seven signed link fields, then reads `SELECT *`; unrelated legacy columns change the HMAC input, so readback authentication fails and the transaction rolls back. Strict SQL modes/required legacy columns can additionally reject the insert earlier. A currently valid nonce or an empty-payload `invalid_event` result does not check this storage path. Public health's receipt-table existence is not evidence of complete capture schema compatibility.

Affected condition: an existing older `*_se_consent_events` event-log schema, including a hybrid left by earlier `dbDelta` attempts, instead of the expected pure immutable-link layout. New/pure continuity installations are not affected by that collision. Non-InnoDB storage remains a separate, fail-closed compatibility issue; it was ruled out in the inspected incident and is not silently converted by this fix.

Raw request/context, per-site metadata, redacted server evidence and native driver/results remain owner-private outside Git under parent `incident-ack-20261111`. The production diagnostic used one fresh, isolated rejection request with every optional category false, no visitor cookies and no form/provider/advertising dispatch. It failed409, created no Consent records, and left preexisting row counts unchanged. Ordinary rate-limit/bootstrap metadata and possible rolled-back auto-increment gaps are not claimed unchanged or reset.

## Exact correction

- New immutable links use **`*_se_consent_event_links`**, with the existing seven fields, primary/unique constraints and InnoDB requirement. Upgrade no longer applies continuity DDL to the legacy event-log table.
- The old `*_se_consent_events` is **never written, altered, copied or dropped** by this hotfix. A metadata-gated compatibility reader projects the exact seven signed fields and still verifies their original HMAC, receipt digest, event binding and current head. Genuine older links remain valid without re-signing or rewriting them.
- Within/cross-table ambiguity is unavailable; no latest-row selection or grant backfill. Missing/invalid metadata fails closed. Missing heads cannot be recreated when either namespace retains a chain link, preserving withdrawal barriers.
- New captures/retries use the same per-chain lock/transaction, event identity and acknowledgment rules. New rejection advances the existing head/barriers; re-grant never revives an older withdrawn stamp. Stored stamp/API2/canonical form binding and original-versus-current semantics are unchanged. No call-bound API is introduced; the prospective call-authority HOLD remains in effect.
- Administrator diagnostics check the new capture table while retaining the existing report check keys. Uninstall no longer claims/deletes the legacy event-log namespace; uninstall is not a deployment or recovery procedure.

Receipt storage, existing chain heads/barriers, policy/banner identity, choices, signature keys, exports, capabilities/nonces, frontend processing and warnings are unchanged. Confirmation is never fabricated and advertising gates are not relaxed.

## Production migration and rollout — separate from the delivered package

**No production hotfix installation or schema migration was executed in this assignment.** The packaged version upgrade creates **one additional InnoDB table** through the normal supported upgrade path. It needs CREATE/index privileges and ordinary DDL capacity. It does not need a table rename, engine conversion, DROP, historical UPDATE, duplicate deletion, old-database restore or copying existing signed links.

Before rollout, retain per-site source/configuration and all Consent data/schema/head/barrier before-state. Verify the destination namespace is absent or already matches the exact expected layout. Install in place, never uninstall. Verify all required columns/unique keys/engines, unchanged legacy-table and receipt/head data, effective policy/banner, and a separately owned diagnostic acknowledgment without reusing actual visitor chains. Inspect actual response/error; do not treat public health alone as acceptance. If receipt/chains/new-link engines are not InnoDB, metadata is inaccessible, either namespace is ambiguous, or retained evidence is corrupt, stop that site's rollout and review a separately scoped migration; do not reset barriers or rewrite historical grants.

Recovery is targeted plugin files under the applicable execution ownership, preserving the new table and all newer receipts/heads/links. Never restore an old database over newer consent. Reverting old code on a collided installation restores the failure, so preserve fail-closed behavior and assess a forward correction rather than hiding the warning. Existing accepted delivery ZIP remains preserved until owning Helper disposition.

## Actual verification

- Actual production HTTP response/schema/source/error inspection; no assumption that cache or engine caused it.
- **44** local authority/AJAX/stamping assertions; **32** withdrawal/history assertions with the pinned historical consumer; **62** diagnostics/redaction/schema assertions; **12** namespace/compatibility assertions; **12** separate-process retry/conflict/rejection concurrency assertions. The concurrency file model is not native lock/durability proof.
- Native Alma/MariaDB, isolated exact-name fixtures: **5 before checks** reproduce the primary-key collision and409 `audit_unavailable`, zero records after rollback; **15 after checks** prove ACK, original/new-namespace eligibility, same-event old-link retry identity, unchanged unrelated legacy history, rejection/re-grant permanent protection and correct new-link counts. Genuine test links were moved only within owned fixtures to exercise compatibility; no production history was migrated.
- All seven owned native fixture tables were removed with positive absence readback. Existing Alma receipts/events/heads, complete data/schema digests, protected settings and installed Consent files compare equal before/after. Both standard execution locks were positively read back FREE and the holder exited. No hotfix code was installed into the active Alma plugin directory.
- Seven changed PHP files linted; archive/member/committed-byte checks recorded with the package manifest. Frontend JavaScript, authority/signature implementation and policy/banner identity are byte-preserved. No live advertising, new call or provider action.

The first native driver needed a PHP eval-file global-scope correction for fixture counters/teardown; only its exact empty owned tables were cleaned before the corrected run. Those initial counter/cleanup attempts are not counted as passes. No production source or schema change was made while correcting the driver.

Remaining acceptance: installed hotfix/bootstrap and browser ACK under the specifically authorized production/Alma rollout, with before/after preservation. These are not claimed from isolated proof. Do not infer every client site's schema from the inspected installation. No unrelated development, public Stable release or historical repair.
