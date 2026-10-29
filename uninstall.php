<?php
/**
 * SiteEngine Consent — Uninstall handler
 *
 * Runs when the plugin is DELETED via the WP admin (Plugins → Delete).
 * This is different from deactivation:
 *  - Deactivation stops the plugin from running but preserves data.
 *  - Uninstall removes the plugin file AND wipes all its data.
 *
 * On uninstall, this file:
 *  - Drops the audit table wp_se_consent_receipts
 *  - Removes all se_consent_* WordPress options
 *  - Removes any lingering scheduled cron events
 *  - Clears any transients we set
 *
 * The audit table contains consent receipts that may have compliance value.
 * If you want to preserve them, EXPORT before deleting:
 *   wp se-consent export --from=2020-01-01 --to=2099-12-31 --format=csv --output=/tmp/backup.csv
 */

// Only run when triggered by WordPress uninstall procedure.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Drop audit table.
$table = $wpdb->prefix . 'se_consent_receipts';
$wpdb->query("DROP TABLE IF EXISTS {$table}");
foreach (['se_consent_events', 'se_consent_chains'] as $suffix) {
    $continuity_table = $wpdb->prefix . $suffix;
    $wpdb->query("DROP TABLE IF EXISTS {$continuity_table}");
}

// 2. Remove all plugin options.
$options = [
    'se_consent_version',
    'se_consent_policy_version',
    'se_consent_activated_at',
    'se_consent_last_cleanup',
];
foreach ($options as $option) {
    delete_option($option);
    // Also delete from network options in case this ran on multisite.
    delete_site_option($option);
}

// 3. Unschedule any remaining cron events.
$timestamp = wp_next_scheduled('se_consent_daily_cleanup');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'se_consent_daily_cleanup');
}
wp_clear_scheduled_hook('se_consent_daily_cleanup');

// 4. Clean up transients (rate limit and privacy URL health cache).
// Transients we set follow the patterns:
//   se_consent_rl_<16-char hash>
//   se_consent_privacy_ok_<32-char md5>
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '\\_transient\\_se\\_consent\\_%'
        OR option_name LIKE '\\_transient\\_timeout\\_se\\_consent\\_%'"
);

// 5. Optional multisite cleanup (per-blog tables and options).
// Only relevant if the plugin was network-activated on a multisite install.
if (is_multisite()) {
    $site_ids = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        $per_site_table = $wpdb->prefix . 'se_consent_receipts';
        $wpdb->query("DROP TABLE IF EXISTS {$per_site_table}");
        foreach (['se_consent_events', 'se_consent_chains'] as $suffix) {
            $continuity_table = $wpdb->prefix . $suffix;
            $wpdb->query("DROP TABLE IF EXISTS {$continuity_table}");
        }
        foreach ($options as $option) {
            delete_option($option);
        }
        restore_current_blog();
    }
}
