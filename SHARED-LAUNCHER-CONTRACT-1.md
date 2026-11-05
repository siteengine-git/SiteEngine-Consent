# Shared Launcher Contract 1 — PROPOSED

**Status: proposed, unimplemented, awaiting both Helpers' agreement.** Documentation only, November 5, 2026. This is an optional, fixed Consent/Shield presentation handshake, not an extensible widget registry or consent authority. No runtime, package, installation, release or delivery change is authorized by this document.

## Verified starting point and review incorporated

Consent baseline: accepted [v0.6.3 / b7142169ae6d1f5ac8f9d32b9312f7ebe8fe207c](https://github.com/siteengine-git/SiteEngine-Consent/tree/v0.6.3). Canonical checkout: `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent\source`.

In [siteengine-consent.php](https://github.com/siteengine-git/SiteEngine-Consent/blob/v0.6.3/siteengine-consent.php): `window.SiteEngineConsent.openPreferences = openModal` directly exposes `openModal(initiator)` (line1188). It remembers an explicitly supplied focus-capable element, opens the existing modal, isolates background/scroll and focuses the heading (line1052). `closeModal()` removes modal visibility, restores background/scroll with `isolateModal(false)`, then returns focus (line1068). The current return is `undefined`, including already-open/missing-DOM early returns. Current fallback checks only connectedness/size and does not reliably handle a removed/disabled/inert target. No registration, revocable acknowledgment or lifecycle API below exists in0.6.3.

Current explicit custom/footer behavior (line1111): mode`custom`, or an initial `[data-se-consent-persistent]` / `.se-consent-preferences` site control, prevents creation of the built-in launcher. Banner visibility separately hides the built-in launcher (`syncLauncher`, line1019). Preserve these contracts.

Read-only Shield inspection: accepted RC3 implementation `876cad5093b0db892578cd3af0673af982e037ee`, `src/se-site-shield/assets/js/widget.js`, lines195–220. The inspected checkout HEAD`12830b3d817b68dd86ce0ec27ef38b44ff52fde8` has the same widget.js bytes as that accepted implementation. Shield's panel is nonmodal; its private `dismiss(restoreFocus)` synchronously hides the panel, collapses the launcher and optionally focuses it. There is no public shared host API. The existing banner-clearance observer is unrelated; this proposal adds no broad observer.

Incorporates Shield Helper's review relayed to Consent Helper on November5: Shield owns persistent launcher/panel/placement and rendered action; Consent owns banner/modal/focus containment/choices/evidence/standalone fallback. Use **Accessibility & Privacy** only after a usable Consent action is acknowledged, otherwise **Accessibility options**. The panel action is **Consent Preferences**. Returning to a panel button hidden by dismissal is forbidden. The exact interfaces below still require agreement; architectural support is not implementation approval.

## Proposed public surface (all new except openPreferences)

One action, one active registration, current document only. Exact integer `contractVersion === 1`; no version range or plugin-activation inference. Both sides preserve other namespace members. All declarations below are **PROPOSED**:

```ts
type OpenResult =
  | { ok: true; reason: 'opened' | 'already_open' }
  | { ok: false; reason: 'unavailable' | 'invalid_return_target' | 'open_failed' };

type PreferencesAction = Readonly<{
  contractVersion: 1;
  id: 'consent-preferences';
  label: string; // Consent-owned localized "Consent Preferences".
  isOpen(): boolean; // Presentation only. Does not bootstrap, capture or mutate choices.
  open(initiator: HTMLElement): OpenResult;
}>;

type SharedState =
  | { state: 'usable'; launcher: HTMLElement; control: HTMLButtonElement }
  | { state: 'unavailable'; reason:
      'pending' | 'disabled' | 'removed' | 'incompatible' | 'replaced' |
      'open_failed' | 'released' | 'provider_unavailable' };

type RegistrationResult =
  | { accepted: false; reason: 'incompatible' | 'unavailable' }
  | { accepted: true;
      release(reason: 'released' | 'replaced' | 'provider_unavailable'): void };

type SharedLauncherHost = Readonly<{
  contractVersion: 1;
  register(action: PreferencesAction,
           onState: (state: SharedState) => void): RegistrationResult;
}>;

// Provider-owned current values, null when unavailable. No pre-init placeholders needed.
// window.SiteEngineConsent.preferencesAction: PreferencesAction | null
// window.SiteEngineShield.sharedLauncher: SharedLauncherHost | null
```

`register` returns synchronously. `accepted:true` is registration acceptance only, **never permission to hide Consent's button**. Initial callbacks are queued in a microtask after `register` returns; pending may precede usable. Rejection returns the false union and never invokes the callback. Same active `(host, action)` is bound once; Consent's reconciliation is idempotent. A different accepted registration replaces the old one: notify old `unavailable/replaced`, invalidate its click handlers/queued callbacks, then render/acknowledge the new one. Invalid registration attempts (including non-string/empty label or non-function callback) do not evict a valid registration. If registration throws after partial rendering, Shield removes that partial action/restores its label before returning control.

`release` is idempotent, synchronous, returns `undefined`, cancels pending usable callbacks and removes only that registration's action. A stale handle cannot revoke its replacement. Later callbacks always belong to their original registration; Consent ignores stale callbacks using an attempt-object identity check (no shared tokens/storage/IDs required). Callback exceptions must not escape host teardown or prevent releasing the action; invalidate it and use the normal unavailable/fallback path.

## Proposed notifications and either initialization order

Use `window.dispatchEvent(new CustomEvent(name, {detail: ...}))`. These are same-document presentation notifications, not persisted messages:

| Name | Exact detail | When |
|---|---|---|
| `se-consent:preferences-action` | `{contractVersion: 1}` | After publishing/replacing/nulling `SiteEngineConsent.preferencesAction`. |
| `se-shield:shared-launcher` | `{contractVersion: 1}` | After publishing/replacing/nulling `SiteEngineShield.sharedLauncher`. |
| `se-consent:preferences-dialog` | `{contractVersion: 1, state: 'opened' \| 'closed' \| 'open_failed'}` | After the corresponding completed Consent operation, as specified below. |

Notification return value is ignored; none is cancelable authority. Read the **current global** after a notification; do not trust an older event payload as the current registration. Unsupported versions/properties/functions fail without suppressing fallback.

Each plugin installs its peer-event listener **before** probing current globals. Consent publishes a descriptor only after render has attached the modal/backdrop and bound its existing controls; the global0.6.3 API existing earlier is insufficient readiness. Shield publishes a host only after its persistent launcher/panel and handlers are initialized and usable. Both announce after publication and immediately reconcile current values. DOMContentLoaded is a one-shot initialization boundary if needed, not a repeated timer. Thus Consent-first, Shield-first and either plugin appearing after DOMContentLoaded work without an event backlog, polling or a shared boot service.

On controlled disable/teardown, announce null/revoke **before** removing or disabling shared controls. On re-enable or replacement, publish a new object and acknowledge afresh; never reuse an obsolete lease. On `pageshow` (including BFCache) reconcile once against current globals/owned DOM. The single `isOpen()` query closes a specific missed-notification race: if Shield initializes while Consent is already open, it must remain pending and wait for `closed`; it must not acknowledge/reopen a competing panel. No polling of `isOpen`.

## Positive acknowledgment and temporary suppression

Shield sends `onState({state:'usable', launcher, control})` only after all are true:

- The native button is attached, labeled with the registered action label, enabled, and its keyboard/click handoff is bound to **this** action/registration. It is reachable when Shield's normally closed panel opens; hidden-with-closed-panel is expected, not a failed acknowledgment.
- The persistent launcher is attached to this document, outside the panel, enabled, visible, not inert/hidden/aria-disabled, named **Accessibility & Privacy**, and keyboard-operable using Shield's existing semantics. Shared panel naming/action placement must make both accessibility and privacy discoverable without changing Shield's placement settings.
- The host/descriptor are still current, `action.isOpen()` is false, and the panel-close completion required below is supported. Consent can verify the supplied DOM references; it does not open the panel or create a choice as an acknowledgment test.

Consent validates that positive state before setting a private `sharedLauncherUsable` flag. Failed/throwing registration, no acknowledgment, pending, unsupported version, null/replaced provider/host, rejected DOM references, any unavailable callback or failed opening clears the flag and reconciles fallback. Registration alone, a plugin being active, DOM selector detection or an accessible label alone never suppresses it.

Suppression affects **only** the built-in persistent launcher, retained in DOM when auto mode owns it. Its effective hidden state is `bannerVisible || sharedLauncherUsable`. Explicit custom/footer mode remains a distinct condition that may mean no built-in exists; clearing a shared lease must not change that mode, manufacture duplicate controls or hide/remove footer/menu controls. Shared action markup must **not** use `data-se-consent-persistent` or `.se-consent-preferences`, which would accidentally trigger permanent initial custom suppression. Shield binds its own action handler to the registered callback. The initial banner, Manage Preferences and all consent behavior stay independent.

Shared loss restores the auto launcher immediately according to the existing banner rule. If the banner is visible, its existing Manage Preferences is the entry point. In explicit custom mode the site's own footer/menu integration remains responsible for persistent discoverability; shared fallback must not override the site's deliberate choice.

## Handoff, opening result and focus return

1. Shield captures the **persistent launcher**, never the panel action button, as `initiator`. It guards against duplicate activation and validates that its registration/descriptor and launcher are current.
2. Shield closes its own panel through its owning private dismiss path. Finish visibility, aria-expanded, focus and any scroll/transition restoration **before** calling Consent. Current RC3 dismissal is synchronous and owns no modal body scroll lock. If its implementation later becomes asynchronous, use that operation's explicit completion Promise; do not substitute a delay/animation guess. A failed/cancelled close revokes usability with `open_failed`; do not open Consent over the unfinished panel.
3. Recheck registration identity and launcher usability after that completion (including after an await). Call `action.open(launcher)` once. The Consent adapter validates availability/target and calls the **existing** `window.SiteEngineConsent.openPreferences(launcher)`; no duplicate opening function, modal or processing path. It verifies its own modal/focus state before returning `opened`. If already open, return `already_open` without changing its existing return target, focus or state. The legacy public method retains its signature and existing return behavior.
4. `ok:true` concerns dialog presentation only. Emit `opened` only for an actual transition, after background/scroll isolation and focus entry complete. `already_open` emits no duplicate opened notification. A false result or exception clears shared suppression, revokes Shield usability with `open_failed`, restores any partially changed Consent modal/background/scroll through the existing close/restore path, and exposes fallback. Emit `open_failed` only after cleanup; do not report a successful close/open. Shield restores its normal label, removes/disables the broken action and provides a concise failure notice directing the visitor to the available Consent control. Do not automatically retry/reopen or save anything.
Lifecycle emission belongs in the existing `openModal`/`closeModal` paths so footer/menu/direct API openings are covered too; the shared adapter must not duplicate those notifications. Failure cleanup must restore the saved background/scroll even if its modal/backdrop DOM was removed.

5. On every actual modal close (Escape, Cancel or existing choice actions), Consent first restores modal visibility/aria, exact prior background inert/aria state and body scroll. **Then** validate/focus the stored initiator. Emit `closed` only after cleanup and focus resolution; no reason/category/choice/receipt is included. Closing by itself never records a choice. Existing explicit Save/Reject actions retain their existing processing; notifications must not disclose which action closed it.

Usable focus targets must be connected to this document, rendered with a nonzero rectangle, keyboard-focusable, not disabled/aria-disabled/hidden/inert (including ancestors), and have visible computed visibility/display. Validate after restoring Consent's own background isolation: the shared launcher being temporarily inert **because Consent is open** is expected and must not itself revoke acknowledgment. Shield must not re-open its panel or reclaim focus while Consent is open.

If the supplied launcher disappears or is unusable **before handoff**, fail with `invalid_return_target` and restore fallback. If it disappears during the modal, keep the modal usable and finish ordinary cleanup; clear the stale shared lease and focus the visible auto Consent launcher, otherwise the banner Manage control, otherwise a visible supported explicit footer/menu control. If none exists, use the main heading/main landmark or body with a temporary `tabindex=-1`, restore the prior tabindex after focusing, and do not focus a hidden/disabled element or leave focus in a removed dialog. Never unhide an explicit custom control or undo another component's legitimate inert state to force focus. Focus with preventScroll where supported after scroll restoration; do not restore a stale document scroll snapshot after Consent has opened.

Shield must report its own removal/disable/replacement before DOM mutation and cancel obsolete handlers/continuations. Both plugins revalidate on pageshow, each handoff and modal return. Contract1 does **not** claim immediate detection of arbitrary unannounced third-party DOM/CSS tampering while idle; neither owner can promise that without observation. If a concrete supported lifecycle requires that stronger guarantee, bring a narrowly scoped observation change back for agreement. No polling or new broad DOM observer is part of this proposal.

## Minimal Consent example — proposed implementation sketch

Local helpers below are future Consent-owned glue, **not additional public APIs**. `reconcileOwnLauncher` applies the independent custom/banner/shared conditions above; `openViaExistingModal` is only the checked adapter to `openPreferences`, with the specified cleanup/results/events. Its close/fallback work extends the existing return path.

```js
// After Consent render succeeds; all new names in this example are PROPOSED.
let attempt = null;
const action = Object.freeze({
  contractVersion: 1, id: 'consent-preferences', label: 'Consent Preferences',
  isOpen: () => modalIsOpen(),
  open: launcher => openViaExistingModal(launcher) // calls openPreferences(launcher)
});
function reconcileShared() {
  const host = window.SiteEngineShield?.sharedLauncher;
  if (attempt?.host === host && attempt?.action === action) {
    const state = attempt.state;
    const usable = state?.state === 'usable' && (modalIsOpen()
      ? state.launcher.isConnected : validateSharedDom(state));
    reconcileOwnLauncher(usable); // pageshow validates; own modal's inert is expected
    return;
  }
  const old = attempt; attempt = null;
  reconcileOwnLauncher(false); // shared flag only; never override explicit custom mode
  old?.registration?.release('replaced');
  if (window.SiteEngineConsent.preferencesAction !== action ||
      host?.contractVersion !== 1 || typeof host.register !== 'function') return;
  const next = { host, action, registration: null }; attempt = next;
  try {
    next.registration = host.register(action, state => {
      if (attempt !== next) return; // ignore stale acknowledgment
      next.state = state;
      const usable = state.state === 'usable' && validateSharedDom(state);
      reconcileOwnLauncher(usable);
      if (state.state === 'usable' && !usable) {
        attempt = null; next.registration?.release('released');
      }
    });
    if (!next.registration.accepted) { attempt = null; reconcileOwnLauncher(false); }
  } catch (_) { attempt = null; reconcileOwnLauncher(false); }
}
window.addEventListener('se-shield:shared-launcher', reconcileShared);
window.addEventListener('pageshow', reconcileShared);
window.SiteEngineConsent.preferencesAction = action;
window.dispatchEvent(new CustomEvent('se-consent:preferences-action',
  { detail: { contractVersion: 1 } }));
reconcileShared(); // listener first, then current-global probe
// Owned teardown: null descriptor, invalidate/release attempt, restore own launcher,
// then announce se-consent:preferences-action before removing any owned DOM.
```

The private adapter additionally clears suppression on opening failure, independent of Shield returning a notification. Its focus-return validation is performed after existing isolation has been restored, not while the target is intentionally inert.

## Minimal Shield example — proposed implementation sketch

Run in Shield's initialized launcher/panel closure. `renderConsentAction` creates only a native panel button. `revoke` cancels the current registration, queued acknowledgment and click continuation, notifies unavailable, then removes its action/restores the normal label. `dismissForConsent` is the completion boundary around existing `dismiss(true)`, including any restoration Shield actually owns; it resolves true on completion or false on failure. These are private helpers, not new cross-plugin APIs.

```js
// After Shield launcher/panel/handlers exist; all new public names are PROPOSED.
let current = null;
const host = Object.freeze({
  contractVersion: 1,
  register(action, onState) {
    if (action?.contractVersion !== 1 || action.id !== 'consent-preferences' ||
        typeof action.open !== 'function' || typeof action.isOpen !== 'function')
      return { accepted: false, reason: 'incompatible' };
    if (!shieldControlsUsable()) return { accepted: false, reason: 'unavailable' };
    if (current) revoke(current, 'replaced');
    const record = { action, onState, active: true }; current = record;
    const button = renderConsentAction(action.label);
    button.addEventListener('click', async () => {
      if (!record.active || record.busy) return;
      record.busy = true;
      try {
        if (!await dismissForConsent() || !record.active || current !== record ||
            window.SiteEngineConsent?.preferencesAction !== action) {
          if (record.active) revoke(record, 'open_failed'); return;
        }
        const result = action.open(launcher); // persistent launcher, NOT button
        if (!result?.ok) revoke(record, 'open_failed');
      } catch (_) { revoke(record, 'open_failed'); }
      finally { record.busy = false; }
    });
    queueMicrotask(() => acknowledgeIfCurrent(record, button));
    // acknowledgeIfCurrent also runs on dialog closed; waits while action.isOpen().
    return { accepted: true, release: reason => revoke(record, reason) };
  }
});
window.addEventListener('se-consent:preferences-dialog', event => {
  if (event.detail?.contractVersion !== 1 || !current) return;
  if (event.detail.state === 'closed') acknowledgeIfCurrent(current);
});
window.SiteEngineShield = window.SiteEngineShield || {};
window.SiteEngineShield.sharedLauncher = host;
window.dispatchEvent(new CustomEvent('se-shield:shared-launcher',
  { detail: { contractVersion: 1 } }));
// Consent's listener/probe performs registration regardless of which plugin was first.
// On provider event, revoke if the current descriptor changes/nulls. On pageshow,
// validate current DOM. Host teardown revokes, nulls host and announces before removal.
```

`acknowledgeIfCurrent` retains its registration's button reference, checks current globals/DOM and modal state, sets the shared accessible name, then calls the positive callback. If already acknowledged and Consent's modal is open, retain the lease through Consent's intentional isolation; do not emit a fresh acknowledgment or inspect its temporary inert state as a teardown. Host teardown and replacement still revoke while the modal is open. Both sketches omit ordinary local DOM/error helpers; the signatures, timing, results and failure requirements above are normative, not optional omissions.

## Short verification checklist for later authorized implementation

- Consent-first, Shield-first, each after DOMContentLoaded, late host/provider, BFCache pageshow and Shield appearing during an already-open Consent modal; no missed readiness, duplicate registration/control or polling.
- Registration rejection, unsupported version, pending acknowledgment, valid rendered/usable acknowledgment, revoke/disable/remove/re-enable and replacement. Old callbacks/releases/awaited clicks cannot suppress or open a replacement. Controlled teardown restores auto fallback before removal; third-party unannounced tampering limitation disclosed.
- Explicit custom mode and initial footer/menu marker remain independent; footer/menu actions still work; initial banner and its Manage action are never suppressed by this handshake.
- Shield panel fully dismissed and focus/scroll restored before existing Consent opener runs. Modal heading focus, keyboard containment/Escape/Cancel, background/scroll restoration then persistent-launcher return. Test removed/disabled/hidden target before and during open and the ordered fallback without forcing other components' state.
- Missing modal, opener exception/partial failure and concurrent activation: false result/cleanup/notification, usable fallback, no competing panel, no hidden return target or automatic retry.
- Shared label **Accessibility & Privacy** only while acknowledged; ordinary **Accessibility options** otherwise. Both actions discoverable/keyboard-usable at narrow widths/zoom and with relevant existing Shield adjustments. No new screen-reader/WCAG certification claim.
- Snapshot consent cookies/storage/event and form-authority state plus counters/requests around opening, Cancel/Escape and notifications: zero consent choice/capture/receipt/evidence mutations. Explicit Save/Reject keep their existing separate behavior; no current-eligibility/receipt lookup is needed by the interface.

Documentation-task validation is source inspection, example syntax and unchanged runtime/package hashes only. None of these proposed lifecycle tests has been implemented or run against an installed shared interface. Registration carries version/labels/functions/DOM references/presentation status only: no consent values, cookies, acknowledgments, receipts, signatures, continuity material, submission content or other secrets. It never grants permission to export or deliver anything.
