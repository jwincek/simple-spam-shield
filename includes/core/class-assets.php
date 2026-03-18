<?php
/**
 * Front-end asset enqueuing — honeypot CSS and time-gate JS.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Core;

final class Assets {

	/**
	 * Enqueue front-end scripts and styles.
	 */
	public static function enqueue(): void {
		if ( ! (bool) get_option( 'sss_enabled', true ) ) {
			return;
		}

		wp_enqueue_style(
			'sss-honeypot',
			SSS_URL . 'assets/css/honeypot.css',
			[],
			SSS_VERSION
		);

		wp_enqueue_script(
			'sss-guard',
			SSS_URL . 'assets/js/guard.js',
			[],
			SSS_VERSION,
			[ 'in_footer' => true ]
		);

		wp_localize_script( 'sss-guard', 'sssGuard', [
			'nonce'     => wp_create_nonce( \SSS\Guards\Nonce::ACTION ),
			'timestamp' => time(),
		] );
	}
}
