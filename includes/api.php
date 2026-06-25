<?php
/**
 * Public integration API.
 *
 * Stable, prefixed functions other plugins can call to protect their own
 * forms with Simple Spam Shield's guards. The plugin's internal classes are
 * intentionally NOT part of this contract — integrate through these functions
 * so internals can change freely. Every function fails open (treats the
 * submission as clean / returns nothing) when the plugin is unavailable, so
 * a host plugin degrades gracefully if Simple Spam Shield is deactivated.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'simple_spam_shield_check' ) ) {
	/**
	 * Check a form submission against the enabled guards.
	 *
	 * The plugin's own hidden fields (honeypot, signed token, behavioral data)
	 * are read from $_POST automatically; pass only the human-meaningful
	 * fields. Wrap the call in a function_exists() check so your plugin keeps
	 * working when Simple Spam Shield is not active.
	 *
	 *     $result = simple_spam_shield_check(
	 *         array( 'content' => $message, 'author' => $name, 'email' => $email ),
	 *         'acme_contact_form'
	 *     );
	 *     if ( is_wp_error( $result ) ) { ... }
	 *
	 * @param array  $fields  Recognized keys: 'content', 'author', 'email'.
	 * @param string $context A short label for the form type (shown in the log).
	 * @return true|\WP_Error  True if clean, WP_Error if a guard blocked it.
	 */
	function simple_spam_shield_check( array $fields, string $context = 'custom' ) {
		if ( ! class_exists( '\Simple_Spam_Shield\Core\Guard_Runner' ) ) {
			return true;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Anti-spam check on a third-party form submission; values are sanitized on read.
		$data = array(
			'content'                            => isset( $fields['content'] ) ? (string) $fields['content'] : '',
			'author'                             => isset( $fields['author'] ) ? (string) $fields['author'] : '',
			'email'                              => isset( $fields['email'] ) ? (string) $fields['email'] : '',
			'simple_spam_shield_website_url'     => sanitize_text_field( wp_unslash( $_POST['simple_spam_shield_website_url'] ?? '' ) ),
			'simple_spam_shield_form_loaded'     => sanitize_text_field( wp_unslash( $_POST['simple_spam_shield_form_loaded'] ?? '' ) ),
			'simple_spam_shield_behavioral_data' => sanitize_textarea_field( wp_unslash( $_POST['simple_spam_shield_behavioral_data'] ?? '' ) ),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return \Simple_Spam_Shield\Core\Guard_Runner::run( $data, $context );
	}
}

if ( ! function_exists( 'simple_spam_shield_protect_selector' ) ) {
	/**
	 * Register a CSS selector whose forms should receive the protection fields
	 * (honeypot, signed token, behavioral data) on the front end.
	 *
	 * Call on or before `wp_enqueue_scripts`:
	 *
	 *     add_action( 'wp_enqueue_scripts', function () {
	 *         if ( function_exists( 'simple_spam_shield_protect_selector' ) ) {
	 *             simple_spam_shield_protect_selector( '#acme-contact-form' );
	 *         }
	 *     } );
	 *
	 * @param string $selector A CSS selector, e.g. '#acme-contact-form'.
	 */
	function simple_spam_shield_protect_selector( string $selector ): void {
		if ( '' === $selector ) {
			return;
		}

		add_filter(
			'simple_spam_shield_form_selectors',
			static function ( array $selectors ) use ( $selector ): array {
				$selectors[] = $selector;
				return $selectors;
			}
		);
	}
}

if ( ! function_exists( 'simple_spam_shield_field_markup' ) ) {
	/**
	 * Return the hidden protection fields to embed inside a form.
	 *
	 * Use this when you would rather place the fields explicitly than match the
	 * form by selector. Echo the (already-escaped) result inside your <form>;
	 * the front-end script enhances the form automatically.
	 *
	 * @return string Hidden-input HTML, or '' when the plugin is unavailable.
	 */
	function simple_spam_shield_field_markup(): string {
		if ( ! class_exists( '\Simple_Spam_Shield\Core\Assets' ) ) {
			return '';
		}

		return \Simple_Spam_Shield\Core\Assets::field_markup();
	}
}
