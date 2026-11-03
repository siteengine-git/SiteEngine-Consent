# Milestone 2 — browser and integration reliability

Focused installed pass completed November 3, 2026 on accepted **0.6.2**, runtime/source release [v0.6.2](https://github.com/siteengine-git/SiteEngine-Consent/releases/tag/v0.6.2), source pin `ca46d81ce016c451a8be9377a9510b0932d2e9c6`. **No Consent runtime defect requiring a code change was demonstrated.** No new package, installation or release was manufactured. All six installed members and the accepted delivery ZIP remain unchanged; SHA256 `bec0bbbe7cdec7d22fd901ab16931724ebbe4dcc73be86080924b8ad2ce59da5`.

Milestone 1 is closed; its diagnostics and authorization evidence was carried forward. The Helper explicitly delegated this Consent Codex for Milestone 2, including bounded Alma execution. This work did not reopen the old joint acceptance campaign or change another plugin.

## What was actually exercised

One new synthetic Fluent Forms form and one temporary published fixture page were allocated by returned IDs, with a unique ownership marker and no notification or integration-feed metadata. Existing delivery gates were verified false; existing Ads routing targeted the shared form, not this fixture. Shared form/settings were preserved. No advertising export, customer notification or unrelated plugin change was invoked.

The connected in-app browser used the real HTTPS page, existing WordPress-authenticated browser context, installed Fluent Forms **6.2.17** assets/submission handler and actual Consent AJAX capture. Browser actions used real controls; no acknowledgement, receipt, stamp or server eligibility result was fabricated. Exactly **three business submissions** and **three consent interactions** were performed in **one new browser/site chain**:

| Step | Installed result |
| --- | --- |
| N: business submission before any acknowledged consent | Successful vendor submission with a valid form-bound signed negative stamp (`missing`); advertising ineligible. |
| Grant advertising, leave personalization denied; submit A | Successful acknowledgement and vendor submission; canonical signed evidence verified and A initially `eligible`. |
| Reload the same page/browser | Existing advertising choice and independent denied personalization persisted; no new consent interaction. |
| Withdraw using the existing reject action | New immutable event/revision 2; A remains historically verified but becomes `withdrawn`. N stays negative. |
| Explicitly re-grant advertising with personalization still denied; submit C | New immutable event/revision 3 in the same chain; C is `eligible`, A remains `withdrawn`, N stays negative. Advertising withdrawal barrier remains revision 2. |

Every admitted submission was read by its exact owned form/case/marker, then exact returned ID. Canonical signatures, canonical form binding, unrelated business fields and vendor entry-details projections passed. Response hashes for N/A/C stayed unchanged through later choices. No latest-row selection or adoption of old fixtures occurred.

Original Consent receipts, event links, chain heads and barriers were checked against before-state row hashes and remained unchanged. Protected configuration, activation, cron and the shared form remained unchanged. New events are retained as synthetic audit evidence; no historical row, immutable link or barrier was removed or reset.

## Concrete compatibility findings

- **Entry-details format:** Fluent Forms stores an array as separate `sub_field_name` rows under the field name; nested arrays use `maybe_serialize`, and empty/null/false subvalues are omitted. This agrees with the retained serializer fixture. An initial test incorrectly expected one serialized whole-stamp row. The readback was corrected to compare the exact vendor projection with the canonical signed response, and the already-persisted N was checked without resubmission. No runtime correction was needed. Current consumer authority is the canonical response stamp, not reconstructed entry details or flat fallback.
- **Preference entry point:** after acknowledgement, the tested theme does not supply a persistent preferences link. The fixture page received one small button using the existing `data-se-consent-action="manage"` hook so withdrawal/re-grant could be exercised through UI controls. Only that owned page changed; the Consent banner and shared theme were not redesigned. A deployment must expose a reachable preferences entry point using the existing integration, such as `SiteEngineConsent.openPreferences()`. This pass verifies that integration, not an out-of-box persistent theme control.
- **Installed transport:** actual valid capture and Fluent Forms nonces worked through same-origin HTTPS AJAX. Two anonymous HTML reads of the owned URL returned HTTP200/Cloudflare `DYNAMIC`, no Age header, and HTTPS AJAX configuration with a nonce. No immediate cache/proxy/nonce defect appeared. This is not a guarantee about future cache lifetime, expired nonces, every edge or anonymous JavaScript submission.
- **Privacy-aware tooling:** the browser tool exposes sanitized DOM rather than page globals and redacts receipt fields. A failed attempt to inspect `navigator` in that scope is a tooling boundary, not evidence that the browser lacks Web Locks. Actual acknowledged captures passed the production required-API guards. Authority was verified from persisted signed evidence rather than from redacted DOM values.

## Retained evidence and explicit coverage limits

The production browser algorithm, authority/continuity classes, API contract 2 and policy/banner identity are unchanged from the tested rc.2 implementation through stable0.6.2. Existing focused VM/SQL-boundary/parallel-file evidence was reviewed and carried forward, not rerun or counted as new installed passes:

| Behavior | Evidence for this milestone |
| --- | --- |
| Real grant, withdrawal, re-grant, signed form persistence, category independence, reload persistence | New installed browser/WordPress/MariaDB pass above. |
| Business submission without acknowledged authority stays negative after later acknowledgements | New installed N, checked again after withdrawal and re-grant. |
| Delayed/failed acknowledgement, original-event retry, reversed/superseded ACKs and stale grants | Retained actual production-JS VM and authority/continuity fixtures. Not newly injected into the installed browser. |
| Concurrent-tab rejection/order and first-use key serialization | Retained VM and separate-process algorithm evidence. No live multi-tab race or InnoDB concurrency acceptance claimed. |
| Storage refusal or missing required browser APIs, explicit failure/retry feedback and negative authority | Retained production-JS VM evidence. No live browser permission/API fault injection; installed N additionally proves a usable business form without authority. |
| Actual current HTTPS/cache/valid nonce/Fluent Forms path | New installed successful browser operations and bounded anonymous HTML reads. No forced nonce expiry, cache purge or compatibility matrix. |

The connected browser was already WordPress-authenticated. Anonymous HTML was inspected, but anonymous JavaScript submission, anonymous security-widget challenges, other browsers, minimum PHP, forced durable-storage denial and live cross-tab faults were not newly exercised. Missing coverage was not converted into a pass or used to start another framework/campaign. No security, negative evidence, ordering or withdrawal guarantee was weakened, and no unsupported-environment fallback was added.

## Fixture closure and delivery

The owned fixture page is now **draft/unpublished**, and its form is **draft**; both statuses and retained submission IDs were positively read back before releasing the locks. The temporary browser tab was closed. Synthetic preference/continuity state was not broadly cleared, nor was any server barrier reset. All synthetic form/page/revision/metadata/submissions and the new audit graph remain retained under Consent Codex ownership, with exact IDs and hashes in the private assignment journal/handback. No deletion or clean-database restoration is claimed. Prior fixture ownership is unchanged.

The existing common executor and overlapping joint-operation locks were acquired only for server work/protected unfinished fixtures, then released and independently read back free. No worker was terminated or lock bypassed. A redundant guarded read attempted after release correctly refused its expired execution lease; it is not counted as a verification pass, and no mutation was replayed.

Implemented: no runtime patch. Packaged: no new build. Installed: accepted0.6.2 retained. Verified: the focused installed cases above, with isolated/deferred boundaries explicit. Released: no new release; accepted0.6.2 delivery remains exactly one unchanged installable ZIP. Milestone 2 closes with this evidence and the site preference-entry-point requirement. Await the Helper's next bounded task; do not begin Milestone 3.
