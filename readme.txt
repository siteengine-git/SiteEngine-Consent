=== SiteEngine Consent ===
Contributors: siteengine
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.6.1-rc.2
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

When SiteEngine Base 2.2+ is active, Business Name and Privacy Policy URL are read from SiteEngine → Business Data and the audit screen appears under SiteEngine → Consent. Existing wp-config.php constants remain supported and take precedence for backward compatibility. Without Base, the plugin remains standalone and the audit screen remains under Tools.

== Installation ==

1. Upload the `siteengine-consent` folder to `/wp-content/plugins/`
2. Activate through the WordPress Plugins menu
3. If SiteEngine Base is active, complete Business Name and Privacy Policy URL under SiteEngine → Business Data. Otherwise the plugin uses WordPress defaults, or you may keep legacy wp-config.php overrides:

`define('SE_CONSENT_COMPANY_NAME', 'Your Client Brand Name');`
`define('SE_CONSENT_PRIVACY_URL',  'https://yourclient.com/privacy-policy/');`

4. Verify by visiting the site in incognito — the banner should appear
5. Check SiteEngine → Consent (with Base) or Tools → SiteEngine Consent (standalone) to see the audit log

== Changelog ==

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
