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

		$timestamp = $data['simple_spam_shield_form_loaded'] ?? '';

		if ( empty( $timestamp ) ) {
			// Jetpack's form processor does not forward arbitrary hidden
			// fields through its pipeline — only recognized form field data
			// arrives in $form_data. When the timestamp is absent we cannot
			// measure elapsed time, so we skip rather than hard-fail.
			// The other guards (honeypot, nonce, keywords, link limit) still
			// protect the submission.
			if ( 'jetpack_form' === $context ) {
				return true;
			}

			return $this->fail(
				__( 'Submission rejected — please enable JavaScript.', 'simple-spam-shield' )
			);
		}

		$elapsed = time() - (int) $timestamp;

		// Guard against negative elapsed time caused by client/server
		// clock skew (the timestamp is now generated client-side via
		// Date.now()). A negative value means the client clock is ahead
		// of the server clock — treat it as zero elapsed.
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
