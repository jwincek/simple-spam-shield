<?php
/**
 * Plugin Name: Simple Spam Shield
 * Description: Config-driven spam prevention for Comments, WooCommerce Reviews, and Jetpack Contact Form blocks — no external services required.
 * Version:     1.0.0
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Author:      Jerome Wincek / Claude Opus 4.6
 * Text Domain: simple-spam-shield
 * License:     GPL-2.0-or-later
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'SSS_VERSION', '1.0.0' );
define( 'SSS_FILE', __FILE__ );
define( 'SSS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSS_URL', plugin_dir_url( __FILE__ ) );

// Autoload namespaced classes: SSS\Core\Config → includes/core/class-config.php
spl_autoload_register( function ( string $class ): void {
	if ( ! str_starts_with( $class, 'SSS\\' ) ) {
		return;
	}

	$relative = substr( $class, strlen( 'SSS\\' ) );
	$parts    = explode( '\\', $relative );
	$name     = array_pop( $parts );
	$dir      = strtolower( implode( '/', $parts ) );
	$file     = SSS_DIR . 'includes/' . $dir . '/class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Plugin activation — set default options.
 */
register_activation_hook( __FILE__, function (): void {
	\SSS\Core\Config::init( SSS_DIR . 'config/' );

	$defaults = \SSS\Core\Config::get( 'settings', 'defaults', [] );
	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( "sss_{$key}" ) ) {
			add_option( "sss_{$key}", $value );
		}
	}

	// Create the spam logs database table.
	\SSS\Core\Database_Manager::create_table();

	// Schedule the daily log-retention purge.
	if ( ! wp_next_scheduled( 'sss_purge_logs' ) ) {
		wp_schedule_event( time(), 'daily', 'sss_purge_logs' );
	}
} );

/**
 * Plugin deactivation — clean up transients and scheduled events.
 */
register_deactivation_hook( __FILE__, function (): void {
	delete_transient( 'sss_stats' );
	wp_clear_scheduled_hook( 'sss_purge_logs' );
} );

/**
 * Daily cron callback — prune log rows older than the retention window.
 */
function sss_purge_logs(): void {
	$days = (int) get_option( 'sss_log_retention_days', 30 );
	\SSS\Core\Database_Manager::purge_older_than( $days );
}
add_action( 'sss_purge_logs', 'sss_purge_logs' );

/**
 * Initialize the plugin on plugins_loaded.
 */
function sss_init(): void {
	// 1. Config loader.
	\SSS\Core\Config::init( SSS_DIR . 'config/' );

	// 2. Guard pipeline (the "abilities" layer).
	\SSS\Core\Guard_Runner::init();

	// 3. Integration hooks — thin consumers that delegate to the guard pipeline.
	\SSS\Integrations\Comments::init();
	\SSS\Integrations\WooCommerce::init();
	\SSS\Integrations\Jetpack_Forms::init();

	// 4. Front-end assets (honeypot field + JS timer).
	add_action( 'wp_enqueue_scripts', [ \SSS\Core\Assets::class, 'enqueue' ] );

	// 5. Admin settings (admin only).
	if ( is_admin() ) {
		\SSS\Core\Admin::init();
	}

	// 6. Self-heal the retention cron for installs that predate it
	//    (the activation hook only fires on (re)activation).
	if ( ! wp_next_scheduled( 'sss_purge_logs' ) ) {
		wp_schedule_event( time(), 'daily', 'sss_purge_logs' );
	}
}
add_action( 'plugins_loaded', 'sss_init' );
