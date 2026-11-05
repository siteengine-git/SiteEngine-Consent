# Shared panel contract 2 — proposed for owner agreement

November 5, 2026. **Proposed, not implemented or agreed.** Prepared by Consent Codex for exchange through the Helpers/Aubrey before either owner wires the implementations. This is a narrow replacement for the Contract 1 modal handoff, not an extension that silently changes its meaning. Accepted Shield RC4 / Consent 0.6.4-rc.1 and their rollback ZIPs remain unchanged.

## Feasibility from accepted source

Consent source `aaf13f3878a8db0b20935da2e6dcd4d221177391`, `siteengine-consent.php`: `render` creates one `.se-consent-modal-inner` containing categories, explicit actions and the status node. `bindEvents` delegates clicks at document level. Toggle getters/setters and `modalConfirmation` find the existing nodes by attributes/IDs; moving the same subtree preserves those bindings. `saveConsent` invokes `closeModal` after the existing persistence/dispatch sequence. Focus containment, Escape, background isolation and body overflow changes are separate and gated by `modalIsOpen`/`modalIsolationActive`.

Shield RC4 source `7fae4bc682887de050a283b04235a56a7212bb07`, `assets/js/widget.js`: its general launcher opens a nonmodal panel, `dismiss` hides it and returns focus, and its broker action currently dismisses the panel before opening Consent's separate modal. The separate-modal handoff must be replaced. No capture, identity, persistence or authority redesign is required.

Smallest Consent change: retain the original dialog/backdrop for fallback, move its **single existing inner subtree** into a Shield-owned slot, route close through the host when mounted, and keep the original dialog hidden. Never clone controls or call `openModal` while mounted. Delegate mounting and closing to presentation helpers only; keep category/save/reject/retry and all authority code intact. Add the existing Retry confirmation action to the mounted status area so retry is reachable without closing the host or relying on a separate banner. Preserve same-event retry semantics.

## Proposed exact browser interface

Use the existing `window.SEShieldSharedLauncher` global with **contractVersion: 2**. Contract 1 is incompatible with mounted presentation; the new Consent candidate must use standalone UI with an old broker, and the new Shield candidate must retain Accessibility without a compatible provider. Do not run both handoffs or suppress fallback on registration alone.

```ts
type View = 'closed' | 'inactive' | 'active';
type CloseReason = 'cancel' | 'saved';
type HostActions = {
  close(reason: CloseReason): boolean;
  invalidate(): void;
};
type ConsentProvider = {
  id: 'siteengine-consent'; contractVersion: 2;
  isReady(): boolean;
  mount(slot: HTMLElement, actions: HostActions): boolean;
  setView(view: View): boolean;
  unmount(): void;
};
type Registration = {
  readonly registrationId: string;
  confirm(): boolean;
  withdraw(reason: string): void;
  openConsent(initiator: HTMLElement): boolean;
};
window.SEShieldSharedLauncher = {
  contractVersion: 2,
  register(provider: ConsentProvider): Registration | null,
  getState(): {
    contractVersion: 2; registrationId: string | null; generation: number;
    status: 'unavailable' | 'registered' | 'ready';
    reason: string; expiresAt: number;
  }
};
```

The identity, current-global, safe/nonregressing generation and monotonic six-second lease checks remain; Shield pushes renewal every two seconds. Events retain `se-shield-shared-launcher-api`, `se-shield-shared-launcher-state` and `se-consent-shared-launcher-provider`, with version 2. Events are discovery signals: readers validate the current global/handle/state, not event payload authority. No consent state, category values, receipts, signatures, secrets or choice events cross this interface.

## Call order, acknowledgment and failure

