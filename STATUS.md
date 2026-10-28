# SiteEngine Consent current status — 2026-10-28

**0.6.1-rc.1 — unaccepted candidate; Ads integration acceptance BLOCKED; no deployment.**
Canonical checkout remains `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent\source`, origin https://github.com/siteengine-git/SiteEngine-Consent.git, branch main. Work started from clean local/remote `42149bcd3427d3ef202eeb944c074e334ae7ce0c`; retained source baseline `368c0affe8fe3bb6929222a23164429f3538512e` and original archive/history are preserved. Current checkpoint name: `candidate-0.6.1-rc.1-isolated-2026-10-28`. Exact resulting commit/push evidence is recorded in the parent local STATUS.md after creation; use `git rev-parse HEAD` for the current documentation tip.

Authorized scope: finalized CONSUMER-CONTRACT.md, prospective Consent repair, isolated regression tests, documentation, reviewed commit/push and ZIP. The request ended mid-sentence; the existing REPAIR-PROPOSAL.md provided the bounded independent scope. No runtime changes outside Consent, deployment, live WordPress acceptance writes, historical repair, policy changes, exports or messages.

Implemented: actual Fluent Forms response-array mutation filter (3 arguments), post-component priority, frozen request stamp on submission_form_data, removal of forged nested/trimmed flat authority, signed/row-bound/policy-valid acknowledgements and signed form-bound stored stamps. Strict boolean category mapping replaces submitted Google flags. Current form ID echo must match signed cookie identity; missing/pending/failed/contradictory/expired/unsigned/ambiguous authority is explicitly negative. Legacy rows remain unchanged. Each new interaction receives a namespaced event receipt; same-event retries serialize via database advisory lock and reuse the same row, conflicting/duplicate replays fail. No unique-index/schema migration. Server time drives new retention timestamps. Browser pending/failed choices have no earlier receipt authority; stale ACKs cannot restore newer choices; legacy/policy/banner mismatch requires a new explicit choice. Policy remains `2026-05-08-v1`.

Consumer contract decision: actual unchanged Ads 0.6.3 still ignores negative stamps, falls through to flat/details grants and resolves latest reused ID. Consent-only sanitization protects fresh supported submissions but cannot establish a global veto over preexisting details/history/alternate paths. ADS-CODEX-ASSIGNMENT.md specifies the required Ads-owned signature/negative-veto/exact-row/digest contract. Prepared only, not sent; Ads/Call files and existing gates were not modified. Integration acceptance remains blocked until the coordinated change and lifecycle tests pass. No fake blocking receipt IDs or denial rows were introduced.

Fresh tests on Dell-8940: **44 PHP assertions + 22 browser-script assertions + 8 real-consumer fixture checks = 74 checks**. Four consumer checks intentionally reproduce unsafe legacy behavior and are blockers, not acceptance passes. All six PHP files pass PHP 8.3.36 lint. Tests load actual Consent code without constructor/WordPress bootstrap, the attributed installed Fluent Forms entry-details method excerpt, and the actual external SHA-256-pinned Ads CLI source. WordPress APIs, in-memory persistence/locks and VM browser DOM/storage/transport are doubled. Coverage includes stored JSON shape/canonical details, signature/form mismatch, malformed payload/nonce boundary/rate limit, conflicts/missing data, audit write/read failures/retry/contention, accept-to-reject/reject-to-accept history, policy/banner/expiry, storage denial, hardcoded fields, cookie budget and reversed/cross-tab ACK order. See tests/README.md for reproduction and precise limits.

No real MySQL concurrency/durability, core nonce cryptography, real Fluent Forms/payment/alternate submission, hidden-field sanitization, actual web/browser/cache or installed acceptance was run. A matching `se_consent_receipt_id` field must survive the installed form sanitizer or new authority fails closed. Advisory-lock behavior assumes the same primary WPDB connection; engine/routing/autocommit compatibility needs isolated engine and later controlled acceptance. DNC/full Ads export gates remain unchanged and were not exercised by a live export. Minimum PHP 7.4 compatibility was not executed. Source inspections used files only, no WP-CLI/REST bootstrap. Last verified deployment remains the historical 0.6.0 evidence below.

Candidate ZIP: parent `deliverables/siteengine-consent-0.6.1-rc.1.zip`, **30,469 bytes**, SHA-256 `c20ecb87b134c411e4f12fc01c9b22c9f105b43e1ac67af4cca1287505633a5a`. Four runtime files only; each ZIP entry SHA-256 matches source. PACKAGE-MANIFEST.json records exact bytes/hashes. Original 0.6.0 ZIP/uninstall hashes remain unchanged. No ZIP, operational notes, external source tree, credentials, live rows or logs are staged for public Git.

