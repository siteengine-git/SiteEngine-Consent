=== SiteEngine Consent ===
Contributors: siteengine
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.6.4-rc.1
License: Proprietary

First-party consent management for WordPress. Google Consent Mode v2, Fluent Forms integration, local audit log, exports.

== Description ==

SiteEngine Consent is a standalone consent management plugin built for agencies deploying Google Ads and Customer Match across multiple client sites.

Key capabilities:

* Renders a consent banner with Accept All / Manage Preferences / Reject Non-Essential
* Fires Google Consent Mode v2 default and update signals in the correct order
* Captures signed receipt-backed Fluent Forms consent evidence or an explicit negative state
* Maintains a searchable audit log of every consent interaction
* Provides admin UI, WP-CLI, and REST endpoints for exports and monitoring
* Auto-scheduled retention cleanup (default 730 days, configurable)
* Integrates with SiteEngine Base when available; remains fully operational without it
* Administrator-only deployment diagnostics and redacted support reporting

When SiteEngine Base 2.2+ is active, Business Name and Privacy Policy URL are read from SiteEngine → Business Data and the audit screen appears under SiteEngine → Consent. Existing wp-config.php constants remain supported and take precedence for backward compatibility. Without Base, the plugin remains standalone and the audit screen remains under Tools.

On the Consent screen, choose Deployment diagnostics and support report. Checks distinguish verified prerequisites, failures and untested conditions. Generate redacted report requires administrator permissions and a nonce; JSON is displayed for copying, never stored or sent. No consent records, cookies, credentials or signing/continuity material are included. API availability does not prove Ads enforcement or delivery. Capture/current-eligibility explanations are reference material, not an error history.

Upgrade normally without uninstalling. Diagnostics performs no schema repair, capture, cleanup or external dispatch. Existing WordPress/plugin bootstrap can still update version/schema options, cron and privacy transients/probes. The default policy and rc.2 banner identity are preserved; plugin release identity is separate. Preserve all receipt history, links, heads and withdrawal barriers. Missing/incompatible storage or unavailable authority requires targeted investigation; never reset historical data to clear a check. See the source MILESTONE-1.md for upgrade/recovery and verification limits.

== Installation ==

1. Upload the `siteengine-consent` folder to `/wp-content/plugins/`
2. Activate through the WordPress Plugins menu
3. If SiteEngine Base is active, complete Business Name and Privacy Policy URL under SiteEngine → Business Data. Otherwise the plugin uses WordPress defaults, or you may keep legacy wp-config.php overrides:

`define('SE_CONSENT_COMPANY_NAME', 'Your Client Brand Name');`
`define('SE_CONSENT_PRIVACY_URL',  'https://yourclient.com/privacy-policy/');`

4. Verify by visiting the site in incognito — the banner should appear
5. Check SiteEngine → Consent (with Base) or Tools → SiteEngine Consent (standalone) to see the audit log

== Persistent preferences ==

A compact Consent Preferences button remains available after acceptance or rejection. Opening, Cancel and Escape do not save a choice. Keyboard focus enters the dialog and returns to its launcher. Category switches expose independent On/Off states. Pending/failed confirmation is reported separately from choices applied on this page.

Footer integration: use `<button type="button" data-se-consent-action="manage" data-se-consent-persistent aria-haspopup="dialog" aria-controls="se-consent-modal">Consent Preferences</button>`. Menu integration: add CSS class `se-consent-preferences` to a link with URL `#se-consent-modal`. Either explicit marker present at initial render suppresses the default launcher. For dynamically inserted controls, use the filter below with mode custom and ensure the replacement is always reachable. Existing `window.SiteEngineConsent.openPreferences()` remains supported.

Use the `siteengine_consent_preference_controls` PHP filter to return an array: mode auto (default) or custom, side right (default) or left, bottom clearance in CSS pixels (8–320, default 88). Example: `add_filter("siteengine_consent_preference_controls", function () { return ["mode"=>"auto", "side"=>"right", "bottom"=>120]; });`. Safe-area insets are added automatically. Test alongside site launchers at narrow widths and zoom; no automatic collision detection or other-plugin changes. Presentation options do not alter policy/banner identity.

== Optional shared launcher candidate ==

