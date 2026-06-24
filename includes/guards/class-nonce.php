<?php
/**
 * Nonce guard — validates a WordPress nonce on the submission.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace Simple_Spam_Shield\Guards;

final class Nonce extends Abstract_Guard {

	public const ACTION = 'simple_spam_shield_form_submit';
	public const FIELD  = 'simple_spam_shield_nonce';

	public function check( array $data, string $context ): \WP_Error|true {
		$nonce = $data[ self::FIELD ] ?? '';

		// Jetpack's form processor only forwards recognized form fields,
		// so our injected nonce hidden input is not present in the data.
		// Skip rather than hard-fail — other guards still protect.
		if ( empty( $nonce ) && 'jetpack_form' === $context ) {
			return true;
		}

		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			return $this->fail(
				__( 'Security check failed — please refresh the page and try again.', 'simple-spam-shield' )
			);
		}

		return true;
	}
}
