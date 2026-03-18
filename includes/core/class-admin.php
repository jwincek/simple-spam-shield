<?php
/**
 * Admin settings — provides a WP admin page for configuring the plugin.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Core;

final class Admin {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public static function add_menu(): void {
		add_options_page(
			__( 'Spam Shield', 'simple-spam-shield' ),
			__( 'Spam Shield', 'simple-spam-shield' ),
			'manage_options',
			'simple-spam-shield',
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * Register all settings.
	 */
	public static function register_settings(): void {
		// General section.
		add_settings_section(
			'sss_general',
			__( 'General', 'simple-spam-shield' ),
			'__return_null',
			'simple-spam-shield'
		);

		self::add_toggle( 'sss_enabled', __( 'Enable spam protection', 'simple-spam-shield' ), 'sss_general', true );

		// Protection targets section.
		add_settings_section(
			'sss_targets',
			__( 'Protection targets', 'simple-spam-shield' ),
			function () {
				echo '<p>' . esc_html__( 'Choose which form types to protect.', 'simple-spam-shield' ) . '</p>';
			},
			'simple-spam-shield'
		);

		self::add_toggle( 'sss_protect_comments', __( 'WordPress comments', 'simple-spam-shield' ), 'sss_targets', true );
		self::add_toggle( 'sss_protect_woo_reviews', __( 'WooCommerce product reviews', 'simple-spam-shield' ), 'sss_targets', true );
		self::add_toggle( 'sss_protect_jetpack_forms', __( 'Jetpack contact form blocks', 'simple-spam-shield' ), 'sss_targets', true );

		// Guards section.
		add_settings_section(
			'sss_guards',
			__( 'Spam guards', 'simple-spam-shield' ),
			function () {
				echo '<p>' . esc_html__( 'Enable or disable individual spam checks.', 'simple-spam-shield' ) . '</p>';
			},
			'simple-spam-shield'
		);

		$guard_defs = Config::get( 'guards', 'guards', [] );

		foreach ( $guard_defs as $slug => $def ) {
			self::add_toggle(
				"sss_{$slug}_enabled",
				$def['label'] ?? $slug,
				'sss_guards',
				$def['enabled_by_default'] ?? true
			);
		}

		// Time gate setting.
		register_setting( 'simple-spam-shield', 'sss_time_gate_seconds', [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3,
		] );

		add_settings_field(
			'sss_time_gate_seconds',
			__( 'Minimum seconds before submit', 'simple-spam-shield' ),
			function () {
				$value = get_option( 'sss_time_gate_seconds', 3 );
				printf(
					'<input type="number" name="sss_time_gate_seconds" value="%d" min="1" max="30" class="small-text"> %s',
					esc_attr( $value ),
					esc_html__( 'seconds', 'simple-spam-shield' )
				);
			},
			'simple-spam-shield',
			'sss_guards'
		);

		// Link limit setting.
		register_setting( 'simple-spam-shield', 'sss_link_limit_max', [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3,
		] );

		add_settings_field(
			'sss_link_limit_max',
			__( 'Maximum links per submission', 'simple-spam-shield' ),
			function () {
				$value = get_option( 'sss_link_limit_max', 3 );
				printf(
					'<input type="number" name="sss_link_limit_max" value="%d" min="0" max="50" class="small-text">',
					esc_attr( $value )
				);
			},
			'simple-spam-shield',
			'sss_guards'
		);

		// Blocked keywords.
		register_setting( 'simple-spam-shield', 'sss_blocked_keywords', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		] );

		add_settings_field(
			'sss_blocked_keywords',
			__( 'Blocked keywords', 'simple-spam-shield' ),
			function () {
				$value = get_option( 'sss_blocked_keywords', '' );
				printf(
					'<textarea name="sss_blocked_keywords" rows="8" cols="50" class="large-text code">%s</textarea>' .
					'<p class="description">%s</p>',
					esc_textarea( $value ),
					esc_html__( 'One keyword or phrase per line. Case-insensitive.', 'simple-spam-shield' )
				);
			},
			'simple-spam-shield',
			'sss_guards'
		);

		// Logging section.
		add_settings_section(
			'sss_logging',
			__( 'Logging', 'simple-spam-shield' ),
			'__return_null',
			'simple-spam-shield'
		);

		self::add_toggle( 'sss_log_blocked', __( 'Log blocked submissions', 'simple-spam-shield' ), 'sss_logging', true );
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

		// Show block log if logging is enabled.
		$log = get_option( 'sss_block_log', [] );
		if ( ! empty( $log ) ) {
			echo '<div class="notice notice-info"><p>';
			printf(
				/* translators: %d: number of blocked submissions */
				esc_html__( '%d submissions blocked recently.', 'simple-spam-shield' ),
				count( $log )
			);
			echo ' <a href="' . esc_url( wp_nonce_url( admin_url( 'options-general.php?page=simple-spam-shield&clear_log=1' ), 'sss_clear_log' ) ) . '">';
			echo esc_html__( 'Clear log', 'simple-spam-shield' );
			echo '</a></p></div>';

			// Handle log clearing.
			if ( isset( $_GET['clear_log'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'sss_clear_log' ) ) {
				delete_option( 'sss_block_log' );
				echo '<script>window.location.href="' . esc_url( admin_url( 'options-general.php?page=simple-spam-shield' ) ) . '";</script>';
				return;
			}

			// Display recent blocks.
			echo '<table class="widefat striped" style="max-width:800px;margin-bottom:20px">';
			echo '<thead><tr><th>' . esc_html__( 'Time', 'simple-spam-shield' ) . '</th>';
			echo '<th>' . esc_html__( 'Guard', 'simple-spam-shield' ) . '</th>';
			echo '<th>' . esc_html__( 'Context', 'simple-spam-shield' ) . '</th>';
			echo '<th>' . esc_html__( 'IP', 'simple-spam-shield' ) . '</th></tr></thead><tbody>';

			foreach ( array_reverse( array_slice( $log, -20 ) ) as $entry ) {
				printf(
					'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					esc_html( $entry['time'] ?? '' ),
					esc_html( $entry['guard'] ?? '' ),
					esc_html( $entry['context'] ?? '' ),
					esc_html( $entry['ip'] ?? '' )
				);
			}

			echo '</tbody></table>';
		}

		echo '<form method="post" action="options.php">';
		settings_fields( 'simple-spam-shield' );
		do_settings_sections( 'simple-spam-shield' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Helper: register a toggle (checkbox) setting + field.
	 */
	private static function add_toggle( string $option, string $label, string $section, bool $default ): void {
		register_setting( 'simple-spam-shield', $option, [
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => $default,
		] );

		add_settings_field(
			$option,
			$label,
			function () use ( $option, $default ) {
				$value = get_option( $option, $default );
				printf(
					'<input type="checkbox" name="%s" value="1" %s>',
					esc_attr( $option ),
					checked( $value, true, false )
				);
			},
			'simple-spam-shield',
			$section
		);
	}
}
