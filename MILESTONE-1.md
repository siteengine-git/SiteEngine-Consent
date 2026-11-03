# Milestone 1 — deployment diagnostics and support

Stable **0.6.2**, finalized from reviewed candidate `687d0582b2acd7857b6893679eefe90a0cfc50e3`. Milestone 1 installed verification passed on Alma and Helper acceptance is authorized by the release assignment. Stable differs from tested rc.1 only in release metadata. This is not client-production deployment. No Ads/Base/Call Bridge source changes. See PACKAGE-MANIFEST.json for exact installable members and ZIP hash.

## Administrator use

Open **SiteEngine → Consent** when Base is present, or **Tools → SiteEngine Consent** in standalone operation. Select **Deployment diagnostics and support report**. The same existing `manage_options` gate applies in both routes. The diagnostics view can also be selected with `view=diagnostics` on the existing Consent page; it returns before the receipt viewer/export queries.

The table distinguishes **verified**, **failed** and **untested**. Verified means only that particular inspected prerequisite passed. It checks actual required receipts/events/chains columns, nullability, receipt auto-increment, indexes and InnoDB engines; connection read-only/autocommit flags; effective configuration presence/range; cleanup schedule presence; and the expected public static current-eligibility API signature. Missing and inaccessible tables cannot always be distinguished by metadata visibility. Metadata-read failure remains untested rather than a pass. No overall operational, Ads enforcement or delivery success is inferred.

Select **Generate redacted report** to create copyable JSON on the page. This POST requires the existing administrator capability and a dedicated WordPress nonce; no report is saved or sent. The fixed output allowlist contains safe loaded component versions, configuration source/presence flags and numeric limits, SHA256 fingerprints of policy/banner identities, diagnostics and verification limits. No company name, URL, DB name/prefix, server path, raw SQL error, receipt, cookie, submission, signing material, continuity secret or arbitrary configuration is included. Output is escaped, including the report textarea. This is a support report, not a receipt export.

The reference explanations cover existing capture/ACK states and contract 2 reasons; they are not an observed error log. No new telemetry or broad request logging exists. A signature/receipt never proves person identity, intent or permission. Historical verification may succeed while current authority is unavailable or withdrawn. Only strict `available === true && allowed === true` passes Consent's advertising gate; Ads still owns policy/DNC/identity/dispatch enforcement. Personalization stays independent. Old submissions remain negative/withdrawn after re-grant.

## Upgrade and recovery

Stage the candidate under the existing project `deliverables` folder, separately from the approved installation directory `D:\SiteEngine\Plugins\..Deliverables\PLUGIN — SiteEngine Consent`. Preserve its existing rc.2 ZIP until Helper acceptance. After acceptance, the delivery owner replaces that copy with exactly one accepted installable ZIP; preserve prior packages in the project archive.

The authorized Alma installer checks actual overlapping activity and the existing execution lock before installation, takes selective plugin/settings/storage backups, verifies all six ZIP members against PACKAGE-MANIFEST.json, and uses a normal WordPress plugin upgrade. No uninstall, deactivation-based cleanup, table drop, historical repair, export, form submission or external dispatch is needed to verify diagnostics. All active settings, audit receipts, immutable links, heads and permanent barriers must remain intact. This milestone adds no DDL or migration logic. The existing release-version bootstrap may rerun the unchanged dbDelta schema routine and update plugin/policy version options; existing cron registration and privacy URL transient/probe behavior also remains. Diagnostics itself only reads metadata/configuration/schedule state, does not create receipts or fix anything, and makes no HTTP request.

Policy default stays `2026-05-08-v1`. The default banner identity is explicitly pinned to its prior `0.6.1-rc.2` value, separate from plugin release identity, so this upgrade alone does not renew consent. Existing explicit banner overrides are honored; a site override derived from the release constant must be reviewed by its owner. Authority/continuity files, signatures, categories, API contract 2, frontend capture/order and health/export behavior remain unchanged.

Targeted recovery:

- Missing/inaccessible metadata: inspect DB grants and the supported upgrade outcome. Do not run a receipt/capture test to prove access. Preserve the redacted report and resolve the precise metadata boundary.
- Wrong columns/indexes/engine: stop capture acceptance and arrange a backed-up, separately scoped storage correction. Never deduplicate legacy receipt IDs, convert storage automatically, reset heads/barriers or modify history through diagnostics.
- Read-only/nonautocommit: inspect the existing database routing/connection configuration with the server owner; the diagnostic does not establish locks or write durability.
- Configuration missing/invalid: review the same explicit constants → Base → WordPress precedence. Do not change policy or banner identity just to clear a diagnostic. Cleanup absence requires normal cron diagnosis; do not run cleanup as a test.
- Missing/wrong API: verify the full approved plugin package and duplicate/MU copy situation. Do not substitute an always-successful provider. A loaded signature still needs real consumer/dispatch acceptance.
- Pending/failed ACK: forms remain usable, the submission remains negative, and existing same-event retry/new-choice behavior applies. Do not upgrade old submissions after a delayed ACK or re-grant.

If this admin-only upgrade fails, the installer can restore the backed-up rc.2 plugin files and verify hashes, without uninstall or DB rollback. Preserve all newer data and barriers. A restored older release can trigger its existing version bootstrap too; no whole-database restore is permitted.

