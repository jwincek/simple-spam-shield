<?php
/**
 * Request helpers — single source of truth for reading the client request.
 *
 * Centralizes visitor-IP detection so every guard and the allowlist agree
 * on the same value. By default only the direct connection IP
 * (REMOTE_ADDR) is trusted; forwarded headers are honored *only* when the
 * admin explicitly enables the trusted-proxy option, because
 * X-Forwarded-For is attacker-controlled and would otherwise let a visitor
 * spoof an allowlisted IP and bypass every guard.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace Simple_Spam_Shield\Core;

final class Request {

	/**
	 * Resolve the visitor's IP address.
	 *
	 * @return string A validated IP, or '0.0.0.0' when none can be determined.
	 */
	public static function ip(): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		// Only consult forwarded headers when the site is explicitly
		// configured to sit behind a trusted reverse proxy / load balancer.
		if ( get_option( 'simple_spam_shield_trust_proxy', false ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$raw       = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$forwarded = trim( explode( ',', $raw )[0] );

			if ( filter_var( $forwarded, FILTER_VALIDATE_IP ) ) {
				return $forwarded;
			}
		}

		return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '0.0.0.0';
	}
}