**One next task:** have the Ads-owning Codex implement/test ADS-CODEX-ASSIGNMENT.md under its own authorization, then review coordinated isolated compatibility evidence before requesting controlled Alma acceptance. Do not deploy this candidate independently.

## Historical baseline record — 2026-10-24

Release/source: **0.6.0 retained baseline; functional acceptance pending**. Policy default: `2026-05-08-v1`. Runtime code and metadata are unchanged.

Canonical checkout: `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent\source`.
Existing project/archive home: `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent` (actual attached folder; obsolete recorded spellings were not recreated).
Origin: https://github.com/siteengine-git/SiteEngine-Consent.git, branch `main`.
Preserved initial remote commit: `5c6042fc79c53a2ae48d0c8c00231974a75f1326`, containing only README.md.
Baseline checkpoint: `baseline-0.6.0-retained-2026-10-24` (source identity only; not installed acceptance).
Verified source baseline commit: `368c0affe8fe3bb6929222a23164429f3538512e`. Later documentation-only commits retain these exact plugin blobs. Resolve the current documentation tip with `git rev-parse HEAD`; public push/remote-byte verification is recorded in parent STATUS.md after completion.

Retained installable archive remains in the parent project: `siteengine-consent-0.6.0.zip`, 26,211 bytes.
SHA-256: `86ef1d90173d48a4379c18f1e67f10c576c753fddae3d4ad0da3ca0a5c127284`.

| Original file | SHA-256 |
| --- | --- |
| siteengine-consent.php | c2f22a0cd645943fc9cf759b471083a64132d63662054eb9248c5627568b5a71 |
| readme.txt | 7c1478a6e14342d57cf3bdb9b9d72ec56746f2b3a4c6078fa3c1e0d132f452f5 |
| uninstall.php | 5ac04f29a8841637c340a3c630d76903bb458a4534d508fd933208914885a08b |

Archive entries, extracted source and both inspected deployments match all three hashes. No same-version deployed patch was found. Existing parent `siteengine-consent-new` and both older ZIPs identify 0.5.3 and remain untouched; they are not the editable baseline. No prior project AGENTS.md, STATUS.md or Git checkout existed in the inspected project.

Fresh checks: archive size/SHA-256, three entry/file SHA-256 comparisons, original PHP openings, installed source comparisons, provider hook invocation/shape, consumer lookup, direct read-only configuration/schema/cron inspection, and terminal Git clone/push dry run. Both checkout PHP texts passed stdin lint using Alma PHP **8.3.36 CLI**; both matching installed PHP files passed file lint. No local php.exe was found in PATH or the bounded Program Files/SiteEngine search. Stdin lint emitted an ionCube mmap warning; syntax results were successful. No WordPress load/integration/browser test was run.

Inspected test stack: Consent 0.6.0, Base 2.2.2, Fluent Forms/Pro 6.2.16, Ads Bridge 0.6.3; relevant plugins are active in persisted options. No MU directory or additional Consent class implementation was found in the inspected plugin files. Receipt schema has the expected 13 columns, primary id and non-unique receipt_id/policy_version/created_at indexes. A daily cleanup option entry and prior cleanup metadata exist; no cleanup was invoked. Web worker executable points to lsphp83; vhost handler configuration was inaccessible, and no web request was made. Exact loaded class and public cache behavior remain unverified without bootstrap. Golden source matches 0.6.0; its active state remains historically reported rather than freshly bootstrapped.

Detailed site-specific authority, paths, scheduler timestamps, setup delta, provider/consumer hashes and limitations are in parent STATUS.md, outside this public checkout. Configuration authority is explicit legacy constants, then Base shared fields, then WordPress fallback; actual stored evidence is recorded only locally. No settings/policy changes were made.

Verified defects: the callback return on before_insert_submission is discarded by installed Fluent Forms; the callback expects an insert record rather than a response array. Browser receipt IDs are reused while audit writes append; installed Ads resolves the newest row for an ID. Older bindings can therefore resolve later choices. This is source-derived impact, not a claim that live records were inspected or modified.

Historical evidence: the handoff's 18 static PASS lines and Golden fixture narrative are preserved in the original parent handoff, not rerun or promoted to functional acceptance. No original executable harness was present. Baseline import does not validate stamping, authentic consent, immutability, browser/policy transitions, compatibility at PHP 7.4, or legal compliance.

Publication scope: unchanged neutral plugin source, existing README, repository rules/status and technical repair proposal. No archives, handoff transcripts, active config, receipt rows, form submissions, logs, database exports or credentials are included. Public visibility and proprietary metadata remain unchanged.

**One next task:** review and explicitly authorize the bounded Consent repair/test assignment in REPAIR-PROPOSAL.md, with the legacy Ads fallback compatibility decision resolved before implementation or controlled Alma submissions. No runtime repair/deployment is authorized by this checkpoint.
