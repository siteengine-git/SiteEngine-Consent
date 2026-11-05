# Consent shared panel candidate 0.6.4-rc.2

November 5, 2026. **Implemented, locally verified and packaged; not installed, jointly verified, Helper-accepted or released.** Approved interface is the exact Contract2 at `eb2da2a0493d92d475c65c18695f6febd292e71d`, confirmed by both Helpers through Aubrey. Existing proposal filename is retained; no alternative API was introduced. Candidate checkpoint: `candidate-0.6.4-rc.2-shared-panel-local-20261105`.

## Implemented behavior

The version2 provider registers with `SEShieldSharedLauncher`, validates the exact registration ID, current global, nonregressing safe generation and live six-second lease. Shield renews every two seconds. Both initialization orders and delayed render are supported. Duplicate API announcements do not reconfirm/reset an active draft. Old version1 handoff is incompatible and leaves standalone controls available.

`mount(slot, actions)` moves the original `#se-consent-preferences-content` subtree, without cloning, after same-document/connected/empty-slot and standalone-closed checks. `setView('closed'|'inactive'|'active')` controls presentation sessions without focus or persistence. `unmount()` returns that retained node to the original dialog, even from a detached host. Stale provider callbacks cannot steal or unmount a replacement's controls. Partial/failed mounting is unwound. The provider has no version1 `open` callback.

Consent-specific banner/footer/menu Manage actions and the backward-compatible `openPreferences(initiator)` use `handle.openConsent(initiator)`. Consent verifies literal success, live readiness, active mounted view, visibility and focus in its own controls. Failure withdraws/cleans up the host before opening standalone. The legacy method still returns undefined. Shield owns active tab semantics, sizing, container focus, Escape and scroll behavior. Consent's original modal/backdrop, trap, background isolation and body lock remain inactive while mounted. Initial banner and explicit custom/footer behavior stay independent.

Draft selections survive tab switching. Closing discards unsaved edits; explanatory text says so. New sessions reload current stored choices, including necessary-only defaults when there is no valid prior state. Existing explicit Save/Reject initiate the unchanged persistence path and close via `actions.close('saved')`; Cancel uses `'cancel'`. Opening/tab/closing never chooses consent. Pending/failed saved events retain their state and same-event retry identity. Inline Retry confirmation delegates to `retryAudit`. A host failure that discards an unsaved draft shows an explicit recovery notice. Missing/failed/expired/incompatible hosts restore standalone controls and safe focus fallback.

## Package and preservation

Candidate: project `deliverables/candidates/siteengine-consent-0.6.4-rc.2.zip`, **50,016 bytes**.
SHA-256: `74691f60a2114c0e5f582c7612ec1c8d7173e5ce7ecd8cffbbdd7e9e911135c6`.
`PACKAGE-MANIFEST.json` records all six member hashes. Only main frontend/release metadata and packaged readme changed; authority, continuity, diagnostics and uninstall payloads remain unchanged. Policy `2026-05-08-v1`, banner `0.6.1-rc.2`, API2, signed evidence/history and permanent withdrawal barriers remain unchanged.

Accepted rc.1 source `aaf13f3878a8db0b20935da2e6dcd4d221177391`, ZIP SHA-256 `5842abffe169046db66e0213c0ea5333b0fe2d86fb26496f1ceb3239fa174337`, is retained in candidates and the authoritative delivery directory. Accepted Shield RC4 and private joint rollback/evidence remain under their owner. No accepted delivery was replaced. No public Stable release or client deployment.

## Actual focused checks

- `node tests/shared-panel.cjs`: **70** assertions against production embedded JS, existing storage/capture boundary, synthetic v2 host and DOM-ownership model. Both orders, mount identity, draft/tab/close, direct entry, explicit Save/Reject/pending/retry, invalid/stale readiness, renewal/expiry, partial mount, failed open/close and standalone recovery.
- `node tests/preferences.cjs`: **34** affected standalone UI assertions and embedded JS syntax.
- PHP8.3.36 `-n -l siteengine-consent.php`: pass.
- `tests/preferences-upgrade.php` against retained accepted rc.1 main: **42** preservation assertions. Preexisting PHP named bodies outside frontend/config output and immutable authority/continuity/uninstall hashes preserved; policy/banner defaults unchanged.
- Direct accepted/current embedded-function comparison: **51 unchanged JavaScript function bodies**, including payload building, Google flags, storage, capture/ack/retry and form authority. The explicit save path changes only its close reason to the presentation layer.
- `node --check tests/shared-panel.cjs`, `git diff --check`: pass. Six ZIP member/hash and source-byte checks; accepted delivery rehash: pass.

These are isolated checks, not installed WordPress, actual Shield browser, pixel layout, real capture/storage, screen-reader or cache-HIT acceptance. No site calls, consent events/forms/exports, server fixtures, executor lock or external messages were created. Historical version1 tests remain retained, not claimed as v2 passes. Prior authority/integration evidence is carried forward rather than repeated.

## Joint installed handoff and recovery

TO: HELPER — SiteEngine Consent; HELPER — SiteEngine Site Shield; PLUGIN CODEX — SiteEngine Site Shield

FROM: PLUGIN CODEX — SiteEngine Consent

SUBJECT: Contract2 Consent candidate ready for exact-pin joint pass

Resolve the named candidate checkpoint to its source commit and use the exact ZIP/hash above. Coordinate the Shield host candidate's source/ZIP pins before installation. Shield Codex is the sole authorized joint Alma executor; Consent acquired no server ownership. This document prepares the relay through Aubrey and does not claim delivery/acknowledgment.

Use existing execution locks and targeted plugin/settings/schema/full-history before-state. Install only the exact coordinated pair; preserve accepted rollback files and policy/banner/configuration. Complete one focused pass: general Accessibility entry; direct Consent entry from banner/footer/menu; tabs/closing/draft reset; keyboard/focus; desktop/mobile sticky-tab layout; failure/lease fallback; category/save/pending/retry using explicitly owned synthetic fixtures with delivery disabled. Never create a live choice or fixture without its exact recorded ownership. Retain audit/event/head/barrier records; no uninstall, cleanup or old database restoration. If owned save fixtures are unavailable, report that exact gap and continue nonmutating checks.

Capture desktop/mobile screenshots and installed payload hashes. Return protected full data/schema/options preservation, actual ordinary version-bootstrap effects, retained fixture/recovery resources, limitations and positive lock-release readback. Carry prior authority evidence; do not reopen the old acceptance campaign. Screen-reader speech/cache-HIT limitations remain until actually tested. Any failure belongs to its owning plugin; repeat affected checks only.

Recovery is targeted plugin-file rollback to retained accepted rc.1 under the same lock, coordinated with Shield; never roll a database back over newer events/barriers. No new schema or setting migration is introduced. If runtime defects require a rebuilt package, pin a new candidate and retain this one. Keep accepted delivery copies until each Helper separately accepts its successor. No client deployment, Stable publication or Milestone4.