## Focused verification

Local PHP 8.3.36 `-n`; WordPress/native DB boundaries doubled. Commands from canonical source (PHP executable is the retained local test runtime, not a new environment):

```powershell
$PHP = 'D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Ads-Bridge\local-test-runtime\php-8.3.36\php.exe'
& $PHP -n tests/diagnostics.php
foreach ($mode in @('missing','wrong','real')) { & $PHP -n tests/diagnostics-api.php $mode }
& $PHP -n tests/diagnostics-upgrade.php '..\deliverables\milestone1-preupgrade-source.php'
```

The final argument is the retained noneditable snapshot of `7a54433:siteengine-consent.php`, outside the repository/package. The diagnostics fixture uses actual packaged DDL to supply metadata, then independently mutates/removes columns/indexes/engines and simulates missing/failed metadata reads. **62 diagnostics assertions, 3 separate-process API checks, 41 source upgrade-preservation checks = 106**. Access refusal happens before reads; invalid nonce refuses before report creation; support output excludes sentinel secrets/PII/cookies/request data and resists markup injection. Exceptions restore database error suppression. Preservation verifies every preexisting named function body except admin rendering, the three immutable implementation/uninstall hashes, and policy/banner defaults. No unchanged runtime/browser/joint acceptance suite was rerun.

Those 106 isolated checks do not constitute installed acceptance. Separate installed evidence below covers the actual upgrade, active Base callback, capabilities, nonces and HTTPS reporting. Minimum PHP 7.4, browser capture and Ads dispatch were not rerun for this diagnostics milestone.

If a PHP fatal or duplicate/MU bootstrap collision prevents Consent or wp-admin from loading, this screen cannot run; verify the complete package and use the existing host/WordPress error tooling. Retained joint acceptance tools remain pinned to rc.2 and were not repurposed or run for this diagnostics candidate.

## Installed verification and acceptance — November 3, 2026

The Helper explicitly delegated this Consent Codex to install, verify and finalize the release, superseding the older Alma-only executor restriction for this bounded assignment. Actual activity was inspected, and the existing common executor and overlapping joint-operation locks were acquired without terminating or bypassing another executor. Both were released and independently read back as free after completion. No shared services, runtimes, other plugin source, Base activation or client-production deployment changed.

The reviewed candidate ZIP and six members were verified before an in-place upgrade with targeted owner-private recovery copies. **24 installed WordPress/native assertions** passed: actual table/schema/storage prerequisites, configuration/API availability, active Base module route/rendering, unchanged effective configuration/policy/banner, valid report JSON and redaction, real WordPress nonce checks and capability refusal. **21 HTTPS assertions** passed using short-lived sessions for an existing administrator: actual authenticated admin GET, form nonce, report POST, web-loaded version, diagnostic facts and redaction; invalid nonce terminates with an expired-link error and no report; unauthenticated nonadministrators are redirected to login. No account, password or role was created/changed. Each test-owned token was destroyed and its private context removed, including failure paths.

One test expectation was corrected after inspection: an invalid nonce returns the WordPress error body with HTTP200 because the admin header has already been emitted before the page callback. No report is disclosed. The server-side nonce refusal and HTTPS error response both passed; no runtime fix or weakened nonce check was made. Prior successful HTTP/report checks were carried forward; only the affected rejection checks were repeated.

Actual metadata agrees with diagnostics. Before/after full row and schema digests of receipts, immutable event links and chain heads/barriers match; activation and Ads settings match. The only changed Consent option is its version marker (rc.2 → rc.1 → stable0.6.2). Cron and privacy transient digests are unchanged. The preexisting normal version bootstrap ran without an observed schema/data/configuration delta. No consent event, form submission, receipt export, cleanup or advertising dispatch was invoked.

Stable0.6.2 was then installed and all six final payload hashes were verified. CLI version, installed version option and actual HTTPS web-loaded version are0.6.2; policy/banner/effective configuration remain unchanged. Unchanged functional suites were carried forward, not repeated. The alternate standalone path is covered by the existing Base-absent focused fixture and unchanged route-registration body; Base was not deactivated on Alma.

Limits: no connected authenticated UI browser was available for visual/click testing; actual HTTPS WordPress routes/cookies/nonces/HTML/JSON were exercised instead. An authenticated subscriber session was not used; real WordPress capability refusal for user0 and HTTPS anonymous refusal passed, with isolated nonadministrator access evidence retained. No new consent/Ads delivery acceptance, minimum-PHP run or client-production release is implied. No material Milestone1 defect remains. Under the Helper's conditional release direction, Milestone1 is accepted.

Recovery copies, scoped original settings/schema/audit data and nonsecret verification results remain owner-private under this assignment. Consent Codex owns retention/cleanup of these new artifacts; prior joint fixture/audit owners and permanent barriers are unchanged. No temporary auth context or owned session remains. Never restore an old database or delete retained audit evidence. The approved delivery is replaced only with this verified final stable ZIP, and prior packages remain archived. Exact commit/tag, ZIP/hash and release links are recorded in STATUS.md and PACKAGE-MANIFEST.json.
