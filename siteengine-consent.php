<?php
/**
 * Plugin Name: SiteEngine Consent
 * Plugin URI:  https://siteengine.io/
 * Description: First-party consent management for WordPress. GTM Consent Mode v2, Fluent Forms server-side consent stamping, local audit log with retention, admin viewer, exports, rate limiting, privacy-policy fallback, public health endpoint. Integrates with SiteEngine Base when available and remains standalone-safe.
 * Version:     0.6.1-rc.2
 * Author:      SiteEngine
 * Author URI:  https://siteengine.io/
 * License:     Proprietary
 * Text Domain: siteengine-consent
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Update URI:  false
 *
 * Update URI: false tells WordPress core (5.8+) never to check wordpress.org
 * for updates to this plugin, preventing a same-slug plugin in the public
 * directory from being offered as an "update" over the top of this one.
 *
 * Deployment: /wp-content/plugins/siteengine-consent/siteengine-consent.php
 * Activate via WP Admin → Plugins.
 *
 * Changelog 0.6.1-rc.2:
 *  - Candidate: same-browser withdrawal barriers and current eligibility API.
 *  - Integration blocked on coordinated Ads contract; no live acceptance.
 *
 * Changelog 0.6.0:
 *  - SiteEngine Base integration: registers as a SiteEngine module when Base is active.
 *  - Uses Base Business Name and Privacy Policy URL as shared configuration when present.
 *  - Existing SE_CONSENT_COMPANY_NAME / SE_CONSENT_PRIVACY_URL constants remain
 *    supported and take precedence for backward compatibility.
 *  - Admin audit/export page moves under SiteEngine when Base is active and
 *    remains under Tools when Base is absent.
 *  - Exposes Base module health/status using the existing consent schema, cron,
 *    receipt, and privacy-URL diagnostics.
 *  - Base prerequisites require explicitly configured Business Name and Privacy
 *    Policy URL, while runtime fallbacks remain safe when Base is unavailable.
 *
 * Changelog 0.5.3:
 *  - FIX: the 0.5.2 duplicate-instance guard used class_exists() and therefore
 *    always fired against itself, so 0.5.2 could never activate. PHP early-binds
 *    unconditional top-level class declarations at compile time, meaning the
 *    class existed before any statement in the file executed. Replaced with a
 *    runtime sentinel constant (SE_CONSENT_BOOTSTRAPPED), which also records
 *    the path of the copy that loaded first so the notice can name it exactly.
 *    0.5.2 should not be deployed.
 *
 * Changelog 0.5.2:
 *  - Added a duplicate-instance guard. A site still carrying the 0.4.x
 *    must-use copy at /wp-content/mu-plugins/siteengine-consent.php would
 *    fatal on class redeclaration the moment this plugin was activated,
 *    taking down wp-admin with it. The plugin now detects the collision,
 *    stops loading, and surfaces an admin notice naming the file to delete.
 *
 * Changelog 0.5.1 (accessibility remediation — WCAG 2.1 AA):
 *  - 4.1.2 Name, Role, Value: category toggles carry role="switch". They
 *    previously set aria-checked on a plain button, which is invalid ARIA;
 *    assistive technology announced them as buttons with no state.
 *  - 2.4.7 Focus Visible / 1.4.11 Non-text Contrast: added a visible focus
 *    indicator to every button and toggle. Focus was previously conveyed only
 *    by an 8% background shift. Two-layer ring, verified >=3:1 on both the
 *    dark banner and the light modal.
 *  - 1.4.3 Contrast (Minimum): default accent hover changed #14AEB9 -> #1B4B8A.
 *    White text on the old teal measured 2.7:1 against a 4.5:1 requirement.
 *    Sites overriding --se-consent-accent-hover must re-verify their own value.
 *  - 1.4.11 Non-text Contrast: toggle track gained a #6b7280 border. The
 *    #d1d5db off-state measured 1.47:1 on white — effectively invisible.
 *  - 2.4.3 Focus Order: opening the preferences dialog now moves focus to its
 *    heading; closing returns focus to the control that opened it.
 *  - 2.1.2 No Keyboard Trap: Tab and Shift+Tab now cycle within the open
 *    dialog rather than moving to page content behind the backdrop.
 *  - 4.1.2: page content behind the dialog is hidden from assistive technology
 *    via aria-hidden while it is open.
 *  - 1.3.1 Info and Relationships: dialog uses aria-labelledby pointing at its
 *    visible heading rather than a separate, mismatched aria-label.
 *  - 2.3.3 / 1.4.12: added prefers-reduced-motion and forced-colors support.
 *
 * Changelog 0.5.0 (standalone plugin):
 *  - Repackaged from must-use plugin to regular activatable plugin
 *  - Added activation, deactivation, and uninstall hooks
 *  - Added activation-time schema install and cron scheduling
 *  - Added deactivation-time cron cleanup (data preserved)
 *  - Added companion uninstall.php for full data removal on delete
 *  - Removed all references to external SiteEngine services (LeadEngine, etc.)
 *  - Plugin is fully standalone; no dependencies on other SiteEngine plugins
 *
 * Changelog 0.4.1 (Evening 2):
 *  - WP-CLI command: wp se-consent export --from=YYYY-MM-DD --to=YYYY-MM-DD [--format=csv|json] [--output=/path]
 *  - Admin Export form (Tools → SiteEngine Consent) with nonce-protected CSV/JSON download
 *  - Public REST endpoint /wp-json/se-consent/v1/health for fleet monitoring
 *  - Shared build_export_rows() used by both CLI command and admin form
 *
 * Changelog 0.4.0 (Evening 1 hardening):
 *  - Retention cleanup via daily WP-Cron (default 730 days, override SE_CONSENT_RETENTION_DAYS)
 *  - Rate limiting on AJAX receipt endpoint (20/min per IP hash)
 *  - Privacy policy fallback URL when client URL is missing or 404s
 *    (default https://siteengine.io/agency-privacy-template/, override SE_CONSENT_PRIVACY_FALLBACK_URL)
 *  - Admin audit viewer at Tools → SiteEngine Consent (requires manage_options)
 *  - Health warnings on admin page (privacy URL not configured, etc.)
 *
 * Per-site wp-config.php overrides:
 *   define('SE_CONSENT_COMPANY_NAME', 'Client Name, Inc.');
 *   define('SE_CONSENT_PRIVACY_URL',  'https://client.com/privacy/');
 *   define('SE_CONSENT_RETENTION_DAYS', 730);              // optional
 *   define('SE_CONSENT_PRIVACY_FALLBACK_URL', 'https://...'); // optional
 *   define('SE_CONSENT_DEBUG', false);
 */

if (!defined('ABSPATH')) {
    exit;
}

// ---------------------------------------------------------------------------
// DUPLICATE INSTANCE GUARD
//
// Earlier deployments shipped this plugin as a must-use plugin under
// /wp-content/mu-plugins/. MU plugins load before regular plugins, so if such
// a copy is still present, reaching the class declaration below would raise:
//
//     PHP Fatal error: Cannot declare class SiteEngine_Consent,
//     because the name is already in use
//
// on EVERY request — front end and wp-admin alike. Because MU plugins cannot
// be deactivated from the dashboard, that state is recoverable only over
// SFTP/SSH. Bail out with a visible, actionable notice instead.
//
// NOTE ON IMPLEMENTATION: this deliberately does NOT use
// class_exists('SiteEngine_Consent'). PHP early-binds unconditional top-level
// class declarations at compile time, so the class below already exists by the
// time any statement in this file runs — a class_exists() guard here would
// fire against itself and the plugin could never load (this was the 0.5.2 bug).
//
// SE_CONSENT_VERSION is the correct signal: it is defined at RUNTIME by every
// version of this plugin ever shipped, including the 0.4.x must-use builds,
// and this check runs before our own constants block below. The documented
// per-site wp-config.php overrides never include SE_CONSENT_VERSION.
// ---------------------------------------------------------------------------
if (defined('SE_CONSENT_VERSION')) {
    add_action('admin_notices', function () {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Reflection names the file that actually declared the class, whatever
        // it is called and wherever it lives — the 0.4.x copies used varying
        // filenames under mu-plugins/, so a hardcoded path would miss them.
        $origin = '';
        if (class_exists('SiteEngine_Consent', false)) {
            try {
                $origin = (string) (new ReflectionClass('SiteEngine_Consent'))->getFileName();
            } catch (Exception $e) {
                $origin = '';
            }
        }
        if ('' === $origin && defined('SE_CONSENT_BOOTSTRAPPED')) {
            $origin = (string) constant('SE_CONSENT_BOOTSTRAPPED');
        }

        echo '<div class="notice notice-error"><p><strong>'
            . esc_html__('SiteEngine Consent is loaded twice.', 'siteengine-consent')
            . '</strong> '
            . esc_html__(
                'Another copy loaded first, so this one stopped to avoid a fatal error. Delete the file below, then reload this page.',
                'siteengine-consent'
            )
            . '</p>';

        if ('' !== $origin) {
            echo '<p><code>' . esc_html($origin) . '</code></p>';
        } else {
            echo '<p>'
                . esc_html__(
                    'The other copy could not be located automatically. Check wp-content/mu-plugins/ for any file containing this plugin — must-use files load regardless of their filename.',
                    'siteengine-consent'
                )
                . '</p>';
        }

        echo '<p>'
            . esc_html(sprintf(
                /* translators: %s: version number of the copy that loaded first */
                __('Version currently running: %s', 'siteengine-consent'),
                SE_CONSENT_VERSION
            ))
            . '</p></div>';
    });

    return;
}

// Records which file booted, as a secondary signal for diagnostics.
define('SE_CONSENT_BOOTSTRAPPED', __FILE__);

