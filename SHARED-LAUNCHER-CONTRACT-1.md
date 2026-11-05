# Shared Launcher Contract 1 — authoritative broker and Consent adapter

## Authority and supersession

November 5, 2026. The Helper approved **Shield's contract at `eba2e2e085e5da3a60c0bde8c2fa7531e9770238` plus the registration-identity addition below** for this Consent implementation assignment. [Pinned Shield contract](https://github.com/siteengine-git/SiteEngine-Site-Shield/blob/eba2e2e085e5da3a60c0bde8c2fa7531e9770238/docs/SHARED-LAUNCHER-CONTRACT-1.md). The originating document's historical proposed/unimplemented labels do not grant Shield implementation/deployment authority; each owner retains its own assignment.

This explicitly supersedes Consent proposal `dee85e8ca1aeff655b9a656ac8952556ddf814e5`. Its `SiteEngineShield.sharedLauncher`, `SiteEngineConsent.preferencesAction`, callback registration, structured opening results and dialog notification API are obsolete and **not implemented**. There are no compatibility aliases or second contract. Historical proposal bytes remain in Git only.

Baseline: accepted Consent **v0.6.3 / b7142169ae6d1f5ac8f9d32b9312f7ebe8fe207c**. Consent **0.6.4-rc.1 / aaf13f3878a8db0b20935da2e6dcd4d221177391** is now a Helper-accepted compatibility candidate for controlled agency installation, verified with Shield **2.0.0-rc.4**. [Acceptance and delivery record](SHARED-LAUNCHER-ACCEPTANCE.md) carries the installed evidence and limits. Screen-reader speech and cache-HIT interoperability remain untested. This is not a public Stable release.

## Exact registration-identity clarification for Shield

All original Shield contract fields/methods retain their signatures and meanings. Add the following fields, with these exact types/semantics:

```ts
type Registration = {
  readonly registrationId: string; // nonempty opaque identity, fresh for each NEW registration
  confirm(): boolean;
  withdraw(reason: string): void; // idempotent, current registration only
};
type Availability = {
  contractVersion: 1;
  registrationId: string | null;
  generation: number;
  status: 'unavailable' | 'registered' | 'ready';
  reason: string;
  expiresAt: number;
};
```

Shield creates an identity for every new registration; identical active provider registration may return the same handle. A replacement gets a different identity, including after withdrawal/re-registration in the same document. `registered` and `ready` identify the active handle. `unavailable` may identify a retained registration; when none remains its identity is null. Never emit ready with null/empty identity. Old handles cannot confirm, renew, withdraw or open a replacement. Identity is transient presentation state, **not** a credential, visitor identity, consent intent or permission; do not persist it or put provider objects in events.

Consent pins the returned identity rather than following a subsequently mutated handle. Suppression and provider opening require the current broker object, the exact pinned active-handle identity in authoritative state, matching version1, a nonnegative safe-integer generation that has not regressed, a successful explicit confirmation, a valid unexpired lease and its own ready provider. Registration identity and generation are complementary; generation alone never suffices. Stale events do not control state: Consent reads current `getState()`, and old provider closures are bound to their obsolete attempt and return false without changing the replacement.

This pinned addition is returned through Aubrey for Shield Codex to implement identically. Consent has not edited Shield's contract/source or claimed its implementation is present.

## Implemented Consent-facing contract

Shield supplies `window.SEShieldSharedLauncher` with `contractVersion:1`, `register(provider): Registration|null` and `getState():Availability`.

Consent passes an in-memory provider `{id:'siteengine-consent', contractVersion:1, isReady():boolean, open(initiator):boolean}`. `isReady` checks completed render/bind and exact connected modal/backdrop nodes, not choices. `open` calls the existing `window.SiteEngineConsent.openPreferences(initiator)` exactly once and returns true only if the owned modal is open/visible and contains focus. The legacy method still returns undefined. Already-open, stale registration, unusable target, missing readiness, no-op opening and exceptions return false. Narrow partial-open cleanup restores background/scroll even when the modal was removed; no new opening or consent-processing path.

