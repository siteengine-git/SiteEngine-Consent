# SiteEngine Consent status — 2026-10-24

Release/source: **0.6.0 retained baseline; functional acceptance pending**. Policy default: `2026-05-08-v1`. Runtime code and metadata are unchanged.

Canonical checkout: `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent\source`.
Existing project/archive home: `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent` (actual attached folder; obsolete recorded spellings were not recreated).
Origin: https://github.com/siteengine-git/SiteEngine-Consent.git, branch `main`.
Preserved initial remote commit: `5c6042fc79c53a2ae48d0c8c00231974a75f1326`, containing only README.md.
Baseline checkpoint: `baseline-0.6.0-retained-2026-10-24` (source identity only; not installed acceptance). Exact baseline commit is recorded after checkpoint creation below.

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
