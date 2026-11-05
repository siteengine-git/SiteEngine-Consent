# Stable 0.6.3 — Helper accepted and installed

Helper accepted candidate3a9fc67243edb115012f514dac9fae63207d8582 / ZIP6e8c4e27b15e1a549615f2d34cd6cd55e7b123b1034c8654eaff26a0e0568f91. Stable0.6.3 changes only two main release-version strings and readme release labeling; the four other packaged files are byte-identical. Completed candidate functional evidence and the coverage limits below are carried forward, not rerun as a new campaign.

Final ZIP siteengine-consent-0.6.3.zip,45274 bytes, SHA256 **fbd0ebc9c63f37c088a3fb9f341297fcbb80ed30f912fbb89cd1afd3c45db737**. Final six installed/source/archive hashes, CLI/HTTPS version0.6.3, effective non-version configuration/policy/banner, all receipt/event/head/barrier data and protected activation/Ads settings verified. Consent option delta: version only; privacy transients unchanged; cron digest changed during ordinary bootstrap with no explicit cron write or exact attribution. No new event, fixture, form/submission, dispatch or client deployment. Both existing executor locks positively released; targeted recovery evidence retained privately. Runtime pin: v0.6.3. Publication/delivery completion is recorded separately from installation below in STATUS.md.

Historical candidate holds below are superseded by this explicit Helper acceptance and stable release assignment. The implementation/integration examples, recovery boundaries and precise coverage limits remain applicable.

## Retained candidate implementation and evidence

# Milestone 3 — persistent preferences candidate

Version: **0.6.3-rc.2**, based on accepted v0.6.2 (`ca46d81ce016c451a8be9377a9510b0932d2e9c6`). Milestones 1–2 evidence is carried forward. Candidate implemented, packaged, installed on Alma and focused-verified; **not Helper-accepted or released**. The accepted delivery ZIP remains 0.6.2.

Visitors now have a fixed **Consent Preferences** button after acceptance or rejection. It uses the existing manage/openPreferences path. Opening, Cancel and Escape do not persist a choice. Save Preferences and Reject Non-Essential retain the existing capture/acknowledgment implementation. Independent category switches expose visible On/Off/Always on text, accessible names/descriptions and checked states. Confirmation feedback distinguishes local application from pending/failed server confirmation and retains the existing same-event retry.

The modal focuses its heading on entry, contains keyboard focus, isolates background content using inert/aria-hidden, restores preexisting background/scroll state, and returns focus to its initiating control. A hidden banner is excluded from keyboard navigation. Buttons wrap at narrow widths; the dialog scrolls within the dynamic viewport, and safe-area spacing is preserved. Existing focus rings remain visible. This is focused keyboard/accessibility evidence, not screen-reader or WCAG certification.

## Footer, menu and placement integration

A footer can provide:

```html
<button type="button" data-se-consent-action="manage"
        data-se-consent-persistent aria-haspopup="dialog"
        aria-controls="se-consent-modal">Consent Preferences</button>
```

For a WordPress menu link, use URL `#se-consent-modal` and CSS class `se-consent-preferences`. The delegated click handler prevents navigation and opens the same modal. An explicit persistent marker or menu class present at initial render suppresses the built-in button. Existing manage hooks without the explicit persistent marker keep working and do not suppress the default automatically. For controls inserted later, explicitly select custom mode and ensure the replacement remains reachable on every applicable page. Existing `window.SiteEngineConsent.openPreferences()` remains supported and remembers the active initiator.

Presentation-only filter (no new stored setting or target system):

```php
add_filter('siteengine_consent_preference_controls', function () {
    return ['mode' => 'auto', 'side' => 'right', 'bottom' => 120];
});
```

`mode`: auto/default or custom (disable built-in); `side`: right/default or left; `bottom`: clearance in CSS pixels, clamped 8–320, default88. Safe-area insets are added. Invalid values fall back safely. Presentation options are excluded from consent-notice identity. There is no automatic cross-plugin collision detector; choose clearance appropriate to the site. No other-plugin or theme edits were made.

## Verification actually performed

