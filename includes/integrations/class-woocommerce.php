<?php
/**
 * WooCommerce integration — protects product reviews.
 *
 * WooCommerce reviews are WordPress comments on product post types.
 * This integration adds an extra layer that specifically targets
 * product review submissions, complementing the general Comments
 * integration for cases where WooCommerce is active.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Integrations;

use SSS\Core\Guard_Runner;

final class WooCommerce {

	/**
	 * Register hooks — only if WooCommerce is active.
	 */
	public static function init(): void {
		if ( ! (bool) get_option( 'sss_protect_woo_reviews', true ) ) {
			return;
		}

		// Wait for WooCommerce to load before hooking.
		add_action( 'woocommerce_init', [ __CLASS__, 'register_hooks' ] );
	}

	/**
	 * Register the actual review protection hooks.
	 */
	public static function register_hooks(): void {
		// Hook into WooCommerce's review validation.
		add_action( 'woocommerce_new_comment', [ __CLASS__, 'check_review' ], 1 );

		// Also inject honeypot into the review form.
		add_action( 'woocommerce_product_review_comment_form_args', [ __CLASS__, 'add_form_fields' ] );
	}

	/**
	 * Check a new WooCommerce review via the guard pipeline.
	 *
	 * @param int $comment_id The new comment ID.
	 */
	public static function check_review( int $comment_id ): void {
		// The preprocess_comment filter in the Comments integration
		// already covers this path. But if that integration is disabled,
		// this hook provides standalone WooCommerce protection.
		if ( (bool) get_option( 'sss_protect_comments', true ) ) {
			// Already handled by the Comments integration.
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		// Skip moderators.
		if ( current_user_can( 'moderate_comments' ) ) {
			return;
		}

		$data = [
			'content'         => $comment->comment_content ?? '',
			'author'          => $comment->comment_author ?? '',
			'email'           => $comment->comment_author_email ?? '',
			'sss_website_url' => sanitize_text_field( $_POST['sss_website_url'] ?? '' ),
			'sss_nonce'       => sanitize_text_field( $_POST['sss_nonce'] ?? '' ),
			'sss_form_loaded' => sanitize_text_field( $_POST['sss_form_loaded'] ?? '' ),
		];

		$result = Guard_Runner::run( $data, 'woo_review' );

		if ( is_wp_error( $result ) ) {
			// Trash the review and set an error notice.
			wp_trash_comment( $comment_id );

			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $result->get_error_message(), 'error' );
			}
		}
	}

	/**
	 * Modify WooCommerce review form args to include a note about protection.
	 *
	 * @param array $args Form arguments.
	 * @return array Modified arguments.
	 */
	public static function add_form_fields( array $args ): array {
		// The JS-based injection in guard.js handles the actual field injection.
		// This filter is a hook point for themes that override the review form.
		return $args;
	}
}
