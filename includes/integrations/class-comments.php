<?php
/**
 * Comments integration — protects WordPress comments.
 *
 * This is a thin integration class (the "consumer" pattern from the
 * reference architecture). It hooks into WP's comment lifecycle and
 * delegates all spam-checking to the Guard_Runner pipeline.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Integrations;

use SSS\Core\Guard_Runner;

final class Comments {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		if ( ! (bool) get_option( 'sss_protect_comments', true ) ) {
			return;
		}

		add_filter( 'preprocess_comment', [ __CLASS__, 'check_comment' ], 1 );
	}

	/**
	 * Run the guard pipeline on a comment submission.
	 *
	 * @param array $commentdata Comment data array.
	 * @return array Unmodified data if checks pass.
	 */
	public static function check_comment( array $commentdata ): array {
		// Skip logged-in users with moderate_comments capability.
		if ( current_user_can( 'moderate_comments' ) ) {
			return $commentdata;
		}

		// Build normalized data for the guard pipeline.
		$data = self::normalize( $commentdata );

		$result = Guard_Runner::run( $data, 'comment' );

		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html( $result->get_error_message() ),
				esc_html__( 'Comment Blocked', 'simple-spam-shield' ),
				[
					'response'  => 403,
					'back_link' => true,
				]
			);
		}

		return $commentdata;
	}

	/**
	 * Normalize comment data into the format the guards expect.
	 */
	private static function normalize( array $commentdata ): array {
		return [
			'content'         => $commentdata['comment_content'] ?? '',
			'author'          => $commentdata['comment_author'] ?? '',
			'email'           => $commentdata['comment_author_email'] ?? '',
			// JS-injected fields from a public form submission; there is no
			// plugin nonce to verify at this stage (that is the optional Nonce
			// guard's job downstream). Values are sanitized on read.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			'sss_website_url' => sanitize_text_field( wp_unslash( $_POST['sss_website_url'] ?? '' ) ),
			'sss_nonce'       => sanitize_text_field( wp_unslash( $_POST['sss_nonce'] ?? '' ) ),
			'sss_form_loaded' => sanitize_text_field( wp_unslash( $_POST['sss_form_loaded'] ?? '' ) ),
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		];
	}
}