- 30 focused JavaScript UI assertions reuse the retained browser transport/storage double: launcher after acceptance/rejection; focus entry, forward Tab wrap and Escape return; exact background state restoration; no choice/request on open/close; pending/failed feedback; explicit/custom duplicate suppression; side/clearance clamp; safe-area/dynamic viewport source support. This isolated DOM model is not a browser layout test.
- 7 PHP presentation configuration checks; 41 source preservation checks against v0.6.2; 35 unchanged JavaScript function-region comparisons. All non-frontend preexisting PHP methods, authority/continuity/uninstall hashes, policy/banner defaults, and non-UI JavaScript function regions are unchanged. Three affected PHP files lint; embedded JS compiles. Commands below. The retained unchanged Ads/withdrawal/browser fault suites were not rerun.
- One actual installed in-app Chromium browser pass: inherited acknowledged synthetic grant reopened without renewal; modal heading focus; Enter/Space, forward/backward Tab edges, Escape and Cancel focus return; named independent switches and visible focus. Background disappears from the accessibility tree while modal is open and returns afterward.
- Desktop layout at1365×900, ordinary800×900 screenshots, mobile390×844 and narrow320×640; narrow controls and all actions reachable by scrolling/keyboard. Site Shield launcher has no intersection with Consent at mobile size; its open panel also allows opening Consent without obscuring the active modal. No Site Shield setting/source change.
- Exactly two real capture interactions in the existing owned synthetic browser chain: rejection, then Save Preferences with advertising on and analytics/personalization off. Pending feedback observed; final confirmation state saved; reload preserves independent choices. Real persisted rows, validated immutable links, Google mappings and API2 current eligibility agree. For this readback only, form-id-zero stamps were assembled and signed in memory from those real persisted events; no new business submission or persisted form stamp was created. Prior actual Fluent Forms acceptance is retained from Milestone2.
- Before any new choice, upgrade and repeated open/close left every Consent row/head/barrier unchanged. After two choices, all original immutable rows and unrelated heads remain unchanged; only the owned head advanced by2 with monotonic barriers. Earlier signed synthetic form responses remain byte-identical, the unacknowledged entry remains negative, and previously granted entries remain withdrawn. No historical repair, barrier reset, export, notification or advertising delivery.
- All six installed/source/archive payload members match. Effective non-version configuration, policy/banner identity, activation and Ads configuration unchanged. Only Consent option delta: version0.6.2→0.6.3-rc.1→0.6.3-rc.2. Privacy transients unchanged. Cron digest changed during ordinary bootstrap/verification; no explicit cron mutation or claim of exact attribution. Schema structure unchanged; normal receipt AUTO_INCREMENT advanced for the two owned interactions.
- Owned fixture unpublished with positive readback; exact audit/history retained; both common/overlapping locks positively released; test tab closed and viewport reset. No temporary authentication account/session created.

The fixture-create reporting line initially referenced a nonexistent convenience API, after the successful write. The ownership journal was inspected and the page read back without replaying creation. A later readback initially compared the effective banner hash with its static seed and compared AUTO_INCREMENT state as schema structure; assertions were corrected against the actual implementation/metadata and the existing captures rechecked without creating more choices. These were tooling corrections, not product defects or failed installed behavior hidden as passes.

Final feedback correction: the initial rc.1 pass is carried forward. Source review found the floating failure panel could occupy the persistent launcher area at narrow widths. Final rc.2 reserves launcher clearance, hides the inert floating panel while the modal is open, and presents pending/failed status inside the dialog. Two isolated assertions were added; affected opening/focus/desktop/mobile views were rechecked on the installed final candidate via the owned draft preview, with no additional captures. All receipt/event/head data remained byte-identical during this successor installation. The failed-state layout was not newly forced in an installed browser. The local-file screenshot attempt was blocked by browser URL policy and was not bypassed or counted as a test.

## Coverage limits

The connected in-app browser did not apply the browser zoom shortcut, so actual browser zoom remains **unverified**, separately from successful320px reflow. No screen-reader session, physical mobile safe-area device, other browser/version matrix, or newly forced installed storage/network failure is claimed. Pending/failed UI changes are covered by isolated fixtures; unchanged retry/order/security behavior carries its prior evidence. Minimum PHP7.4/WordPress5.8 are preserved declarations, not newly executed environments. No new Ads dispatch acceptance or business-form campaign was run.

## Upgrade and targeted recovery

Install the candidate normally in place; do not uninstall or deactivate shared dependencies. Its six members are pinned in PACKAGE-MANIFEST.json. The only functional payload change is frontend/configuration output in the main file; readme/release metadata are coherent. No migration, new preference storage, consent-notice renewal, retention or authority change. Default policy stays2026-05-08-v1, static banner seed0.6.1-rc.2; effective banner material/hash is unchanged.

If a regression requires recovery, acquire the existing execution lock, compare installed candidate hashes with its manifest, and restore only the six targeted accepted0.6.2 plugin files from the retained owner-private file backup (or verified accepted ZIP), with main loaded last. Verify the restored payload and version bootstrap. Preserve every current database event/link/head/barrier and browser choice; never restore the old database/options snapshot over newer events, run uninstall, reset barriers or repair historical grants. Normal older-version bootstrap may change the plugin version option. Reconcile any later file drift before a recovery write. Recovery has not been executed because no demonstrated runtime defect required it.

Focused local commands (from source, PHP executable available in the existing sibling local test runtime):

```text
node tests/preferences.cjs
php -n tests/preferences-config.php
php -n tests/preferences-upgrade.php <retained-v0.6.2-main-file>
php -n -l siteengine-consent.php
php -n -l tests/preferences-config.php
php -n -l tests/preferences-upgrade.php
```

Candidate is staged outside the authoritative installation directory. Keep its existing accepted0.6.2 ZIP unchanged until Helper acceptance. Next task: Helper reviews this candidate and the explicit zoom/screen-reader coverage limits; no Milestone4 work is authorized by this handback.
