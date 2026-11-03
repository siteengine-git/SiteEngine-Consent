# Milestone 1 — deployment diagnostics and support

Candidate **0.6.2-rc.1**, based on source checkpoint `7a54433cf5eba4583071c3b25dc6b99b8e548454`. Implemented and isolated-tested; installed verification and Helper acceptance are separate. No Ads/Base/Call Bridge source changes. See PACKAGE-MANIFEST.json for exact installable members and ZIP hash.

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

These checks do not execute a real MySQL upgrade, WordPress capability/nonce cryptography, actual wp-admin/Base menu rendering, minimum PHP 7.4, browser capture or Ads dispatch. No installed acceptance is claimed.

If a PHP fatal or duplicate/MU bootstrap collision prevents Consent or wp-admin from loading, this screen cannot run; verify the complete package and use the existing host/WordPress error tooling. Retained joint acceptance tools remain pinned to rc.2 and were not repurposed or run for this diagnostics candidate.

## Exact remaining installed verification

Alma Codex remains the server installer/test owner under current roles; no server execution was delegated to this source task. Current local Alma activity/status was inspected, preserving the accepted Ads/joint evidence and newer settings. No server lock was acquired and no site request/bootstrap/install was attempted. This is an executor-scope boundary, not a connection failure or a request for Alma to approve plugin decisions.

After the candidate is selected for installed acceptance, the assigned executor installs this exact package under the existing nonoverlapping lock, compares diagnostics with read-only metadata, visits both available admin integration routes, verifies an administrator can generate the report and a nonadministrator cannot access the page, and compares before/after audit/head/barrier and relevant option hashes. Do not damage real storage to test failure cases; those belong to isolated fixtures. Record installed package hash, actual checks and bootstrap effects; retain backups/cleanup ownership. Helper decides product acceptance and delivery replacement. No manual harness operation is requested from Aubrey.
