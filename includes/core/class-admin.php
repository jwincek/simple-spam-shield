<?php
/**
 * Admin settings — provides WP admin pages for configuring the plugin
 * and viewing spam logs.
 *
 * Now includes: allowlist field, behavioral analysis threshold, and
 * a dedicated Spam Logs subpage using WP_List_Table backed by a
 * custom database table (all ported from Comment & Form Guard).
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace Simple_Spam_Shield\Core;

final class Admin {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_menus' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_init', [ __CLASS__, 'add_privacy_policy_content' ] );
		add_action( 'admin_init', [ Database_Manager::class, 'create_table' ] );
	}

	/**
	 * Register suggested privacy-policy text.
	 *
	 * The plugin records the IP address, user agent, and a content excerpt
	 * of blocked submissions in a custom table, so site owners should
	 * disclose this in their privacy policy.
	 */
	public static function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			/* translators: %s: number of days entries are retained, or "indefinitely". */
			__( 'Simple Spam Shield blocks spam submissions on comment, review, and contact forms. When a submission is blocked, the plugin records the visitor IP address, browser user-agent string, and a short excerpt of the submitted content in a log on this site, to help the site owner review false positives and tune spam protection. These entries are stored for %s and are not shared with any third party.', 'simple-spam-shield' ),
			(int) get_option( 'simple_spam_shield_log_retention_days', 30 ) > 0
				/* translators: %d: number of days. */
				? sprintf( _n( '%d day', '%d days', (int) get_option( 'simple_spam_shield_log_retention_days', 30 ), 'simple-spam-shield' ), (int) get_option( 'simple_spam_shield_log_retention_days', 30 ) )
				: __( 'as long as the plugin is active', 'simple-spam-shield' )
		);

		wp_add_privacy_policy_content( 'Simple Spam Shield', wp_kses_post( wpautop( $content ) ) );
	}

	// ------------------------------------------------------------------
	// Menu registration
	// ------------------------------------------------------------------

	/**
	 * Add a top-level menu with Settings and Spam Logs subpages.
	 * Mirrors the Comment & Form Guard admin menu structure.
	 */
	public static function add_menus(): void {
		add_menu_page(
			__( 'Spam Shield', 'simple-spam-shield' ),
			__( 'Spam Shield', 'simple-spam-shield' ),
			'manage_options',
			'simple-spam-shield',
			[ __CLASS__, 'render_settings_page' ],
			'dashicons-shield',
			80
		);

		add_submenu_page(
			'simple-spam-shield',
			__( 'Settings', 'simple-spam-shield' ),
			__( 'Settings', 'simple-spam-shield' ),
			'manage_options',
			'simple-spam-shield',
			[ __CLASS__, 'render_settings_page' ]
		);

		add_submenu_page(
			'simple-spam-shield',
			__( 'Spam Logs', 'simple-spam-shield' ),
			__( 'Spam Logs', 'simple-spam-shield' ),
			'manage_options',
			'simple-spam-shield-spam-logs',
			[ __CLASS__, 'render_logs_page' ]
		);
	}

	// ------------------------------------------------------------------
	// Settings registration
	// ------------------------------------------------------------------

	/**
	 * Register all settings, sections, and fields for the settings page.
	 */
	public static function register_settings(): void {

		// ---- General ----
		add_settings_section( 'simple_spam_shield_general', __( 'General', 'simple-spam-shield' ), '__return_null', 'simple-spam-shield' );
		self::add_toggle( 'simple_spam_shield_enabled', __( 'Enable spam protection', 'simple-spam-shield' ), 'simple_spam_shield_general', true );

		// ---- Protection targets ----
		add_settings_section( 'simple_spam_shield_targets', __( 'Protection targets', 'simple-spam-shield' ), function () {
			echo '<p>' . esc_html__( 'Choose which form types to protect.', 'simple-spam-shield' ) . '</p>';
		}, 'simple-spam-shield' );

		self::add_toggle( 'simple_spam_shield_protect_comments', __( 'WordPress comments', 'simple-spam-shield' ), 'simple_spam_shield_targets', true );
		self::add_toggle( 'simple_spam_shield_protect_woo_reviews', __( 'WooCommerce product reviews', 'simple-spam-shield' ), 'simple_spam_shield_targets', true );
		self::add_toggle( 'simple_spam_shield_protect_jetpack_forms', __( 'Jetpack contact form blocks', 'simple-spam-shield' ), 'simple_spam_shield_targets', true );

		// ---- Guard toggles ----
		add_settings_section( 'simple_spam_shield_guards', __( 'Spam guards', 'simple-spam-shield' ), function () {
			echo '<p>' . esc_html__( 'Enable or disable individual spam checks.', 'simple-spam-shield' ) . '</p>';
		}, 'simple-spam-shield' );

		$guard_defs = Config::get( 'guards', 'guards', [] );
		foreach ( $guard_defs as $slug => $def ) {
			self::add_toggle(
				"simple_spam_shield_{$slug}_enabled",
				$def['label'] ?? $slug,
				'simple_spam_shield_guards',
				$def['enabled_by_default'] ?? true
			);
		}

		// ---- Guard-specific settings ----

		// Time gate seconds.
		self::add_number( 'simple_spam_shield_time_gate_seconds', __( 'Minimum seconds before submit', 'simple-spam-shield' ), 'simple_spam_shield_guards', 3, 1, 30, __( 'seconds', 'simple-spam-shield' ) );

		// Link limit.
		self::add_number( 'simple_spam_shield_link_limit_max', __( 'Maximum links per submission', 'simple-spam-shield' ), 'simple_spam_shield_guards', 3, 0, 50 );

		// Behavioral threshold.
		register_setting( 'simple-spam-shield', 'simple_spam_shield_behavioral_threshold', [
			'type'              => 'number',
			'sanitize_callback' => function ( $v ) {
				return max( 0.0, min( 1.0, (float) $v ) );
			},
			'default'           => 0.6,
		] );

		add_settings_field(
			'simple_spam_shield_behavioral_threshold',
			__( 'Behavioral suspicion threshold', 'simple-spam-shield' ),
			function () {
				$value = get_option( 'simple_spam_shield_behavioral_threshold', 0.6 );
				printf(
					'<input type="number" name="simple_spam_shield_behavioral_threshold" value="%.1f" min="0.0" max="1.0" step="0.1" class="small-text">' .
					'<p class="description">%s</p>',
					esc_attr( $value ),
					esc_html__( 'Score between 0.0 (lenient) and 1.0 (strict). Submissions scoring at or above this threshold are blocked. Default 0.6.', 'simple-spam-shield' )
				);
			},
			'simple-spam-shield',
			'simple_spam_shield_guards'
		);

		// Blocked keywords.
		register_setting( 'simple-spam-shield', 'simple_spam_shield_blocked_keywords', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		] );

		add_settings_field(
			'simple_spam_shield_blocked_keywords',
			__( 'Blocked keywords', 'simple-spam-shield' ),
			function () {
				$value = get_option( 'simple_spam_shield_blocked_keywords', '' );
				printf(
					'<textarea name="simple_spam_shield_blocked_keywords" rows="8" cols="50" class="large-text code">%s</textarea>' .
					'<p class="description">%s</p>',
					esc_textarea( $value ),
					esc_html__( 'One keyword or phrase per line. Case-insensitive.', 'simple-spam-shield' )
				);
			},
			'simple-spam-shield',
			'simple_spam_shield_guards'
		);

		// ---- Allowlist ----
		add_settings_section( 'simple_spam_shield_allowlist', __( 'Allowlist', 'simple-spam-shield' ), function () {
			echo '<p>' . esc_html__( 'Submissions from allowlisted IPs or emails bypass all guards.', 'simple-spam-shield' ) . '</p>';
		}, 'simple-spam-shield' );

		register_setting( 'simple-spam-shield', 'simple_spam_shield_allowlist', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		] );

		add_settings_field(
			'simple_spam_shield_allowlist',
			__( 'Allowed IPs and emails', 'simple-spam-shield' ),
			function () {
				$value = get_option( 'simple_spam_shield_allowlist', '' );
				printf(
					'<textarea name="simple_spam_shield_allowlist" rows="6" cols="50" class="large-text code">%s</textarea>' .
					'<p class="description">%s</p>',
					esc_textarea( $value ),
					esc_html__( 'One entry per line. Supports: exact IPs (192.168.1.1), CIDR ranges (10.0.0.0/8), exact emails (user@example.com), or email domains (@trusted.org).', 'simple-spam-shield' )
				);
			},
			'simple-spam-shield',
			'simple_spam_shield_allowlist'
		);

		// Trusted-proxy toggle — governs whether forwarded headers are
		// trusted for IP detection (allowlist + logging).
		register_setting( 'simple-spam-shield', 'simple_spam_shield_trust_proxy', [
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		] );

		add_settings_field(
			'simple_spam_shield_trust_proxy',
			__( 'Trust proxy headers for IP detection', 'simple-spam-shield' ),
			function () {
				printf(
					'<label><input type="checkbox" name="simple_spam_shield_trust_proxy" value="1" %1$s> %2$s</label><p class="description">%3$s</p>',
					checked( get_option( 'simple_spam_shield_trust_proxy', false ), true, false ),
					esc_html__( 'Use the X-Forwarded-For header to determine the visitor IP.', 'simple-spam-shield' ),
					esc_html__( 'Enable only if this site is behind a trusted reverse proxy or load balancer (e.g. Cloudflare, Nginx). When off, the direct connection IP is used. Turning this on without a trusted proxy lets visitors spoof their IP and bypass the allowlist.', 'simple-spam-shield' )
				);
			},
			'simple-spam-shield',
			'simple_spam_shield_allowlist'
		);

		// ---- Logging ----
		add_settings_section( 'simple_spam_shield_logging', __( 'Logging', 'simple-spam-shield' ), '__return_null', 'simple-spam-shield' );
		self::add_toggle( 'simple_spam_shield_log_blocked', __( 'Log blocked submissions to database', 'simple-spam-shield' ), 'simple_spam_shield_logging', true );
		self::add_number( 'simple_spam_shield_log_retention_days', __( 'Delete logs older than', 'simple-spam-shield' ), 'simple_spam_shield_logging', 30, 0, 3650, __( 'days (0 = keep forever)', 'simple-spam-shield' ) );
	}

	// ------------------------------------------------------------------
	// Page renderers
	// ------------------------------------------------------------------

	/**
	 * Render the Settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

		// Quick stats banner.
		$count = Database_Manager::get_count();
		if ( $count > 0 ) {
			echo '<div class="notice notice-info"><p>';
			printf(
				/* translators: 1: number of blocked submissions, 2: link to logs page */
				esc_html__( '%1$d submissions blocked. %2$s', 'simple-spam-shield' ),
				absint( $count ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=simple-spam-shield-spam-logs' ) ) . '">' .
				esc_html__( 'View spam logs →', 'simple-spam-shield' ) . '</a>'
			);
			echo '</p></div>';
		}

		echo '<form method="post" action="options.php">';
		settings_fields( 'simple-spam-shield' );
		do_settings_sections( 'simple-spam-shield' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the Spam Logs page with WP_List_Table.
	 */
	public static function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle "Clear all" action.
		if ( isset( $_GET['action'] ) && 'clear_all' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'simple_spam_shield_clear_all_logs' ) ) {
				Database_Manager::delete_all();
				wp_safe_redirect( admin_url( 'admin.php?page=simple-spam-shield-spam-logs&cleared=1' ) );
				exit;
			}
		}

		// Load the list table class.
		require_once SIMPLE_SPAM_SHIELD_DIR . 'admin/class-spam-logs-list-table.php';
		$table = new \Simple_Spam_Shield\Admin\Spam_Logs_List_Table();
		$table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Spam Logs', 'simple-spam-shield' ) . '</h1>';

		// Show success notices.
		if ( isset( $_GET['cleared'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'All spam logs cleared.', 'simple-spam-shield' ) . '</p></div>';
		}

		$count = Database_Manager::get_count();
		if ( $count > 0 ) {
			$clear_url = wp_nonce_url(
				admin_url( 'admin.php?page=simple-spam-shield-spam-logs&action=clear_all' ),
				'simple_spam_shield_clear_all_logs'
			);
			echo '<a href="' . esc_url( $clear_url ) . '" class="page-title-action" onclick="return confirm(\'' .
				esc_js( __( 'Delete all log entries?', 'simple-spam-shield' ) ) . '\')">' .
				esc_html__( 'Clear all logs', 'simple-spam-shield' ) . '</a>';
		}

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="simple-spam-shield-spam-logs">';
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	// ------------------------------------------------------------------
	// Field helpers
	// ------------------------------------------------------------------

	/**
	 * Register a toggle (checkbox) setting + field.
	 */
	private static function add_toggle( string $option, string $label, string $section, bool $default ): void {
		register_setting( 'simple-spam-shield', $option, [
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => $default,
		] );

		add_settings_field( $option, $label, function () use ( $option, $default ) {
			printf(
				'<input type="checkbox" name="%s" value="1" %s>',
				esc_attr( $option ),
				checked( get_option( $option, $default ), true, false )
			);
		}, 'simple-spam-shield', $section );
	}

	/**
	 * Register a number input setting + field.
	 */
	private static function add_number( string $option, string $label, string $section, int $default, int $min, int $max, string $suffix = '' ): void {
		register_setting( 'simple-spam-shield', $option, [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => $default,
		] );

		add_settings_field( $option, $label, function () use ( $option, $default, $min, $max, $suffix ) {
			$value = get_option( $option, $default );
			printf(
				'<input type="number" name="%s" value="%d" min="%d" max="%d" class="small-text">',
				esc_attr( $option ),
				esc_attr( $value ),
				absint( $min ),
				absint( $max )
			);
			if ( $suffix ) {
				echo ' ' . esc_html( $suffix );
			}
		}, 'simple-spam-shield', $section );
	}
}