Events are the original Shield contract names:

- Listen to `se-shield-shared-launcher-api` and `se-shield-shared-launcher-state` before looking up the broker.
- Emit `se-consent-shared-launcher-provider` with `{contractVersion:1}` after render and on page resumption. It carries no provider object or consent data.
- Read `getState()` after registration/confirmation to handle synchronous state-event races. Pending registration never suppresses. Repeated API notifications reuse the same active attempt; broker replacement withdraws the previous attempt and registers a fresh provider closure.

The host owns the shared label/button and must finish dismissing its panel and restoring its own focus/scroll before invoking the provider with its persistent launcher. No Consent delegated action/custom marker belongs on that Shield button. Shield must use the boolean provider result, not the legacy API return.

## Lease, lifecycle and fallback

Shield renews ready availability every **2 seconds**, with a monotonic `performance.now()` expiry at most **6 seconds** ahead. Consent validates the authoritative deadline and maintains exactly one replacement expiry timeout; it does not poll APIs or renew the broker itself. Missing monotonic/timer capability never permits suppression. Expiry restores fallback even if Shield has stopped executing; a fresh valid renewal of the still-current registration can restore readiness. This does not claim timers can execute in a halted browser.

Unavailability, identity loss, revocation, exceptions or provider failure clear shared suppression. Revoked registrations require fresh identity/confirmation. `pagehide` withdraws and disconnects the local observer. Hidden documents restore fallback; visibility/pageshow explicitly revalidate/confirm, with fresh registration after pagehide. No BFCache ready snapshot is accepted without that confirmation.

Renewals do not announce status, move focus, recreate controls, query consent, or measure modal layout. The local DOM observer watches only direct body-child changes because Consent mounts its modal/backdrop there; no page subtree observation or general widget framework. Removal/replacement withdraws provider readiness; no new capture/cleanup/background monitoring service.

Consent's own modal isolation making Shield inert/aria-hidden is expected: provider readiness is based on owned render nodes, and renewals preserve that interval without moving focus or removing another owner's inert state. On closing, Consent first restores its exact background/scroll state, then revalidates readiness and the return target. A removed/disabled/hidden/inert target clears stale sharing and uses visible own launcher, banner Manage, reachable custom/footer control, or an appropriately focusable page landmark/body. It never forces focus into a hidden control or undoes another component's legitimate isolation.

The shared flag is separate from explicit custom/footer mode. The initial banner remains independent. Direct footer/menu integration remains supported and is never removed on shared success. A missing/unreachable explicit custom replacement does not prove persistent availability: on restoration Consent retains or lazily creates its own fallback rather than trusting the static mode/marker. Valid custom controls continue to replace the floating default. Custom layout checks occur at UI/fallback boundaries, not on every ready renewal.

## Verification and status

Focused local synthetic-broker/DOM/monotonic-clock checks cover both initialization orders and render readiness; registration identity, generation/version/deadline rejection; stale event payloads, old handles/providers and replacement; pending/confirmation; renewal, expiry, revocation/recovery; visibility/pageshow; failed/no-op/partial opening; own isolation interval; removed/disabled/hidden focus targets; missing custom replacement; UI removal; and zero consent snapshots/requests changed by open/close/failures. Exact results and package pin are in STATUS.md and PACKAGE-MANIFEST.json.

The existing focused UI suite is reused. Source comparison preserves the preexisting non-UI JavaScript functions, PHP methods outside frontend output, policy/banner defaults, and the four unchanged packaged includes/uninstall members. No broad authority/Ads acceptance campaign was rerun.

This is isolated evidence, not actual Shield broker, installed WordPress/browser/cache/Reading Mode or screen-reader acceptance. Joint installed verification remains pending both candidates and its bounded execution assignment. Stable0.6.3 remains accepted/installed; its ZIP, original history and existing coverage limits remain intact. No installation, schema/consent processing change, stable release/delivery promotion, client deployment or Milestone4 occurs in this assignment.