if (!defined('SE_CONSENT_COMPANY_NAME_EXPLICIT')) { define('SE_CONSENT_COMPANY_NAME_EXPLICIT', defined('SE_CONSENT_COMPANY_NAME')); }
if (!defined('SE_CONSENT_PRIVACY_URL_EXPLICIT'))  { define('SE_CONSENT_PRIVACY_URL_EXPLICIT', defined('SE_CONSENT_PRIVACY_URL')); }
if (!defined('SE_CONSENT_VERSION'))               { define('SE_CONSENT_VERSION', '0.6.1-rc.2'); }
if (!defined('SE_CONSENT_COOKIE_NAME'))           { define('SE_CONSENT_COOKIE_NAME', 'se_consent'); }
if (!defined('SE_CONSENT_COOKIE_DAYS'))           { define('SE_CONSENT_COOKIE_DAYS', 390); }
if (!defined('SE_CONSENT_POLICY_VERSION'))        { define('SE_CONSENT_POLICY_VERSION', '2026-05-08-v1'); }
if (!defined('SE_CONSENT_BANNER_VERSION'))        { define('SE_CONSENT_BANNER_VERSION', SE_CONSENT_VERSION); }
if (!defined('SE_CONSENT_COMPANY_NAME'))          { define('SE_CONSENT_COMPANY_NAME', get_bloginfo('name')); }
if (!defined('SE_CONSENT_PRIVACY_URL'))           { define('SE_CONSENT_PRIVACY_URL', home_url('/privacy-policy/')); }
if (!defined('SE_CONSENT_PRIVACY_FALLBACK_URL'))  { define('SE_CONSENT_PRIVACY_FALLBACK_URL', 'https://siteengine.io/agency-privacy-template/'); }
if (!defined('SE_CONSENT_RETENTION_DAYS'))        { define('SE_CONSENT_RETENTION_DAYS', 730); }
if (!defined('SE_CONSENT_RATE_LIMIT_PER_MIN'))    { define('SE_CONSENT_RATE_LIMIT_PER_MIN', 20); }
if (!defined('SE_CONSENT_DEBUG'))                 { define('SE_CONSENT_DEBUG', false); }

// ============================================================================
// ACTIVATION / DEACTIVATION HOOKS
// ----------------------------------------------------------------------------
// Registered at file scope per WordPress plugin requirements. Must be defined
// before any class instantiation happens.
// ============================================================================

/**
 * Activation: create schema, schedule cron, mark installed version.
 * Runs once when the site admin activates the plugin.
 */
function siteengine_consent_activate() {
    // Minimum requirements guard.
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'SiteEngine Consent requires PHP 7.4 or newer. Detected: ' . esc_html(PHP_VERSION),
            'Plugin activation failed',
            ['back_link' => true]
        );
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = $wpdb->prefix . 'se_consent_receipts';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        receipt_id varchar(64) NOT NULL,
        method varchar(64) NOT NULL DEFAULT '',
        policy_version varchar(64) NOT NULL DEFAULT '',
        banner_version varchar(64) NOT NULL DEFAULT '',
        consent_json longtext NOT NULL,
        google_consent_json longtext NOT NULL,
        page_url text NULL,
        referrer_url text NULL,
        ip_hash char(64) NULL,
        user_agent_hash char(64) NULL,
        created_at datetime NOT NULL,
        server_created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY receipt_id (receipt_id),
        KEY policy_version (policy_version),
        KEY created_at (created_at)
    ) {$charset_collate};";
    dbDelta($sql);
    foreach (SiteEngine_Consent_Continuity::schema($wpdb->prefix, $charset_collate) as $continuity_sql) dbDelta($continuity_sql);

    update_option('se_consent_version', SE_CONSENT_VERSION, false);
    update_option('se_consent_policy_version', SE_CONSENT_POLICY_VERSION, false);
    update_option('se_consent_activated_at', current_time('mysql', true), false);

    // Schedule daily cleanup cron.
    if (!wp_next_scheduled('se_consent_daily_cleanup')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'se_consent_daily_cleanup');
    }
}
register_activation_hook(__FILE__, 'siteengine_consent_activate');

/**
 * Deactivation: unschedule cron. Data (audit table, options) is preserved
 * so re-activation resumes with prior receipts intact.
 * Full data wipe happens only on uninstall (see uninstall.php).
 */
function siteengine_consent_deactivate() {
    $timestamp = wp_next_scheduled('se_consent_daily_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'se_consent_daily_cleanup');
    }
    // Clear any remaining scheduled instances of this hook.
    wp_clear_scheduled_hook('se_consent_daily_cleanup');
}
register_deactivation_hook(__FILE__, 'siteengine_consent_deactivate');

require_once __DIR__ . '/includes/class-siteengine-consent-continuity.php';
require_once __DIR__ . '/includes/class-siteengine-consent-authority.php';

final class SiteEngine_Consent {
    private static $instance = null;
    private $config = [];
    private $submission_stamps = [];

    public static function instance(): SiteEngine_Consent {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->config = $this->default_config();
        $this->config['banner_version'] = $this->compute_banner_hash();
        $this->config['privacy_url']    = $this->resolve_privacy_url();
        $this->maybe_upgrade();
        $this->hooks();
    }

    private function default_config(): array {
        return [
            'version'        => SE_CONSENT_VERSION,
            'cookie_name'    => SE_CONSENT_COOKIE_NAME,
            'cookie_days'    => (int) SE_CONSENT_COOKIE_DAYS,
            'policy_version' => SE_CONSENT_POLICY_VERSION,
            'banner_version' => SE_CONSENT_BANNER_VERSION,
            'company_name'   => $this->configured_company_name(),
            'privacy_url'    => $this->configured_privacy_url(),
            'privacy_fallback_url' => SE_CONSENT_PRIVACY_FALLBACK_URL,
            'debug'          => (bool) SE_CONSENT_DEBUG,
            'position'       => 'bottom',
            'accent_color'   => '#235DAA',
            // WCAG 1.4.3: white button text on the previous #14AEB9 measured
            // 2.7:1 against a 4.5:1 requirement. #1B4B8A measures 8.68:1.
            'accent_hover'   => '#1B4B8A',
            'categories'     => [
                'necessary'       => true,
                'analytics'       => false,
                'advertising'     => false,
                'personalization' => false,
            ],
            'google_mapping' => [
                'analytics_storage'       => 'analytics',
                'ad_storage'              => 'advertising',
                'ad_user_data'            => 'advertising',
                'ad_personalization'      => 'personalization',
                'functionality_storage'   => 'necessary',
                'personalization_storage' => 'personalization',
                'security_storage'        => 'necessary',
            ],
        ];
    }

    /**
     * Resolve the client name. Explicit legacy constants win; otherwise Base is
     * the shared authority when available; finally fall back to WordPress.
     */
    private function configured_company_name(): string {
        if (SE_CONSENT_COMPANY_NAME_EXPLICIT) {
            return (string) SE_CONSENT_COMPANY_NAME;
        }

        if (function_exists('siteengine_get')) {
            $value = trim((string) siteengine_get('business_name', ''));
            if ('' !== $value) {
                return $value;
            }
        }

        return (string) get_bloginfo('name');
    }

    /**
     * Resolve the client privacy-policy URL with the same compatibility order.
     */
    private function configured_privacy_url(): string {
        if (SE_CONSENT_PRIVACY_URL_EXPLICIT) {
            return (string) SE_CONSENT_PRIVACY_URL;
        }

        if (function_exists('siteengine_get')) {
            $value = trim((string) siteengine_get('privacy_policy_url', ''));
            if ('' !== $value) {
                return $value;
            }
        }

        return (string) home_url('/privacy-policy/');
    }

    public function has_configured_company_name(): bool {
        if (SE_CONSENT_COMPANY_NAME_EXPLICIT && '' !== trim((string) SE_CONSENT_COMPANY_NAME)) {
            return true;
        }
        return function_exists('siteengine_has') && siteengine_has('business_name');
    }

    public function has_configured_privacy_url(): bool {
        if (SE_CONSENT_PRIVACY_URL_EXPLICIT && '' !== trim((string) SE_CONSENT_PRIVACY_URL)) {
            return true;
        }
        return function_exists('siteengine_has') && siteengine_has('privacy_policy_url');
    }

    private function compute_banner_hash(): string {
        $material = [
            'company'    => $this->config['company_name'] ?? '',
            'privacy'    => $this->config['privacy_url'] ?? '',
            'categories' => isset($this->config['categories']) ? array_keys($this->config['categories']) : [],
            'mapping'    => $this->config['google_mapping'] ?? [],
            'static_v'   => SE_CONSENT_BANNER_VERSION,
        ];
        return SE_CONSENT_BANNER_VERSION . '-' . substr(hash('sha256', wp_json_encode($material)), 0, 10);
    }

    /**
     * Resolve the privacy URL with fallback logic.
     * Caches the result for 12 hours via transient to avoid per-request HTTP checks.
     * If the configured URL returns a non-2xx HTTP code, falls back to template URL.
     */
    private function resolve_privacy_url(): string {
        $configured = (string) ($this->config['privacy_url'] ?? '');
        $fallback   = (string) ($this->config['privacy_fallback_url'] ?? SE_CONSENT_PRIVACY_FALLBACK_URL);

        // No configured URL at all → fallback with client context.
        if (empty($configured)) {
            return $this->build_fallback_url($fallback);
        }

        // Cached health check.
        $cache_key = 'se_consent_privacy_ok_' . md5($configured);
        $cached = get_transient($cache_key);

        if ($cached === 'ok') {
            return $configured;
        }
        if ($cached === 'fail') {
            return $this->build_fallback_url($fallback);
        }

        // No cached result — check it. Cheap HEAD request, 5-sec timeout, non-blocking on failure.
        $response = wp_remote_head($configured, ['timeout' => 5, 'redirection' => 3, 'sslverify' => true]);
        if (is_wp_error($response)) {
            // Network/DNS error — assume OK and let user verify; don't punish first request.
            // Cache as ok for 1 hour only so we re-check sooner than the standard 12h.
            set_transient($cache_key, 'ok', HOUR_IN_SECONDS);
            return $configured;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 400) {
            set_transient($cache_key, 'ok', 12 * HOUR_IN_SECONDS);
            return $configured;
        }

        set_transient($cache_key, 'fail', 12 * HOUR_IN_SECONDS);
        return $this->build_fallback_url($fallback);
    }

    private function build_fallback_url(string $base): string {
        $args = [
            'client'  => rawurlencode((string) ($this->config['company_name'] ?? get_bloginfo('name'))),
            'site'    => rawurlencode((string) home_url('/')),
        ];
        $sep = (strpos($base, '?') === false) ? '?' : '&';
        return $base . $sep . http_build_query($args);
    }

