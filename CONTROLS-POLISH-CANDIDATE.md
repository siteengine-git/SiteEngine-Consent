# Consent controls polish — 0.6.5-rc.1

**Current acceptance supersedes the historical pending labels below:** Consent Helper accepts this exact rc.1, following completed paired Alma evidence with ShieldRC6. The unchanged original ZIP is now the sole accepted installation delivery. No stable0.6.5 rebuild/version rename/public Stable release. See [acceptance and delivery](ACCEPTANCE-0.6.5-rc.1.md) for exact pins, carried results, limits and archived prior delivery.

November 6, 2026. **Implemented, locally visual-verified and packaged; not installed, Helper-accepted or released.** Baseline accepted stable0.6.4 / `14e0b1a8cff81acf1611edf2d0202de313f22250`. Candidate checkpoint: `candidate-0.6.5-rc.1-controls-polish-local-20261106`. No Milestone4 or processing architecture change.

## Reproduced cascade and correction

Real Consent0.6.4 CSS/controller and accepted ShieldRC5 CSS/controller (`599becebd9a981a198dc65e22b0499c7f335606a`) were loaded with Shield's retained isolated rendered HTML fixture in local Chrome. No Alma navigation, choices, submissions, capture or retry calls. The stylesheet order preserves Shield loading after Consent.

Computed before: switch **48×44px**, **10px 14px padding**, **8px radius**, gray background; Save inherited Shield's gray background/dark text. Shield's `.se-shield-panel button` specificity `(0,1,1)` beats Consent's original class-only switch/button rules `(0,1,0)`, overriding track padding/radius/min-height/background. The flex text column also squeezed entire descriptions beside switches. This reproducible paired-source cascade is verified; an additional installed theme-specific contribution is not claimed without inspection.

Correction is scoped to `#se-consent-preferences-content`. Switches explicitly use **60×44px**, zero padding, pill radius, border-box, a 28px thumb and independent checked/disabled states. Category heading/state share a row area with the switch; descriptions span the full card width. Cards have consistent spacing, readable line-height, shrinkable headings and wrapping. Actions have explicit minimum44px targets, padding/borders, primary Save styling and hover/focus/disabled presentation. Narrow actions stack; Save and Reject remain separate, visible actions. Inline pending/failed/Retry remains intact, with recognizable Retry styling. Standalone uses the same scoped control styles.

No new global button rules, broad reset or `!important` was added. The entire production JavaScript is byte-identical to stable0.6.4. Main source outside the style block and release version strings is byte-identical; category markup, roles/states, controller bindings, Contract2, acknowledgment/retry, signed authority, policy/banner and withdrawal barriers remain unchanged. Other four runtime members are unchanged. Version metadata/readme describe only this candidate.

## Sizing handoff through Aubrey — prepared before packaging

TO: HELPER — SiteEngine Site Shield

FROM: PLUGIN CODEX — SiteEngine Consent

SUBJECT: Exact Consent mounted sizing/padding assumptions

Mounted root remains normal-flow border-box, width auto, min-width0, max-width100%, **16px internal padding**. Shield's existing `#se-shield-view-consent` slot must retain **padding0** so there is one inset. Shield owns approximately400px responsive container, outer geometry/header/tabs and the single active-tab scroll region. Consent adds no content fixed width/height, overflow region, positioning, trap or scroll lock. The fixed dimensions belong only to switches. Standalone retains24px inner padding,16px at narrow viewport.

If Shield's container correction moves the content inset into its slot, identify that specific conflict before the joint pass; Consent can remove its mounted inset as an owned follow-up. Do not silently double the padding. No general Contract2 approval is required. This prepares Aubrey's relay; delivery/acknowledgment by another chat is not claimed. Shield source and Accessibility Statement dates were not edited.

## Actual checks and evidence

`node tests/controls-visual.cjs --baseline` reproduces accepted styles. `node tests/controls-visual.cjs` uses the existing Shield fixture and actual controllers with a task-scoped Chrome/CDP driver (no package installation or general framework). **80 focused after assertions passed**, with no browser script errors. Results/screenshots are outside Git under parent `controls-polish-20261106`.

