<?php
/**
 * Nonce guard — validates a WordPress nonce on the submission.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Guards;

final class Nonce extends Abstract_Guard {

	public const ACTION = 'sss_form_submit';
	public const FIELD  = 'sss_nonce';

	public function check( array $data, string $context ): \WP_Error|true {
		$nonce = $data[ self::FIELD ] ?? '';

		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			return $this->fail(
				__( 'Security check failed — please refresh the page and try again.', 'simple-spam-shield' )
			);
		}

		return true;
	}
}