    private function hooks(): void {
        add_action('wp_head', [$this, 'print_config'], 1);
        add_action('wp_footer', [$this, 'print_frontend'], 50);
        add_action('wp_footer', [$this, 'debug_marker'], 9999);
        add_action('wp_ajax_se_consent_save_receipt', [$this, 'ajax_save_receipt']);
        add_action('wp_ajax_nopriv_se_consent_save_receipt', [$this, 'ajax_save_receipt']);
        add_filter('fluentform/insert_response_data', [$this, 'stamp_submission_consent'], PHP_INT_MAX, 3);
        add_filter('fluentform/submission_form_data', [$this, 'stamp_submission_details'], PHP_INT_MAX, 3);

        // Retention cleanup cron.
        add_action('init', [$this, 'maybe_schedule_cleanup']);
        add_action('se_consent_daily_cleanup', [$this, 'run_cleanup']);

        // Admin viewer. Base consumes the module filter when present; the
        // Tools page remains the standalone fallback.
        add_filter('siteengine_modules', [$this, 'register_siteengine_module']);
        add_action('admin_menu', [$this, 'register_admin_page']);

        // Public health endpoint for fleet monitoring.
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // WP-CLI export command (only when running under WP-CLI).
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('se-consent export', [$this, 'cli_export']);
        }
    }

    public function maybe_upgrade(): void {
        $installed_version = get_option('se_consent_version');
        if ($installed_version !== SE_CONSENT_VERSION) {
            $this->create_or_update_tables();
            update_option('se_consent_version', SE_CONSENT_VERSION, false);
            update_option('se_consent_policy_version', SE_CONSENT_POLICY_VERSION, false);
        }
    }

    private function create_or_update_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table_name = $wpdb->prefix . 'se_consent_receipts';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            receipt_id varchar(64) NOT NULL,
            method varchar(64) NOT NULL DEFAULT '',
            policy_version varchar(64) NOT NULL DEFAULT '',
            banner_version varchar(64) NOT NULL DEFAULT '',
            consent_json longtext NOT NULL,
            google_consent_json longtext NOT NULL,
            page_url text NULL,
            referrer_url text NULL,
            ip_hash char(64) NULL,
            user_agent_hash char(64) NULL,
            created_at datetime NOT NULL,
            server_created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY receipt_id (receipt_id),
            KEY policy_version (policy_version),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql);
        foreach (SiteEngine_Consent_Continuity::schema($wpdb->prefix, $charset_collate) as $continuity_sql) dbDelta($continuity_sql);
    }

    public function get_config(): array { return $this->config; }

    /**
     * Retention cleanup — schedule daily cron.
     */
    public function maybe_schedule_cleanup(): void {
        if (!wp_next_scheduled('se_consent_daily_cleanup')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'se_consent_daily_cleanup');
        }
    }

    /**
     * Retention cleanup — delete receipts older than SE_CONSENT_RETENTION_DAYS.
     */
    public function run_cleanup(): void {
        global $wpdb;
        $days = max(30, (int) SE_CONSENT_RETENTION_DAYS); // hard floor at 30 days for safety
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $table = $wpdb->prefix . 'se_consent_receipts';
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff));
        update_option('se_consent_last_cleanup', [
            'ran_at'   => current_time('mysql', true),
            'cutoff'   => $cutoff,
            'deleted'  => is_int($deleted) ? $deleted : 0,
            'days'     => $days,
        ], false);
    }

    public function ajax_save_receipt(): void {
        check_ajax_referer('se_consent_save_receipt', 'nonce');

        // Rate limit — 20/min per IP hash by default.
        $ip = $this->get_request_ip();
        $ip_hash = $ip ? hash('sha256', $ip . wp_salt('nonce')) : 'unknown';
        $rl_key = 'se_consent_rl_' . substr($ip_hash, 0, 16);
        $count = (int) get_transient($rl_key);
        $limit = (int) SE_CONSENT_RATE_LIMIT_PER_MIN;
        if ($count >= $limit) {
            wp_send_json_error(['message' => 'Rate limited.'], 429);
        }
        set_transient($rl_key, $count + 1, MINUTE_IN_SECONDS);

        $raw_payload = isset($_POST['payload']) && is_string($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
        if (strlen($raw_payload) > 65536) wp_send_json_error(['message' => 'Consent payload too large.'], 400);
        $payload = json_decode((string) $raw_payload, true);
        if (!is_array($payload)) wp_send_json_error(['message' => 'Invalid consent payload.'], 400);
        $authority = new SiteEngine_Consent_Authority($this->config);
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $result = $authority->save($payload, $ip, $user_agent);
        if (is_wp_error($result)) {
            $data = $result->get_error_data();
            wp_send_json_error(['code' => $result->get_error_code(), 'message' => $result->get_error_message(), 'continuity' => $data['continuity'] ?? null], $data['status'] ?? 503);
        }
        wp_send_json_success($result);
    }

    /** Fluent Forms supplies the response ARRAY, form ID and input configs. */
    public function stamp_submission_consent($response, $form_id, $input_configs) {
        $authority = new SiteEngine_Consent_Authority($this->config);
        try {
            $raw = $_COOKIE[SE_CONSENT_COOKIE_NAME] ?? '';
            $cookie = is_string($raw) ? json_decode(wp_unslash($raw), true) : null;
            $stamp = $authority->resolve($cookie);
            // A pending/local rejection must not fall back to a previous cookie ACK
            // when cookie writes are refused. Require the form's current ID echo.
            if ($stamp['state'] === 'verified' && (!is_array($response)
                || !isset($response['se_consent_receipt_id']) || !is_string($response['se_consent_receipt_id'])
                || $response['se_consent_receipt_id'] !== $stamp['receipt_id'])) {
                $stamp = $authority->negative('conflicting_claim');
            }
        } catch (\Throwable $e) {
            // Do not log cookie/proof/client data or return untrusted original authority.
            $stamp = $authority->negative('unavailable');
        }
        $stamp = SiteEngine_Consent_Authority::seal_stamp($stamp, (int) $form_id);
        $this->submission_stamps[(string) $form_id] = $stamp;
        return $authority->stamp(is_array($response) ? $response : [], $stamp);
    }

    /** Reapply the frozen request decision before entry-details append/integrations. */
    public function stamp_submission_details($response, $submission_id, $form) {
        $authority = new SiteEngine_Consent_Authority($this->config);
        $form_id = is_object($form) ? ($form->id ?? 0) : 0;
        $stamp = $this->submission_stamps[(string) $form_id] ?? SiteEngine_Consent_Authority::seal_stamp($authority->negative('not_captured'), (int) $form_id);
        return $authority->stamp(is_array($response) ? $response : [], $stamp);
    }

    private function get_request_ip(): string {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) continue;
            $value = sanitize_text_field(wp_unslash((string) $_SERVER[$key]));
            $parts = array_map('trim', explode(',', $value));
            $ip = $parts[0] ?? '';
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
        return '';
    }

    public function print_config(): void {
        if (is_admin()) return;
        $frontend_config = [
            'version'        => $this->config['version'],
            'cookieName'     => $this->config['cookie_name'],
            'cookieDays'     => $this->config['cookie_days'],
            'policyVersion'  => $this->config['policy_version'],
            'bannerVersion'  => $this->config['banner_version'],
            'companyName'    => $this->config['company_name'],
            'privacyUrl'     => $this->config['privacy_url'],
            'position'       => $this->config['position'],
            'accentColor'    => $this->config['accent_color'],
            'accentHover'    => $this->config['accent_hover'],
            'debug'          => $this->config['debug'],
            'defaultConsent' => $this->config['categories'],
            'googleMapping'  => $this->config['google_mapping'],
            'formFields'     => [
                'receipt_id'         => 'se_consent_receipt_id',
                'method'             => 'se_consent_method',
                'policy_version'     => 'se_consent_policy_version',
                'banner_version'     => 'se_consent_banner_version',
                'created_at'         => 'se_consent_timestamp',
                'analytics'          => 'se_consent_analytics',
                'advertising'        => 'se_consent_advertising',
                'personalization'    => 'se_consent_personalization',
                'ad_storage'         => 'se_google_ad_storage',
                'analytics_storage'  => 'se_google_analytics_storage',
                'ad_user_data'       => 'se_google_ad_user_data',
                'ad_personalization' => 'se_google_ad_personalization',
                'page_url'           => 'se_consent_page_url',
            ],
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('se_consent_save_receipt'),
            'continuityScope' => substr(hash('sha256', home_url('/') . ':' . $GLOBALS['wpdb']->prefix), 0, 16),
        ];
        echo "\n<script>\n";
        echo "window.dataLayer = window.dataLayer || [];\n";
        echo "function gtag(){window.dataLayer.push(arguments);}\n";
        echo "gtag('consent', 'default', {\n";
        echo "  'ad_storage': 'denied',\n";
        echo "  'analytics_storage': 'denied',\n";
        echo "  'ad_user_data': 'denied',\n";
        echo "  'ad_personalization': 'denied',\n";
        echo "  'personalization_storage': 'denied',\n";
        echo "  'functionality_storage': 'granted',\n";
        echo "  'security_storage': 'granted',\n";
        echo "  'wait_for_update': 500\n";
        echo "});\n";
        echo "window.SEConsentConfig = " . wp_json_encode($frontend_config) . ";</script>\n";
    }

    public function print_frontend(): void {
        if (is_admin()) return;
        ?>
        <style id="se-consent-styles">
            #se-consent-banner,#se-consent-modal,#se-consent-backdrop{box-sizing:border-box}
            #se-consent-banner *,#se-consent-modal *{box-sizing:border-box}
            #se-consent-banner{position:fixed;left:0;right:0;bottom:0;z-index:999999;background:#1f2937;color:#f9fafb;box-shadow:0 -4px 24px rgba(0,0,0,.24);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;transform:translateY(110%);transition:transform 240ms ease}
            #se-consent-banner.se-consent-visible{transform:translateY(0)}
            .se-consent-banner-inner{max-width:1180px;margin:0 auto;padding:18px 22px;display:flex;align-items:center;gap:18px}
            .se-consent-banner-copy{flex:1;min-width:260px;font-size:14px;line-height:1.5}
            .se-consent-banner-copy strong{display:block;margin-bottom:3px;font-size:15px;color:#fff}
            .se-consent-banner-copy a{color:#bfdbfe;text-decoration:underline}
            .se-consent-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
            .se-consent-button{border-radius:7px;border:1px solid transparent;padding:10px 18px;font-size:14px;line-height:1.2;font-weight:600;cursor:pointer;transition:background 160ms ease,border-color 160ms ease,color 160ms ease;white-space:nowrap}
            .se-consent-button-primary{background:var(--se-consent-accent,#235DAA);border-color:var(--se-consent-accent,#235DAA);color:#fff}
            .se-consent-button-primary:hover{background:var(--se-consent-accent-hover,#1B4B8A);border-color:var(--se-consent-accent-hover,#1B4B8A);color:#fff}
            .se-consent-button-secondary{background:transparent;border-color:#9ca3af;color:#f3f4f6}
            .se-consent-button-secondary:hover{background:rgba(255,255,255,.14);color:#fff}
            /* WCAG 2.4.7 / 1.4.11 — visible focus indicator with >=3:1 contrast against
               both the dark banner (#1f2937) and the light modal (#fff). Two-layer ring:
               a dark inner halo plus a white outer ring, so it is visible on either. */
            .se-consent-button:focus-visible,
            .se-consent-toggle:focus-visible{outline:3px solid #fff;outline-offset:2px;box-shadow:0 0 0 6px #0b1b2b;border-radius:7px}
            .se-consent-modal-inner .se-consent-button:focus-visible,
            .se-consent-modal-inner .se-consent-toggle:focus-visible{outline:3px solid #0b3d91;outline-offset:2px;box-shadow:0 0 0 6px #fff}
            /* Fallback for browsers without :focus-visible support. */
            .se-consent-button:focus,
            .se-consent-toggle:focus{outline:3px solid #fff;outline-offset:2px}
            .se-consent-modal-inner .se-consent-button:focus,
            .se-consent-modal-inner .se-consent-toggle:focus{outline:3px solid #0b3d91;outline-offset:2px}
            .se-consent-button:focus:not(:focus-visible),
            .se-consent-toggle:focus:not(:focus-visible){outline:none;box-shadow:none}
            #se-consent-backdrop{display:none;position:fixed;inset:0;z-index:1000000;background:rgba(15,23,42,.62)}
            #se-consent-backdrop.se-consent-visible{display:block}
            #se-consent-modal{display:none;position:fixed;z-index:1000001;left:50%;top:50%;width:min(560px,calc(100vw - 32px));max-height:calc(100vh - 32px);overflow:auto;transform:translate(-50%,-50%);background:#fff;color:#111827;border-radius:14px;box-shadow:0 24px 80px rgba(0,0,0,.35);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif}
            #se-consent-modal.se-consent-visible{display:block}
            .se-consent-modal-inner{padding:24px}
            .se-consent-modal-title{margin:0 0 8px;font-size:21px;line-height:1.25}
            .se-consent-modal-copy{margin:0 0 18px;color:#4b5563;font-size:14px;line-height:1.55}
            .se-consent-category{border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin:0 0 12px}
            .se-consent-category-row{display:flex;align-items:center;justify-content:space-between;gap:16px}
            .se-consent-category h3{margin:0 0 4px;font-size:15px;line-height:1.3}
            .se-consent-category p{margin:0;color:#6b7280;font-size:13px;line-height:1.45}
            /* WCAG 1.4.11 — the off-state track needs >=3:1 against the white card.
               #d1d5db alone is ~1.5:1, so the control was effectively invisible.
               A #6b7280 border (4.8:1 on white) carries the boundary instead. */
            .se-consent-toggle{min-width:48px;height:26px;border-radius:999px;border:1px solid #6b7280;background:#d1d5db;cursor:pointer;position:relative;transition:background 160ms ease}
            .se-consent-toggle::after{content:"";position:absolute;width:20px;height:20px;border-radius:999px;left:2px;top:2px;background:#fff;transition:transform 160ms ease;box-shadow:0 1px 3px rgba(0,0,0,.25)}
            .se-consent-toggle[aria-checked="true"]{background:var(--se-consent-accent,#235DAA);border-color:var(--se-consent-accent,#235DAA)}
            .se-consent-toggle[aria-checked="true"]::after{transform:translateX(22px)}
            .se-consent-toggle[aria-disabled="true"]{cursor:not-allowed;opacity:.85}
            /* WCAG 1.4.12 — do not clip text when users override spacing. */
            .se-consent-banner-copy,.se-consent-modal-copy,.se-consent-category p{overflow-wrap:break-word}
            /* Windows High Contrast Mode — outlines and toggle state must survive
               forced-colors, where background-color is overridden by the OS. */
            @media (forced-colors:active){
                .se-consent-button:focus-visible,.se-consent-toggle:focus-visible,
                .se-consent-button:focus,.se-consent-toggle:focus{outline:3px solid Highlight;outline-offset:2px;box-shadow:none}
                .se-consent-toggle{border:2px solid ButtonText;forced-color-adjust:none;background:ButtonFace}
                .se-consent-toggle[aria-checked="true"]{background:Highlight;border-color:Highlight}
                .se-consent-toggle::after{background:ButtonText}
                .se-consent-toggle[aria-checked="true"]::after{background:ButtonFace}
            }
            /* WCAG 2.3.3 — respect a stated preference for reduced motion. */
            @media (prefers-reduced-motion:reduce){
                #se-consent-banner,.se-consent-button,.se-consent-toggle,.se-consent-toggle::after{transition-duration:0.01ms !important}
            }
            .se-consent-modal-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:18px}
            .se-consent-modal-actions .se-consent-button-secondary{border-color:#d1d5db;color:#374151}
            .se-consent-modal-actions .se-consent-button-secondary:hover,.se-consent-modal-actions .se-consent-button-secondary:focus{background:#f3f4f6;color:#111827}
            @media (max-width:720px){.se-consent-banner-inner{align-items:stretch;flex-direction:column}.se-consent-actions,.se-consent-modal-actions{justify-content:stretch}.se-consent-button{width:100%}}
        </style>
        <script id="se-consent-script">
            (function () {
                'use strict';
                var config = window.SEConsentConfig || {};
                var cookieName = config.cookieName || 'se_consent';
                var cookieDays = parseInt(config.cookieDays || 390, 10);
                var storageKey = cookieName + '_state';
                var receiptKey = cookieName + '_receipt_id';
                var continuityStorage = cookieName + '_continuity_' + (config.continuityScope || 'site');
                var continuityMemory = null;
                function continuityContext(create) {
                    var found = null;
                    try { found = safeParse(window.localStorage.getItem(continuityStorage)); } catch (e) {}
                    try { found = found || safeParse(getCookie(continuityStorage)); } catch (e) {}
                    found = found || continuityMemory;
                    if (found && /^[a-f0-9]{64}$/.test(found.key || '')) return found;
                    if (!create || !window.crypto || typeof window.crypto.getRandomValues !== 'function') return null;
                    var bytes = new Uint8Array(32); window.crypto.getRandomValues(bytes);
                    found = { key: Array.prototype.map.call(bytes, function (v) { return ('0' + v.toString(16)).slice(-2); }).join(''), token: '', revision: 0 };
                    return persistContinuity(found) ? found : null;
                }
                function persistContinuity(context) {
                    var encoded = JSON.stringify(context), durable = false;
                    try { window.localStorage.setItem(continuityStorage, encoded); durable = window.localStorage.getItem(continuityStorage) === encoded; } catch (e) {}
                    try { setCookie(continuityStorage, encoded, cookieDays); durable = getCookie(continuityStorage) === encoded || durable; } catch (e) {}
                    continuityMemory = durable ? context : null;
                    return durable;
                }
                function learnContinuity(payload, context) {
                    if (!context || !Number.isSafeInteger(context.revision) || context.revision < 1 || typeof context.token !== 'string') return;
                    var local = continuityContext();
                    if (!local || local.key !== payload.continuity_key || context.revision <= local.revision) return;
                    persistContinuity({ key: local.key, token: context.token, revision: context.revision });
                }
                // Origin-wide Web Lock prevents two first-use tabs from establishing competing identities.
                // Without it, local choices remain usable but no new authority is acknowledged.
                var continuityReady = window.navigator && window.navigator.locks
                    ? window.navigator.locks.request(continuityStorage, function () { return continuityContext(true); })
                        .catch(function () { return null; }) : Promise.resolve(null);
                var root = document.documentElement;
                root.style.setProperty('--se-consent-accent', config.accentColor || '#235DAA');
                root.style.setProperty('--se-consent-accent-hover', config.accentHover || '#1B4B8A');

                function log() { if (!config.debug || !window.console) return; console.log.apply(console, ['[SiteEngine Consent]'].concat(Array.prototype.slice.call(arguments))); }
                function uuid() {
                    if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
                    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) { var r = Math.random() * 16 | 0; var v = c === 'x' ? r : (r & 0x3 | 0x8); return v.toString(16); });
                }
                function getCookie(name) { var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()\[\]\\/+^])/g, '\\$1') + '=([^;]*)')); return match ? decodeURIComponent(match[1]) : null; }
                function setCookie(name, value, days) { var d = new Date(); d.setTime(d.getTime() + (days*24*60*60*1000)); document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax; Secure'; }
                function safeParse(value) { try { return JSON.parse(value); } catch (e) { return null; } }
                var memoryState = null;
                var memoryOnly = false;
                var activeEventId = null;
                function readStoredConsent() {
                    if (memoryOnly && memoryState) return memoryState;
                    var v = null; try { v = window.localStorage.getItem(storageKey); } catch (e) {}
                    if (v) return safeParse(v);
                    try { var c = getCookie(cookieName); if (c) return safeParse(c); } catch (e) {}
                    return memoryState;
                }
                function getStoredConsent() {
                    var state = readStoredConsent();
                    if (!state || state.contract_version !== 1 || !state.event_id || !state.consent) return null;
                    if (state.policy_version !== config.policyVersion || state.banner_version !== config.bannerVersion) return null;
                    var created = Date.parse(state.created_at);
                    if (!isFinite(created) || created > Date.now() + 300000 || created + cookieDays * 86400000 <= Date.now()) return null;
                    if (state.audit_status === 'saved' && (!state.audit_token || !state.receipt_id || state.expires_at * 1000 <= Date.now())) return null;
                    return state;
                }
                function normalizeConsent(input) { return { necessary: true, analytics: !!input.analytics, advertising: !!input.advertising, personalization: !!input.personalization }; }
                function googleConsentFromCategories(c) { return { ad_storage: c.advertising?'granted':'denied', analytics_storage: c.analytics?'granted':'denied', ad_user_data: c.advertising?'granted':'denied', ad_personalization: c.personalization?'granted':'denied', personalization_storage: c.personalization?'granted':'denied', functionality_storage: 'granted', security_storage: 'granted' }; }
                function applyGoogleConsent(payload, command) {
                    if (!payload || !payload.google_consent) return;
                    window.dataLayer = window.dataLayer || [];
                    if (typeof window.gtag !== 'function') window.gtag = function () { window.dataLayer.push(arguments); };
                    window.gtag('consent', command || 'update', payload.google_consent);
                    log('google consent ' + (command || 'update'), payload.google_consent);
                }
                function buildPayload(input, method) {
                    var consent = normalizeConsent(input || {});
                    var continuity = continuityContext();
                    return { continuity_key: continuity ? continuity.key : '', continuity_token: continuity ? continuity.token : '', contract_version: 1, event_id: uuid(), receipt_id: '', audit_token: '', audit_saved: false, audit_status: 'pending', method: method || 'unknown', policy_version: config.policyVersion || '', banner_version: config.bannerVersion || '', created_at: new Date().toISOString(), page_url: window.location.href, referrer_url: document.referrer || '', consent: consent, google_consent: googleConsentFromCategories(consent) };
                }
                function storeConsent(payload) {
                    var encoded = JSON.stringify(payload);
                    memoryState = payload;
                    // Keep long page/referrer URLs outside the cookie size budget.
                    var cookieState = Object.assign({}, payload); delete cookieState.page_url; delete cookieState.referrer_url;
                    try { setCookie(cookieName, JSON.stringify(cookieState), cookieDays); setCookie(receiptKey, payload.receipt_id || '', cookieDays); } catch (e) {}
                    try { window.localStorage.setItem(storageKey, encoded); window.localStorage.setItem(receiptKey, payload.receipt_id || ''); memoryOnly = window.localStorage.getItem(storageKey) !== encoded; } catch (e) { memoryOnly = true; }
                    window.SiteEngineConsent.state = payload;
                    return payload;
                }
                function consentValue(v) { return v ? 'granted' : 'denied'; }
                function getFieldValueMap(payload) {
                    if (!payload || !payload.consent || !payload.google_consent) return {};
                    return { receipt_id: payload.receipt_id || '', method: payload.method || '', policy_version: payload.policy_version || '', banner_version: payload.banner_version || '', created_at: payload.created_at || '', analytics: consentValue(payload.consent.analytics), advertising: consentValue(payload.consent.advertising), personalization: consentValue(payload.consent.personalization), ad_storage: payload.google_consent.ad_storage || 'denied', analytics_storage: payload.google_consent.analytics_storage || 'denied', ad_user_data: payload.google_consent.ad_user_data || 'denied', ad_personalization: payload.google_consent.ad_personalization || 'denied', page_url: payload.page_url || window.location.href };
                }
                function ensureHiddenField(form, name) {
                    if (!form || !name) return null;
                    var field = form.querySelector('[name="' + cssEscape(name) + '"]');
                    if (field) return field;
                    field = document.createElement('input'); field.type = 'hidden'; field.name = name; field.setAttribute('data-se-consent-generated', '1');
                    form.appendChild(field); return field;
                }
                function populateConsentFields(form) {
                    var payload = getStoredConsent();
                    if (!payload || !payload.consent) {
                        Object.keys(config.formFields || {}).forEach(function (key) { var field = form.querySelector('[name="' + cssEscape(config.formFields[key]) + '"]'); if (field) { field.value = ''; field.defaultValue = ''; field.setAttribute('value', ''); } });
                        return false;
                    }
                    if (!payload.google_consent) payload.google_consent = googleConsentFromCategories(payload.consent);
                    var fields = config.formFields || {};
                    var values = getFieldValueMap(payload);
                    Object.keys(fields).forEach(function (key) {
                        var fieldName = fields[key];
                        var field = ensureHiddenField(form, fieldName);
                        if (field) {
                            var nv = values[key] || '';
                            field.value = nv;
                            field.defaultValue = nv;
                            field.setAttribute('value', nv);
                        }
                    });
                    form.setAttribute('data-se-consent-populated', '1');
                    document.dispatchEvent(new CustomEvent('siteengineConsentFormPopulated', { detail: { form: form, receipt_id: payload.receipt_id || '', values: values } }));
                    log('form populated', form, values);
                    return true;
                }
                function populateAllConsentForms() {
                    var forms = document.querySelectorAll('form.frm-fluent-form, form.fluent_form, form[data-form_id], form');
                    forms.forEach(function (form) {
                        if (form.querySelector('.ff-el-input--content, [name="__fluent_form_embded_post_id"], [name="_fluentform_' + 'nonce"], [data-name]')) {
                            populateConsentFields(form);
                        }
                    });
                }
                function bindFormPopulation() {
                    populateAllConsentForms();
                    document.addEventListener('submit', function (event) { if (event.target && event.target.tagName === 'FORM') populateConsentFields(event.target); }, true);
                    document.addEventListener('siteengineConsentSaved', function () { populateAllConsentForms(); });
                    if (window.jQuery) {
                        try {
                            window.jQuery(document).on('fluentform_submission_started', function (event, formObj) {
                                var formEl = null;
                                if (formObj) {
                                    if (formObj.jquery && formObj.length) formEl = formObj[0];
                                    else if (formObj.tagName === 'FORM') formEl = formObj;
                                    else if (formObj.target && formObj.target.tagName === 'FORM') formEl = formObj.target;
                                }
                                if (!formEl && event && event.target && event.target.tagName === 'FORM') formEl = event.target;
                                if (formEl) { populateConsentFields(formEl); log('fluentform_submission_started repopulation', formEl); }
                            });
                        } catch (e) { log('fluentform jQuery hook failed', e); }
                    }
                    window.setTimeout(populateAllConsentForms, 1000);
                    window.setTimeout(populateAllConsentForms, 3000);
                }
                function cssEscape(value) { if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value); return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&'); }
                function auditStatus(status) {
                    var element = document.getElementById('se-consent-confirmation');
                    if (!element && document.body) {
                        element = document.createElement('div'); element.id = 'se-consent-confirmation';
                        element.setAttribute('role', 'status'); element.setAttribute('aria-live', 'polite');
                        document.body.appendChild(element);
                    }
                    if (element) element.textContent = status === 'saved' ? '' : (status === 'pending' ? 'Saving your preferences…' : 'Your preferences apply on this page, but server confirmation failed. Retry, or reopen preferences and choose again.');
                    if (element && status === 'failed') {
                        var retry = document.createElement('button'); retry.type = 'button'; retry.textContent = 'Retry confirmation';
                        retry.onclick = function () { window.SiteEngineConsent.retryAudit(); }; element.appendChild(retry);
                    }
                }
                function isCurrentInteraction(payload) {
                    var stored = readStoredConsent();
                    return activeEventId === payload.event_id && stored && stored.event_id === payload.event_id;
                }
                function saveAuditReceipt(payload, ready) {
                    if (!ready) return continuityReady.then(function (context) {
                        if (context && !payload.continuity_key) {
                            payload.continuity_key = context.key; payload.continuity_token = context.token;
                            if (isCurrentInteraction(payload)) storeConsent(payload);
                        }
                        return saveAuditReceipt(payload, true);
                    });
                    if (!payload || !payload.continuity_key || !window.navigator || !window.navigator.locks || !config.ajaxUrl || !config.nonce || typeof window.fetch !== 'function') {
                        if (payload && isCurrentInteraction(payload)) {
                            payload.audit_status = 'failed'; storeConsent(payload); auditStatus('failed');
                        }
                        return Promise.resolve(false);
                    }
                    var body = new URLSearchParams();
                    body.append('action', 'se_consent_save_receipt'); body.append('nonce', config.nonce); body.append('payload', JSON.stringify(payload));
                    auditStatus('pending');
                    return window.fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
                        .then(function (r) { return r.json(); })
                        .then(function (result) {
                            learnContinuity(payload, result && result.data && result.data.continuity);
                            if (!isCurrentInteraction(payload)) return false;
                            var receipt = result && result.success && result.data;
                            if (!receipt || receipt.contract_version !== 1 || receipt.event_id !== payload.event_id || !receipt.event_acknowledged || !receipt.audit_token || !receipt.receipt_id || !receipt.row_id
                                || !receipt.continuity || receipt.continuity.revision < (continuityContext() || {}).revision
                                || receipt.policy_version !== config.policyVersion || receipt.banner_version !== config.bannerVersion) throw new Error('Confirmation failed');
                            Object.assign(payload, receipt);
                            payload.audit_saved = true; payload.audit_status = 'saved'; payload.audit_row_id = receipt.row_id;
                            storeConsent(payload); auditStatus('saved'); populateAllConsentForms();
                            document.dispatchEvent(new CustomEvent('siteengineConsentAuditSaved', { detail: payload }));
                            return true;
                        })
                        .catch(function () {
                            if (!isCurrentInteraction(payload)) return false;
                            payload.audit_saved = false; payload.audit_status = 'failed'; payload.receipt_id = ''; payload.audit_token = '';
                            storeConsent(payload); auditStatus('failed'); populateAllConsentForms();
                            document.dispatchEvent(new CustomEvent('siteengineConsentAuditFailed', { detail: payload }));
                            return false;
                        });
                }
                function dispatchConsent(payload) {
                    applyGoogleConsent(payload, 'update');
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        event: 'se_consent_saved',
                        se_consent_receipt_id: payload.receipt_id, se_consent_method: payload.method,
                        se_consent_policy_version: payload.policy_version, se_consent_banner_version: payload.banner_version,
                        se_consent_analytics: payload.consent.analytics ? 'granted' : 'denied',
                        se_consent_advertising: payload.consent.advertising ? 'granted' : 'denied',
                        se_consent_personalization: payload.consent.personalization ? 'granted' : 'denied',
                        se_google_analytics_storage: payload.google_consent.analytics_storage,
                        se_google_ad_storage: payload.google_consent.ad_storage,
                        se_google_ad_user_data: payload.google_consent.ad_user_data,
                        se_google_ad_personalization: payload.google_consent.ad_personalization
                    });
                    document.dispatchEvent(new CustomEvent('siteengineConsentSaved', { detail: payload }));
                    saveAuditReceipt(payload);
                    log('saved', payload);
                }
                function saveConsent(input, method) { var payload = buildPayload(input, method); activeEventId = payload.event_id; storeConsent(payload); dispatchConsent(payload); hideBanner(); closeModal(); return payload; }
                function acceptAll() { return saveConsent({ analytics: true, advertising: true, personalization: true }, 'accept_all'); }
                function rejectNonEssential() { return saveConsent({ analytics: false, advertising: false, personalization: false }, 'reject_non_essential'); }
                function savePreferences() { return saveConsent({ analytics: getToggleValue('analytics'), advertising: getToggleValue('advertising'), personalization: getToggleValue('personalization') }, 'save_preferences'); }
                function getToggleValue(category) { var t = document.querySelector('[data-se-consent-toggle="' + category + '"]'); return t ? t.getAttribute('aria-checked') === 'true' : false; }
                function setToggleValue(category, value) { var t = document.querySelector('[data-se-consent-toggle="' + category + '"]'); if (t) t.setAttribute('aria-checked', value ? 'true' : 'false'); }
                function toggleCategory(category) { setToggleValue(category, !getToggleValue(category)); }
                function showBanner() { var b = document.getElementById('se-consent-banner'); if (b) window.setTimeout(function () { b.classList.add('se-consent-visible'); }, 200); }
                function hideBanner() { var b = document.getElementById('se-consent-banner'); if (b) b.classList.remove('se-consent-visible'); }
                // Element that had focus before the dialog opened, so focus can be
                // returned to it on close (WCAG 2.4.3 Focus Order).
                var lastFocusedBeforeModal = null;
                var FOCUSABLE = 'button:not([disabled]),[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

                function modalIsOpen() {
                    var m = document.getElementById('se-consent-modal');
                    return !!(m && m.classList.contains('se-consent-visible'));
                }
                function focusableInModal() {
                    var m = document.getElementById('se-consent-modal');
                    if (!m) return [];
                    return Array.prototype.filter.call(m.querySelectorAll(FOCUSABLE), function (el) {
                        return el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement;
                    });
                }
                function openModal() {
                    var s = getStoredConsent();
                    if (s && s.consent) { setToggleValue('analytics', !!s.consent.analytics); setToggleValue('advertising', !!s.consent.advertising); setToggleValue('personalization', !!s.consent.personalization); }
                    var m = document.getElementById('se-consent-modal'); var bd = document.getElementById('se-consent-backdrop');
                    if (!m || !bd) return;
                    lastFocusedBeforeModal = document.activeElement;
                    bd.classList.add('se-consent-visible'); m.classList.add('se-consent-visible'); m.setAttribute('aria-hidden', 'false');
                    // Hide the rest of the page from assistive technology while the
                    // dialog is open, so a virtual cursor cannot wander behind it.
                    var banner = document.getElementById('se-consent-banner');
                    if (banner) banner.setAttribute('aria-hidden', 'true');
                    // Move focus into the dialog (WCAG 2.4.3). The heading receives it
                    // so the dialog name is announced before the controls.
                    var heading = document.getElementById('se-consent-modal-title');
                    if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus(); }
                    else { var f = focusableInModal(); if (f.length) f[0].focus(); }
                }
                function closeModal() {
                    var m = document.getElementById('se-consent-modal'); var bd = document.getElementById('se-consent-backdrop');
                    if (!m || !bd) return;
                    var wasOpen = m.classList.contains('se-consent-visible');
                    bd.classList.remove('se-consent-visible'); m.classList.remove('se-consent-visible'); m.setAttribute('aria-hidden', 'true');
                    var banner = document.getElementById('se-consent-banner');
                    if (banner) banner.removeAttribute('aria-hidden');
                    // Return focus to whatever opened the dialog (WCAG 2.4.3).
                    if (wasOpen && lastFocusedBeforeModal && document.contains(lastFocusedBeforeModal)) {
                        try { lastFocusedBeforeModal.focus(); } catch (e) {}
                    }
                    lastFocusedBeforeModal = null;
                }
                function render() {
                    if (document.getElementById('se-consent-banner')) return;
                    var company = config.companyName || 'This website'; var privacyUrl = config.privacyUrl || '/privacy-policy/';
                    var banner = document.createElement('div'); banner.id = 'se-consent-banner'; banner.setAttribute('role', 'region'); banner.setAttribute('aria-label', 'Cookie consent');
                    banner.innerHTML = '<div class="se-consent-banner-inner">' +
                        '<div class="se-consent-banner-copy">' +
                        '<strong>' + escapeHtml(company) + ' respects your privacy.</strong>' +
                        '<span>We use cookies and similar technologies to measure website performance, improve your experience, and support advertising. You can accept all cookies or manage your preferences. </span>' +
                        '<a href="' + escapeAttribute(privacyUrl) + '" target="_blank" rel="noopener">Privacy Policy</a>' +
                        '</div>' +
                        '<div class="se-consent-actions">' +
                        '<button type="button" class="se-consent-button se-consent-button-secondary" data-se-consent-action="manage">Manage Preferences</button>' +
                        '<button type="button" class="se-consent-button se-consent-button-primary" data-se-consent-action="accept">Accept All</button>' +
                        '</div></div>';
                    var backdrop = document.createElement('div'); backdrop.id = 'se-consent-backdrop';
                    var modal = document.createElement('div'); modal.id = 'se-consent-modal';
                    modal.setAttribute('role', 'dialog'); modal.setAttribute('aria-modal', 'true'); modal.setAttribute('aria-hidden', 'true'); modal.setAttribute('aria-labelledby', 'se-consent-modal-title');
                    modal.innerHTML = '<div class="se-consent-modal-inner">' +
                        '<h2 class="se-consent-modal-title" id="se-consent-modal-title">Manage Cookie Preferences</h2>' +
                        '<p class="se-consent-modal-copy">Choose how this site may use cookies and similar technologies. Necessary cookies are always active because they keep the website secure and functional.</p>' +
                        categoryHtml('Necessary', 'Required for security, page delivery, forms, and basic site functionality.', 'necessary', true, true) +
                        categoryHtml('Analytics', 'Helps us understand visits, traffic sources, and website performance.', 'analytics', false, false) +
                        categoryHtml('Advertising', 'Helps measure advertising performance and improve campaign attribution.', 'advertising', false, false) +
                        categoryHtml('Personalization', 'Allows advertising and website experiences to be tailored based on interactions.', 'personalization', false, false) +
                        '<div class="se-consent-modal-actions">' +
                        '<button type="button" class="se-consent-button se-consent-button-secondary" data-se-consent-action="reject">Reject Non-Essential</button>' +
                        '<button type="button" class="se-consent-button se-consent-button-secondary" data-se-consent-action="close">Cancel</button>' +
                        '<button type="button" class="se-consent-button se-consent-button-primary" data-se-consent-action="save">Save Preferences</button>' +
                        '</div></div>';
                    document.body.appendChild(banner); document.body.appendChild(backdrop); document.body.appendChild(modal);
                    bindEvents(); bindFormPopulation();
                    var stored = getStoredConsent();
                    if (!stored) { showBanner(); }
                    else { activeEventId = stored.event_id; stored.google_consent = googleConsentFromCategories(stored.consent); window.SiteEngineConsent.state = stored; applyGoogleConsent(stored, 'update'); if (stored.audit_status !== 'saved') showBanner(); }
                }
                function categoryHtml(title, description, key, checked, locked) {
                    return '<div class="se-consent-category"><div class="se-consent-category-row">' +
                        '<div><h3>' + escapeHtml(title) + '</h3><p>' + escapeHtml(description) + '</p></div>' +
                        '<button type="button" class="se-consent-toggle" role="switch" data-se-consent-toggle="' + escapeAttribute(key) + '" aria-checked="' + (checked?'true':'false') + '" aria-disabled="' + (locked?'true':'false') + '" aria-label="' + escapeAttribute(title) + '"></button>' +
                        '</div></div>';
                }
                function bindEvents() {
                    document.addEventListener('click', function (event) {
                        var actionEl = event.target.closest('[data-se-consent-action]');
                        var toggleEl = event.target.closest('[data-se-consent-toggle]');
                        if (actionEl) {
                            var action = actionEl.getAttribute('data-se-consent-action');
                            if (action === 'accept') acceptAll();
                            else if (action === 'manage') openModal();
                            else if (action === 'reject') rejectNonEssential();
                            else if (action === 'save') savePreferences();
                            else if (action === 'close') closeModal();
                        }
                        if (toggleEl) {
                            if (toggleEl.getAttribute('aria-disabled') === 'true') return;
                            toggleCategory(toggleEl.getAttribute('data-se-consent-toggle'));
                        }
                    });
                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') { closeModal(); return; }

                        // WCAG 2.1.2 No Keyboard Trap / 2.4.3 Focus Order — while the
                        // dialog is open, Tab cycles within it instead of moving to the
                        // page behind the backdrop.
                        if (event.key !== 'Tab' || !modalIsOpen()) return;

                        var focusable = focusableInModal();
                        if (!focusable.length) return;

                        var first = focusable[0];
                        var last = focusable[focusable.length - 1];
                        var active = document.activeElement;
                        var modal = document.getElementById('se-consent-modal');

                        // Focus sitting on the heading (or outside) enters at the edges.
                        if (!modal || !modal.contains(active) || active === document.getElementById('se-consent-modal-title')) {
                            event.preventDefault();
                            (event.shiftKey ? last : first).focus();
                            return;
                        }
                        if (event.shiftKey && active === first) { event.preventDefault(); last.focus(); }
                        else if (!event.shiftKey && active === last) { event.preventDefault(); first.focus(); }
                    });
                }
                function escapeHtml(value) { return String(value).replace(/[&<>'"]/g, function (c) { return { '&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;' }[c]; }); }
                function escapeAttribute(value) { return escapeHtml(value).replace(/`/g, '&#096;'); }

                window.SiteEngineConsent = window.SiteEngineConsent || {};
                window.SiteEngineConsent.version = config.version || '0.5.0';
                window.SiteEngineConsent.state = getStoredConsent();
                window.SiteEngineConsent.acceptAll = acceptAll;
                window.SiteEngineConsent.rejectNonEssential = rejectNonEssential;

                window.SiteEngineConsent.saveConsent = saveConsent;
                window.SiteEngineConsent.openPreferences = openModal;
                window.SiteEngineConsent.getConsent = getStoredConsent;
                window.SiteEngineConsent.retryAudit = function () { var payload = getStoredConsent(); if (!payload || payload.audit_status === 'saved') return Promise.resolve(false); var context = continuityContext(); if (!payload.continuity_key && context) payload.continuity_key = context.key; activeEventId = payload.event_id; return saveAuditReceipt(payload); };
                window.addEventListener('storage', function (event) {
                    if (event.key !== storageKey) return;
                    var state = getStoredConsent(); activeEventId = state ? state.event_id : null;
                    window.SiteEngineConsent.state = state;
                    applyGoogleConsent({ google_consent: googleConsentFromCategories(state ? normalizeConsent(state.consent) : normalizeConsent({})) }, 'update');
                    populateAllConsentForms(); if (!state) showBanner();
                });

                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', render);
                else render();
            })();
        </script>
        <?php
    }

    public function debug_marker(): void {
        if (empty($this->config['debug'])) return;
        if (is_admin()) return;
        echo "\n<!-- SiteEngine Consent loaded: " . esc_html(SE_CONSENT_VERSION) . " -->\n";
    }

    // ============================================================================
    // ADMIN VIEWER — Tools → SiteEngine Consent
    // ============================================================================

    public function register_admin_page(): void {
        // SiteEngine Base owns the central menu when available. The module
        // registry will register this same page slug under SiteEngine.
        if (class_exists('SiteEngine_Base_Module_Registry')) {
            return;
        }

        add_management_page(
            'SiteEngine Consent',
            'SiteEngine Consent',
            'manage_options',
            'se-consent',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register Consent with the SiteEngine Base control plane. This filter is
     * harmless on standalone sites because nothing consumes it without Base.
     */
    public function register_siteengine_module($modules): array {
        if (!is_array($modules)) {
            $modules = [];
        }

        $modules['consent'] = [
            'title'           => 'SiteEngine Consent',
            'menu_title'      => 'Consent',
            'page_slug'       => 'se-consent',
            'capability'      => 'manage_options',
            'order'           => 20,
            'render'          => [$this, 'render_admin_page'],
            'status_callback' => [$this, 'siteengine_module_status'],
            'requirements'    => [
                [
                    'callback' => [$this, 'has_configured_company_name'],
                    'label'    => 'Business Name',
                ],
                [
                    'callback' => [$this, 'has_configured_privacy_url'],
                    'label'    => 'Privacy Policy URL',
                ],
            ],
            'description'     => 'Consent Mode v2, receipt audit trail, retention, and server-side consent provenance.',
            'notice'          => true,
        ];

        return $modules;
    }

    /**
     * Status payload consumed by the Base dashboard after shared prerequisites
     * have passed.
     */
    public function siteengine_module_status(): array {
        $schema_ok = $this->receipts_table_exists();
        $cleanup_scheduled = (bool) wp_next_scheduled('se_consent_daily_cleanup');
        $configured = $this->configured_privacy_url();
        $cache_key = 'se_consent_privacy_ok_' . md5($configured);
        $privacy = get_transient($cache_key);

        if (!$schema_ok) {
            return [
                'status'  => 'error',
                'label'   => 'Error',
                'message' => 'Consent receipt table is missing.',
            ];
        }

        if (!$cleanup_scheduled || 'fail' === $privacy) {
            $parts = [];
            if (!$cleanup_scheduled) {
                $parts[] = 'retention cleanup is not scheduled';
            }
            if ('fail' === $privacy) {
                $parts[] = 'privacy policy URL is unreachable';
            }
            return [
                'status'  => 'warning',
                'label'   => 'Warning',
                'message' => ucfirst(implode('; ', $parts)) . '.',
            ];
        }

        return [
            'status'  => 'ready',
            'label'   => 'Healthy',
            'message' => 'Consent receipts and retention scheduler are operational.',
        ];
    }

    private function admin_page_url(): string {
        if (class_exists('SiteEngine_Base_Module_Registry')) {
            return admin_url('admin.php?page=se-consent');
        }
        return admin_url('tools.php?page=se-consent');
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }

        // Handle export download BEFORE any HTML output. Streams CSV/JSON then exit.
        if (isset($_GET['action']) && $_GET['action'] === 'se_consent_export') {
            check_admin_referer('se_consent_export_action');

            $from   = isset($_GET['from'])   ? $this->normalize_export_date(sanitize_text_field(wp_unslash((string) $_GET['from']))) : '';
            $to     = isset($_GET['to'])     ? $this->normalize_export_date(sanitize_text_field(wp_unslash((string) $_GET['to'])))   : '';
            $format = isset($_GET['format']) ? strtolower(sanitize_text_field(wp_unslash((string) $_GET['format'])))                 : 'csv';

            if (!$from || !$to) {
                wp_die('Invalid export date range.');
            }
            if (!in_array($format, ['csv', 'json'], true)) {
                $format = 'csv';
            }

            $rows = $this->build_export_rows($from, $to);
            $filename = 'se-consent-export-' . $from . '-to-' . $to . '.' . $format;

            nocache_headers();
            if ($format === 'json') {
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo wp_json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $out = fopen('php://output', 'w');
                $this->stream_csv_rows($rows, $out);
                fclose($out);
            }
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'se_consent_receipts';

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, receipt_id, method, banner_version, policy_version, page_url, created_at
                 FROM {$table}
                 WHERE receipt_id LIKE %s OR page_url LIKE %s
                 ORDER BY id DESC
                 LIMIT 100",
                $like, $like
            ));
        } else {
            $rows = $wpdb->get_results("SELECT id, receipt_id, method, banner_version, policy_version, page_url, created_at FROM {$table} ORDER BY id DESC LIMIT 100");
        }

        $total           = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $count_24h       = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE created_at > UTC_TIMESTAMP() - INTERVAL 1 DAY");
        $count_7d        = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE created_at > UTC_TIMESTAMP() - INTERVAL 7 DAY");
        $method_breakdown = $wpdb->get_results("SELECT method, COUNT(*) AS n FROM {$table} WHERE created_at > UTC_TIMESTAMP() - INTERVAL 30 DAY GROUP BY method ORDER BY n DESC");
        $last_cleanup    = get_option('se_consent_last_cleanup', null);

        // Health warnings. Base is the preferred configuration authority when
        // available; legacy wp-config constants remain supported overrides.
        $warnings = [];
        $configured_privacy = $this->configured_privacy_url();
        $cache_key = 'se_consent_privacy_ok_' . md5($configured_privacy);
        $health = get_transient($cache_key);
        if ('fail' === $health) {
            $warnings[] = 'Privacy policy URL <code>' . esc_html($configured_privacy) . '</code> is not reachable. Banner is using the SiteEngine fallback template.';
        }
        if (!$this->has_configured_privacy_url()) {
            $warnings[] = class_exists('SiteEngine_Base_Module_Registry')
                ? 'Privacy Policy URL is not configured in <strong>SiteEngine → Business Data</strong>. The WordPress default URL is being used.'
                : 'No <code>SE_CONSENT_PRIVACY_URL</code> override is configured. The WordPress default privacy-policy URL is being used.';
        }
        if (!$this->has_configured_company_name()) {
            $warnings[] = class_exists('SiteEngine_Base_Module_Registry')
                ? 'Business Name is not configured in <strong>SiteEngine → Business Data</strong>. The WordPress site name is being used.'
                : 'No <code>SE_CONSENT_COMPANY_NAME</code> override is configured. The WordPress site name is being used.';
        }
        $next_cleanup = wp_next_scheduled('se_consent_daily_cleanup');
        if (!$next_cleanup) {
            $warnings[] = 'Daily retention cleanup is not scheduled. Reload any front-end page to re-register the cron.';
        }

        ?>
        <div class="wrap">
            <h1>SiteEngine Consent</h1>
            <p style="color:#666">Plugin <code><?php echo esc_html(SE_CONSENT_VERSION); ?></code> &middot; Banner hash <code><?php echo esc_html($this->config['banner_version']); ?></code> &middot; Policy <code><?php echo esc_html(SE_CONSENT_POLICY_VERSION); ?></code></p>

            <?php if (!empty($warnings)): ?>
                <?php foreach ($warnings as $w): ?>
                    <div class="notice notice-warning"><p><?php echo wp_kses_post($w); ?></p></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h2>At a glance</h2>
            <table class="widefat striped" style="max-width:720px">
                <tbody>
                    <tr><td><strong>Total receipts</strong></td><td><?php echo number_format($total); ?></td></tr>
                    <tr><td><strong>Last 24 hours</strong></td><td><?php echo number_format($count_24h); ?></td></tr>
                    <tr><td><strong>Last 7 days</strong></td><td><?php echo number_format($count_7d); ?></td></tr>
                    <tr><td><strong>Retention</strong></td><td><?php echo (int) SE_CONSENT_RETENTION_DAYS; ?> days</td></tr>
                    <tr><td><strong>Next cleanup</strong></td><td><?php echo $next_cleanup ? esc_html(date('Y-m-d H:i:s', $next_cleanup)) . ' UTC' : '<em>not scheduled</em>'; ?></td></tr>
                    <?php if (is_array($last_cleanup)): ?>
                        <tr><td><strong>Last cleanup</strong></td><td><?php echo esc_html($last_cleanup['ran_at'] ?? '—'); ?> &middot; deleted <?php echo (int) ($last_cleanup['deleted'] ?? 0); ?> rows</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($method_breakdown)): ?>
                <h2>Method breakdown (last 30 days)</h2>
                <table class="widefat striped" style="max-width:480px">
                    <thead><tr><th>Method</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($method_breakdown as $m): ?>
                            <tr>
                                <td><code><?php echo esc_html($m->method ?: '—'); ?></code></td>
                                <td><?php echo number_format((int) $m->n); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2 style="margin-top:32px">Export receipts</h2>
            <form method="get" action="" style="max-width:760px;margin-bottom:24px">
                <input type="hidden" name="page" value="se-consent" />
                <input type="hidden" name="action" value="se_consent_export" />
                <?php wp_nonce_field('se_consent_export_action'); ?>
                <table class="form-table" style="margin-top:8px">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="se-consent-export-from">From</label></th>
                            <td><input type="date" id="se-consent-export-from" name="from" value="<?php echo esc_attr(gmdate('Y-m-d', strtotime('-30 days'))); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="se-consent-export-to">To</label></th>
                            <td><input type="date" id="se-consent-export-to" name="to" value="<?php echo esc_attr(gmdate('Y-m-d')); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="se-consent-export-format">Format</label></th>
                            <td>
                                <select id="se-consent-export-format" name="format">
                                    <option value="csv" selected>CSV</option>
                                    <option value="json">JSON</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p><button type="submit" class="button button-primary">Download</button>
                <span style="color:#666;font-size:13px;margin-left:12px">Or use WP-CLI: <code>wp se-consent export --from=YYYY-MM-DD --to=YYYY-MM-DD --format=csv</code></span></p>
            </form>

            <h2 style="margin-top:32px">Recent receipts (most recent 100)</h2>
            <form method="get" style="margin-bottom:16px">
                <input type="hidden" name="page" value="se-consent" />
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search receipt ID or page URL" style="width:360px" />
                <input type="submit" class="button" value="Search" />
                <?php if ($search !== ''): ?>
                    <a class="button" href="<?php echo esc_url($this->admin_page_url()); ?>">Clear</a>
                <?php endif; ?>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Receipt ID</th>
                        <th>Method</th>
                        <th>Banner Version</th>
                        <th>Page URL</th>
                        <th>Created (UTC)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6"><em>No receipts found.</em></td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo (int) $r->id; ?></td>
                                <td><code style="font-size:11px"><?php echo esc_html($r->receipt_id); ?></code></td>
                                <td><?php echo esc_html($r->method); ?></td>
                                <td><code style="font-size:11px"><?php echo esc_html($r->banner_version); ?></code></td>
                                <td style="max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html($r->page_url); ?></td>
                                <td><?php echo esc_html($r->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top:24px;color:#666;font-size:13px">
                Receipts are also available via direct SQL on table <code><?php echo esc_html($table); ?></code>.
                Full JSON consent payload is stored in the <code>consent_json</code> and <code>google_consent_json</code> columns.
                Per-submission consent stamps are stored in the <code>response</code> column of <code><?php echo esc_html($wpdb->prefix); ?>fluentform_submissions</code> under the <code>_se_consent_server</code> key.
            </p>
        </div>
        <?php
    }
    // ============================================================================
    // EVENING 2 — EXPORTS + HEALTH ENDPOINT
    // ============================================================================

    /**
     * Register the public health REST route.
     */
    public function register_rest_routes(): void {
        register_rest_route('se-consent/v1', '/health', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_health'],
            'permission_callback' => '__return_true', // intentionally public for fleet monitoring
        ]);
    }

    /**
     * Public health endpoint. Returns operational status JSON.
     * No secrets exposed — counts, version, scheduler state, table presence only.
     */
    public function rest_health() {
        global $wpdb;

        $table        = $wpdb->prefix . 'se_consent_receipts';
        $schema_ok    = $this->receipts_table_exists();
        $receipts_24h = 0;
        $receipts_7d  = 0;

        if ($schema_ok) {
            $receipts_24h = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
                gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)
            ));
            $receipts_7d = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
                gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS))
            ));
        }

        $cleanup_scheduled = (bool) wp_next_scheduled('se_consent_daily_cleanup');

        $last_cleanup    = get_option('se_consent_last_cleanup', null);
        $last_cleanup_at = null;
        if (is_array($last_cleanup) && !empty($last_cleanup['ran_at'])) {
            $ts = strtotime((string) $last_cleanup['ran_at']);
            $last_cleanup_at = $ts ? gmdate('Y-m-d\TH:i:s\Z', $ts) : null;
        }

        // Privacy URL health is computed by resolve_privacy_url() and cached in a transient.
        $configured = $this->configured_privacy_url();
        $cache_key  = 'se_consent_privacy_ok_' . md5($configured);
        $cached     = get_transient($cache_key);
        $privacy_url_status = in_array($cached, ['ok', 'fail'], true) ? $cached : 'unknown';

        $ok = $schema_ok && $cleanup_scheduled && $privacy_url_status !== 'fail';

        return rest_ensure_response([
            'ok'                 => (bool) $ok,
            'version'            => SE_CONSENT_VERSION,
            'banner_hash'        => $this->compute_banner_hash(),
            'policy_version'     => SE_CONSENT_POLICY_VERSION,
            'schema'             => $schema_ok ? 'ok' : 'missing',
            'receipts_24h'       => $receipts_24h,
            'receipts_7d'        => $receipts_7d,
            'cleanup_scheduled'  => $cleanup_scheduled,
            'last_cleanup_at'    => $last_cleanup_at,
            'privacy_url_status' => $privacy_url_status,
        ]);
    }

    /**
     * WP-CLI: wp se-consent export --from=YYYY-MM-DD --to=YYYY-MM-DD [--format=csv|json] [--output=/path]
     *
     * @param array<int, string>   $args
     * @param array<string, mixed> $assoc_args
     */
    public function cli_export($args, $assoc_args): void {
        $from = isset($assoc_args['from']) ? $this->normalize_export_date((string) $assoc_args['from']) : '';
        $to   = isset($assoc_args['to'])   ? $this->normalize_export_date((string) $assoc_args['to'])   : '';
        if (!$from || !$to) {
            \WP_CLI::error('Both --from=YYYY-MM-DD and --to=YYYY-MM-DD are required.');
        }

        $format = isset($assoc_args['format']) ? strtolower((string) $assoc_args['format']) : 'csv';
        if (!in_array($format, ['csv', 'json'], true)) {
            \WP_CLI::error('--format must be csv or json.');
        }

        $rows   = $this->build_export_rows($from, $to);
        $output = isset($assoc_args['output']) ? (string) $assoc_args['output'] : '';

        if ($format === 'json') {
            $content = wp_json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            if ($output !== '') {
                file_put_contents($output, $content);
            } else {
                echo $content;
            }
        } else {
            $handle = $output !== '' ? fopen($output, 'w') : fopen('php://stdout', 'w');
            if (!$handle) {
                \WP_CLI::error('Unable to open output stream.');
            }
            $this->stream_csv_rows($rows, $handle);
            fclose($handle);
        }

        fwrite(STDERR, 'Exported ' . count($rows) . ' rows' . PHP_EOL);
    }

    /**
     * Shared row builder used by WP-CLI command AND admin form download.
     * Decodes consent_json / google_consent_json into flat columns.
     *
     * @return array<int, array<string, mixed>>
     */
    private function build_export_rows(string $from, string $to): array {
        global $wpdb;

        if (!$this->receipts_table_exists()) {
            return [];
        }

        $table = $wpdb->prefix . 'se_consent_receipts';
        $start = $from . ' 00:00:00';
        $end   = $to   . ' 23:59:59';

        $records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, receipt_id, method, policy_version, banner_version, page_url, referrer_url, ip_hash, user_agent_hash, created_at, consent_json, google_consent_json
                 FROM {$table}
                 WHERE created_at >= %s AND created_at <= %s
                 ORDER BY created_at ASC, id ASC",
                $start, $end
            ),
            ARRAY_A
        );

        $rows = [];
        foreach ((array) $records as $record) {
            $consent = json_decode((string) ($record['consent_json'] ?? ''), true);
            $google  = json_decode((string) ($record['google_consent_json'] ?? ''), true);
            $consent = is_array($consent) ? $consent : [];
            $google  = is_array($google)  ? $google  : [];

            $rows[] = [
                'id'                        => (int) ($record['id'] ?? 0),
                'receipt_id'                => (string) ($record['receipt_id'] ?? ''),
                'method'                    => (string) ($record['method'] ?? ''),
                'policy_version'            => (string) ($record['policy_version'] ?? ''),
                'banner_version'            => (string) ($record['banner_version'] ?? ''),
                'page_url'                  => (string) ($record['page_url'] ?? ''),
                'referrer_url'              => (string) ($record['referrer_url'] ?? ''),
                'ip_hash'                   => (string) ($record['ip_hash'] ?? ''),
                'user_agent_hash'           => (string) ($record['user_agent_hash'] ?? ''),
                'created_at'                => (string) ($record['created_at'] ?? ''),
                'consent_analytics'         => !empty($consent['analytics'])       ? '1' : '0',
                'consent_advertising'       => !empty($consent['advertising'])     ? '1' : '0',
                'consent_personalization'   => !empty($consent['personalization']) ? '1' : '0',
                'google_ad_storage'         => $this->normalize_google_value($google['ad_storage']         ?? 'denied'),
                'google_analytics_storage'  => $this->normalize_google_value($google['analytics_storage']  ?? 'denied'),
                'google_ad_user_data'       => $this->normalize_google_value($google['ad_user_data']       ?? 'denied'),
                'google_ad_personalization' => $this->normalize_google_value($google['ad_personalization'] ?? 'denied'),
            ];
        }

        return $rows;
    }

    /**
     * Stream CSV rows to a file handle with the canonical column order.
     * @param resource $handle
     */
    private function stream_csv_rows(array $rows, $handle): void {
        $columns = [
            'id', 'receipt_id', 'method', 'policy_version', 'banner_version',
            'page_url', 'referrer_url', 'ip_hash', 'user_agent_hash', 'created_at',
            'consent_analytics', 'consent_advertising', 'consent_personalization',
            'google_ad_storage', 'google_analytics_storage', 'google_ad_user_data', 'google_ad_personalization',
        ];
        fputcsv($handle, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }
    }

    private function normalize_export_date(string $date): string {
        $date = trim($date);
        if ($date === '') return '';
        $ts = strtotime($date);
        if (!$ts) return '';
        $normalized = gmdate('Y-m-d', $ts);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) return '';
        return $normalized;
    }

    private function normalize_google_value($value): string {
        return $value === 'granted' ? 'granted' : 'denied';
    }

    private function receipts_table_exists(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'se_consent_receipts';
        $like  = $wpdb->esc_like($table);
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like)) === $table;
    }
}

add_action('plugins_loaded', static function () {
    SiteEngine_Consent::instance();
}, 20);