- Desktop1280×1000; mobile-width390/320×844. Full-width card descriptions, heading/switch nonoverlap, switch dimensions, action size/reachability and visible tabs at scrolled actions.
- Real tab transitions preserve one controls root. Keyboard focus and visible action outline; pointer actually reaches Save/Reject/Cancel; settled hover/disabled colors/borders/cursors checked.
- Synthetic 200% text enlargement wraps without collision; Save remains reachable by the host's single scroll region. No nested Consent scrolling.
- Actual Chrome page-scale2 screenshot captured. **This is not desktop Ctrl-plus/menu zoom**, which was not exercised for this candidate; prior accepted host zoom evidence is retained, not counted as a fresh candidate pass.
- Pending/failed and Retry presentation use the existing status function in the isolated fixture. Retry/Save/Reject were never invoked. All visual operations left local stored state unchanged and attempted zero capture/retry requests. No real consent evidence was created.
- Host-unavailable standalone fallback restores the same styled controls; screenshot inspected. Before/after category, action, narrow/text200 and feedback screenshots inspected locally.
- Main PHP lint, visual test syntax, exact CSS/version-only source comparison and production-JS identity passed. Six package/source/committed payload hashes verified. Existing functional/authority acceptance is carried forward; no repeated Ads/withdrawal/integration suite.

Important test-tool corrections: screenshots wait for paint/instant scrolling; the nonmodal host is reopened after Tab legitimately dismisses it; hover/disabled styles wait for their existing160ms transition. Secondary hover/disabled states are distinguished by border/text as well as background. These did not require controller changes and failed attempts are not counted as passes.

Selected files: `before-390-categories.png`, `after-390-categories.png`, `before-320-actions.png`, `after-320-actions.png`, `after-320-text200-actions.png`, `after-320-pending.png`, `after-320-failed.png`, `after-320-standalone.png`; corresponding desktop390/320 top/category/action views are retained. Results include exact accepted host JS/CSS hashes and computed styles. This is local paired-source evidence, not current Alma/theme or corrected-host acceptance, screen-reader speech, cache-HIT or certification.

All task Chrome workers closed. Synthetic local Chrome-profile remnants are retained privately outside source; ordinary cleanup encountered read-only files and automatic review rejected forced recursive cleanup as “blocked by policy.” No alternative deletion route/ACL change was attempted. Consent Codex owns retained local artifacts/profile remnants. No server resource or lock was acquired.

## Candidate and bounded joint handoff

ZIP: parent `deliverables/candidates/siteengine-consent-0.6.5-rc.1.zip`, **51,136 bytes**.
SHA-256: `0dd340d791cb8518b93523f4d56f793032ebfe4bd2a4cdc93a37ab77700fe365`.
Six-member hashes: PACKAGE-MANIFEST.json. Accepted0.6.4 ZIP/delivery/rollback remain unchanged, SHA-256 `e09709997aa6f612f01148e083b0d523d63180aa4f48a975e91d6374e141c02a`.

Shield Codex remains sole joint Alma executor. Coordinate its exact corrected-host source/ZIP pin with this Consent candidate before installation; none is guessed here. Under existing locks perform one focused paired visual pass for container/padding, desktop/mobile/enlarged text/available native zoom, tab/focus/reachable actions, no nested scrolling and standalone/feedback. Use synthetic existing feedback fixtures, with capture/Retry/Save/Reject network writes blocked; this styling assignment creates no consent choice or submission. Preserve all settings, policy/banner, receipts/links/heads/barriers, Shield ownership and prior passing evidence. Repeat only demonstrated affected checks.

Return exact pair parity, screenshots, bounded preservation/resource disposition and positive lock release through Aubrey. Targeted rollback uses the accepted0.6.4 plugin files under locks, never an older database or barrier reset. Helpers decide successor acceptance separately. Do not replace accepted delivery, create public Stable releases, touch client sites or begin Milestone4.
