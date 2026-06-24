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
$simple_spam_shield_table_name = $wpdb->prefix . 'simple_spam_shield_spam_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$simple_spam_shield_table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifier cannot be a prepared placeholder; value is the plugin's own prefixed table name.

// 2. Delete all plugin options.
$simple_spam_shield_options = [
	'simple_spam_shield_enabled',
	'simple_spam_shield_hard_block',
	'simple_spam_shield_protect_comments',
	'simple_spam_shield_protect_woo_reviews',
	'simple_spam_shield_protect_jetpack_forms',
	'simple_spam_shield_honeypot_enabled',
	'simple_spam_shield_time_gate_enabled',
	'simple_spam_shield_time_gate_seconds',
	'simple_spam_shield_nonce_enabled',
	'simple_spam_shield_link_limit_enabled',
	'simple_spam_shield_link_limit_max',
	'simple_spam_shield_keyword_block_enabled',
	'simple_spam_shield_blocked_keywords',
	'simple_spam_shield_duplicate_enabled',
	'simple_spam_shield_behavioral_enabled',
	'simple_spam_shield_behavioral_threshold',
	'simple_spam_shield_log_blocked',
	'simple_spam_shield_log_retention_days',
	'simple_spam_shield_block_log',
	'simple_spam_shield_allowlist',
	'simple_spam_shield_trust_proxy',
	'simple_spam_shield_token_secret',
	'simple_spam_shield_db_version',
];

foreach ( $simple_spam_shield_options as $simple_spam_shield_option ) {
	delete_option( $simple_spam_shield_option );
}

// 3. Clean up any duplicate-detection transients.
// Transients use the pattern simple_spam_shield_dup_* but we cannot enumerate them
// without a database query, so clean them via a direct DELETE.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_simple_spam_shield_dup_%' OR option_name LIKE '_transient_timeout_simple_spam_shield_dup_%'"
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// 4. Delete the stats transient.
delete_transient( 'simple_spam_shield_stats' );

// 5. Clear the scheduled retention-purge cron event (belt-and-suspenders;
// deactivation already clears it).
wp_clear_scheduled_hook( 'simple_spam_shield_purge_logs' );