1. Both plugins subscribe before inspecting the current global. Consent registers only one live provider; either may initialize first. `isReady` means its original root/backdrop and handlers are available, not a choice or grant.
2. On explicit `confirm`, Shield validates its container, connected empty Consent tab slot and launcher. It calls `mount(slot, actions)` once, then `setView('closed')`. Only both literal `true` results permit `ready` acknowledgment and lease publication. Consent suppresses its floating launcher only after matching live ready evidence. Mount has no focus or consent side effects. Provider exceptions/false cause cleanup and no suppression.
3. `mount` accepts only a same-document connected slot with no children, no already-open standalone modal and no different active mount. It moves the existing inner node without cloning; duplicate same-slot mount may return true. It validates callable actions and stores them in memory only. Consent does not acquire scroll/background ownership. The original dialog/backdrop stay hidden.
4. General launcher opens Shield with Accessibility selected and calls `setView('inactive')`. Consent-specific `openPreferences(initiator)` and banner/footer/menu Manage actions call the current handle's `openConsent(initiator)`; Shield opens/selects Consent and calls `setView('active')`. Legacy `openPreferences` still returns undefined. Handle method returns true only after the current mount/tab/container are usable and focus has entered the Consent heading. Shield preserves the supplied usable external initiator for close; the general path uses its persistent launcher.
5. Every tab change calls `setView('active'/'inactive')` synchronously. Hidden tab panels are not focusable. These changes never call save/reject/retry. A session begins on the first closed-to-active/inactive transition; Consent loads the current local preferences once. Same-session tab changes preserve the draft and live acknowledgment feedback.
6. Before every hide/close (Close button, Escape, outside dismissal, explicit Consent Cancel or successful choice), Shield calls `setView('closed')`, restores its own background/scroll state if any, then returns focus. Consent Cancel calls `actions.close('cancel')`; the existing explicit save/reject path calls `actions.close('saved')` after initiating persistence. Both return a boolean so Consent can invalidate on failed host closure. Neither close reason is permission authority. Repeated closed calls are idempotent.
7. **Draft rule:** tab changes preserve unsaved selections; closing discards them, never saves them. Consent displays: “Changes are kept while switching tabs. Closing without saving discards unsaved changes.” Next session reloads current stored state. A successfully saved but pending/failed event is not an unsaved draft and retains its existing local state/retry identity and honest feedback. No delayed acknowledgment retroactively upgrades a form submission.
8. Withdrawal/replacement, host failure/removal, failed view/open or expired lease triggers host cleanup **before** `unmount`: Shield hides its panel/restores its focus/scroll ownership, calls `setView('closed')`, then `unmount`, and invalidates readiness. `unmount` is idempotent and nonthrowing: Consent moves the original inner root back to its retained dialog, removes mounted-only presentation state and restores its launcher. The same root must be recoverable even if the host was detached. Do not delete it with host markup.
9. `actions.invalidate()` requests that exact current registration's cleanup/revocation; stale callbacks cannot affect replacements. On silent host disappearance/lease expiry, Consent unmounts its retained root itself, withdraws, and exposes fallback. A failed Consent-specific open first withdraws/cleans up the shared host, then opens standalone; never open both. Safe fallback focus uses the initiating element if usable, then Consent launcher or a page landmark. Shared host failure must not erase a dirty draft silently: show a local notice that unsaved changes were discarded and require explicit saving again.

The mounted root keeps ID `se-consent-preferences-content`; its entry heading keeps `se-consent-modal-title` with `tabindex=-1`. Shield may focus that heading on direct Consent entry after successful `setView('active')`; `setView` itself never moves focus. Keyboard tab selection keeps focus on the selected tab, and Tab enters its panel normally. A false/throwing mount must restore the original root before returning failure; Shield also invokes idempotent unmount during revocation cleanup. All boundary exceptions are caught by the caller. No host may remove its slot before giving Consent the unmount callback; retained root references and lease expiry cover unexpected detached-host recovery without page-wide polling.

## Shield presentation ownership

One shared container, approximately `400px` wide and bounded by viewport/safe-area clearance. Two immediately visible tabs, Accessibility then Consent. Sticky/non-scrolling tablist above independently scrolling active tab content. General launcher always initially selects Accessibility. Use tablist/tab/tabpanel relationships, aria-selected, roving tabindex, ArrowLeft/Right and Home/End; activation/focus policy is consistent. Shield owns panel entry/return, Escape and any containment; Consent never adds a second trap/isolation/scroll lock in mounted mode. Tabs/Close remain reachable with keyboard and zoom. Initial Consent banner and explicit custom/footer suppression remain independent.

## Required focused verification after agreement

- Both initialization orders, delayed DOM readiness, exact identity/lease and replacement; incompatible/missing host leaves standalone.
- General Accessibility entry and Consent-specific banner/footer/menu entry; one container and exactly one set of category controls.
- Draft survives tab switching, closing discards with visible explanation, save/reject remains explicit; open/tab/close creates zero choices or capture requests.
- Pending/failed feedback and same-event retry remain reachable. Save tests use only owned synthetic fixtures with delivery disabled.
- Keyboard tabs/focus/return/Escape; 400px desktop, narrow/mobile and scrolling with tabs visible; screenshots by joint executor.
- Host failure/removal/expiry restores original controls and standalone safely; no nested modal, competing locks or traps.
- Unchanged policy/banner/API2 and protected receipt/link/head/barrier evidence. Carry prior authority evidence forward.

Consent owns only Consent implementation/package; Shield owns its host/tabs and is the single installed executor. No installation before exact candidate pins are coordinated. Accepted delivery ZIPs stay unchanged until separate successor acceptance. No Stable promotion, client deployment or Milestone 4.