0.6.4-rc.1 implements Consent's local adapter for Shared Launcher Contract 1. Site Shield must supply the separately implemented SEShieldSharedLauncher version1 broker with current-registration identity. Consent keeps its own control until its provider is ready and the exact registration is confirmed with a current generation and an unexpired six-second lease; Shield renews every two seconds. Revocation, expiry, unavailable capability or failed opening restores independent fallback. This is presentation coordination only and grants no advertising permission.

The provider calls the existing openPreferences(initiator) once and independently verifies visible/focused modal opening. The legacy method still returns undefined. Background/scroll restoration precedes focus return; a missing/disabled Shield launcher uses Consent's safe fallback. Consent's modal making Shield temporarily inert is expected. Direct footer/menu controls and initial banner remain independent. A missing/unreachable custom replacement cannot block restoration of an owned fallback control. No consent decision/evidence is carried in the interface or stored registration identity.

This is a locally verified candidate, not installed or jointly accepted. Stable0.6.3 and its accepted installation ZIP remain preserved. Each plugin owns its implementation; joint installed/Reading Mode/cache/accessibility acceptance follows separately. No schema, policy/banner, receipt, chain or retention change. The earlier Consent shared-launcher proposal is superseded; only Shield's approved broker contract plus registrationId clarification is implemented.

== Changelog ==

= 0.6.4-rc.1 =
* Optional Shared Launcher Contract1 Consent provider, identity-bound readiness, six-second lease expiry and safe standalone restoration.
* Checked boolean opening adapter around existing preferences opening; safe focus return and partial-modal cleanup.
* Narrow owned-UI lifecycle handling; no new consent processing or authority changes.


= 0.6.3 =
* Milestone 3: persistent preferences launcher, footer/menu integration and bounded placement filter.
* Modal focus isolation/return, hidden-banner keyboard exclusion, visible independent category states and accessible confirmation feedback.
* Narrow/dynamic viewport and safe-area layout; consent authority, history and policy/banner identity unchanged.


= 0.6.2 =
* Milestone 1: administrator deployment diagnostics, capture/current-eligibility explanations and nonce-protected redacted support report in the existing Consent screen.
* Metadata reads only; no repair, record inspection, capture or external dispatch by diagnostics. Bootstrap behavior remains unchanged.
* Keeps the rc.2 default banner identity and existing policy unchanged; upgrade does not renew consent solely because the plugin release changed.


= 0.6.1-rc.2 (candidate; not deployed or accepted) =
* Adds prospective browser/site continuity, immutable event links and permanent withdrawal barriers.
* Adds read-only current eligibility API contract 2; historical signed stamps retain contract 1.
* Uses transactional InnoDB capture with server revisions; stale grants cannot erase withdrawal.
* Requires Web Locks, secure random generation and durable browser storage for new authority.
* Adds two tables without altering legacy receipt schema or policy identity.
* Integration acceptance requires Ads dispatch-boundary eligibility enforcement and coordinated Alma tests.

= 0.6.1-rc.1 (candidate; not deployed or accepted) =
* Corrects Fluent Forms response-array stamping and sanitizes details/integration data.
* Adds signed immutable event acknowledgements, form-bound stored stamps and strict category mapping.
* Serializes idempotent event retries; conflicting replay and ambiguous records fail closed.
* Separates pending/failed browser choices from acknowledged receipt identity and guards late responses.
* Requires explicit choice for legacy, expired or mismatched policy/banner state; policy value unchanged.
* Integration acceptance is BLOCKED on Ads Bridge's negative-stamp/signature/exact-row contract.
* No historical receipt migration, grant backfill, policy change or deployment is included.

= 0.6.0 =
* Registers with the SiteEngine Base module dashboard when Base is active
* Reads shared Business Name and Privacy Policy URL from Base
* Preserves legacy wp-config constants as higher-priority overrides
* Moves audit/export UI under SiteEngine with standalone Tools fallback
* Adds Base health/prerequisite reporting without making Base a hard dependency

= 0.5.0 =
* Repackaged from must-use plugin to standalone activatable plugin
* Added activation, deactivation, and uninstall hooks
* Removed all references to external services

= 0.4.1 =
* WP-CLI export command
* Admin export form
* Public REST health endpoint

= 0.4.0 =
* Retention cleanup cron
* Rate limiting
* Privacy URL fallback
* Admin audit viewer
