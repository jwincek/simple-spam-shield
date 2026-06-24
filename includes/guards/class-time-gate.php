<?php
/**
 * Time Gate guard — rejects submissions completed faster than a human.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace Simple_Spam_Shield\Guards;

final class Time_Gate extends Abstract_Guard {

	public function check( array $data, string $context ): \WP_Error|true {
		$min_seconds = (int) get_option( 'simple_spam_shield_time_gate_seconds', $this->config['min_seconds'] ?? 3 );

		$token = (string) ( $data['simple_spam_shield_form_loaded'] ?? '' );

		if ( '' === $token ) {
			// Jetpack's form processor does not forward arbitrary hidden
			// fields through its pipeline — only recognized form field data
			// arrives in $form_data. When the token is absent we cannot
			// measure elapsed time, so we skip rather than hard-fail.
			// The other guards (honeypot, signature, keywords, link limit)
			// still protect the submission.
			if ( 'jetpack_form' === $context ) {
				return true;
			}

			return $this->fail(
				__( 'Submission rejected — please enable JavaScript.', 'simple-spam-shield' )
			);
		}

		// The issue time is read from the server-signed token, so it cannot
		// be forged. An invalid signature means the value was tampered with
		// or did not originate from this site.
		$issued = \Simple_Spam_Shield\Core\Token::verify( $token );

		if ( false === $issued ) {
			if ( 'jetpack_form' === $context ) {
				return true;
			}

			return $this->fail(
				__( 'Submission rejected — please refresh the page and try again.', 'simple-spam-shield' )
			);
		}

		$elapsed = time() - $issued;

		// Clamp negative elapsed time (server clock moved backwards) to zero.
		if ( $elapsed < 0 ) {
			$elapsed = 0;
		}

		if ( $elapsed < $min_seconds ) {
			return $this->fail(
				__( 'Submission rejected — please slow down.', 'simple-spam-shield' )
			);
		}

		return true;
	}
}
