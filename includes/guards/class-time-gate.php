<?php
/**
 * Time Gate guard — rejects submissions completed faster than a human.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Guards;

final class Time_Gate extends Abstract_Guard {

	public function check( array $data, string $context ): \WP_Error|true {
		$min_seconds = (int) get_option( 'sss_time_gate_seconds', $this->config['min_seconds'] ?? 3 );

		$timestamp = $data['sss_form_loaded'] ?? '';

		if ( empty( $timestamp ) ) {
			// No timestamp present — could be a bot that didn't load JS.
			return $this->fail(
				__( 'Submission rejected — please enable JavaScript.', 'simple-spam-shield' )
			);
		}

		$elapsed = time() - (int) $timestamp;

		if ( $elapsed < $min_seconds ) {
			return $this->fail(
				__( 'Submission rejected — please slow down.', 'simple-spam-shield' )
			);
		}

		return true;
	}
}
