<?php
/**
 * Simple Spam Shield — Uninstall Script.
 *
 * Runs when the plugin is deleted (not just deactivated) from WordPress.
 * Cleans up all plugin data: options, transients, and the custom database table.
 *
 * Ported from Comment & Form Guard's uninstall.php.
 *
 * @package Simple_Spam_Shield
 */

// Exit if WP_UNINSTALL_PLUGIN is not defined.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Drop the custom spam logs database table.
$sss_table_name = $wpdb->prefix . 'sss_spam_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$sss_table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifier cannot be a prepared placeholder; value is the plugin's own prefixed table name.

// 2. Delete all plugin options.
$sss_options = [
	'sss_enabled',
	'sss_protect_comments',
	'sss_protect_woo_reviews',
	'sss_protect_jetpack_forms',
	'sss_honeypot_enabled',
	'sss_time_gate_enabled',
	'sss_time_gate_seconds',
	'sss_nonce_enabled',
	'sss_link_limit_enabled',
	'sss_link_limit_max',
	'sss_keyword_block_enabled',
	'sss_blocked_keywords',
	'sss_duplicate_enabled',
	'sss_behavioral_enabled',
	'sss_behavioral_threshold',
	'sss_log_blocked',
	'sss_log_retention_days',
	'sss_block_log',
	'sss_allowlist',
	'sss_trust_proxy',
	'sss_db_version',
];

foreach ( $sss_options as $sss_option ) {
	delete_option( $sss_option );
}

// 3. Clean up any duplicate-detection transients.
// Transients use the pattern sss_dup_* but we cannot enumerate them
// without a database query, so clean them via a direct DELETE.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sss_dup_%' OR option_name LIKE '_transient_timeout_sss_dup_%'"
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// 4. Delete the stats transient.
delete_transient( 'sss_stats' );

// 5. Clear the scheduled retention-purge cron event (belt-and-suspenders;
// deactivation already clears it).
wp_clear_scheduled_hook( 'sss_purge_logs' );
