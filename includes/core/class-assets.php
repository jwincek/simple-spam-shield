<?php
/**
 * Front-end asset enqueuing — honeypot CSS and time-gate JS.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace Simple_Spam_Shield\Core;

final class Assets {

	/**
	 * Enqueue front-end scripts and styles.
	 */
	public static function enqueue(): void {
		if ( ! (bool) get_option( 'simple_spam_shield_enabled', true ) ) {
			return;
		}

		wp_enqueue_style(
			'simple-spam-shield-honeypot',
			SIMPLE_SPAM_SHIELD_URL . 'assets/css/honeypot.css',
			[],
			SIMPLE_SPAM_SHIELD_VERSION
		);

		wp_enqueue_script(
			'simple-spam-shield-guard',
			SIMPLE_SPAM_SHIELD_URL . 'assets/js/guard.js',
			[],
			SIMPLE_SPAM_SHIELD_VERSION,
			[ 'in_footer' => true ]
		);

		// A signed token (issue time + HMAC). The time gate reads the signed
		// issue time; the signature guard verifies authenticity. Because the
		// HMAC does not expire, this stays valid under full-page caching.
		wp_localize_script( 'simple-spam-shield-guard', 'simpleSpamShieldGuard', [
			'token' => \Simple_Spam_Shield\Core\Token::issue(),
		] );
	}
}
