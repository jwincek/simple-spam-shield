<?php
/**
 * Guard Runner — executes the spam-check pipeline.
 *
 * This is the "abilities layer" equivalent from the Petstablished Sync
 * architecture: thin, testable operations with clear inputs and outputs.
 * Each guard is a class implementing Guard_Interface. The runner loads
 * them from config, sorts by weight, and runs them in order.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Core;

use SSS\Guards\Guard_Interface;

final class Guard_Runner {

	/** @var Guard_Interface[] */
	private static array $guards = [];

	/**
	 * Initialize: register all guards defined in config/guards.json.
	 */
	public static function init(): void {
		$definitions = Config::get( 'guards', 'guards', [] );

		$guard_map = [
			'honeypot'      => \SSS\Guards\Honeypot::class,
			'time_gate'     => \SSS\Guards\Time_Gate::class,
			'nonce'         => \SSS\Guards\Nonce::class,
			'link_limit'    => \SSS\Guards\Link_Limit::class,
			'keyword_block' => \SSS\Guards\Keyword_Block::class,
		];

		foreach ( $definitions as $slug => $def ) {
			if ( ! isset( $guard_map[ $slug ] ) ) {
				continue;
			}

			$class = $guard_map[ $slug ];
			/** @var Guard_Interface $instance */
			$instance = new $class( $slug, $def );

			self::$guards[] = $instance;
		}

		// Sort by weight descending (highest priority first).
		usort( self::$guards, fn( Guard_Interface $a, Guard_Interface $b ) => $b->get_weight() <=> $a->get_weight() );
	}

	/**
	 * Run all enabled guards against a submission.
	 *
	 * @param array  $data    Submission data (comment fields, form fields, etc.).
	 * @param string $context One of 'comment', 'woo_review', 'jetpack_form'.
	 * @return \WP_Error|true  True if all guards pass, WP_Error on first failure.
	 */
	public static function run( array $data, string $context ): \WP_Error|true {
		if ( ! (bool) get_option( 'sss_enabled', true ) ) {
			return true;
		}

		foreach ( self::$guards as $guard ) {
			if ( ! $guard->is_enabled() ) {
				continue;
			}

			$result = $guard->check( $data, $context );

			if ( is_wp_error( $result ) ) {
				self::log_block( $guard->get_slug(), $context, $result->get_error_message() );
				return $result;
			}
		}

		return true;
	}

	/**
	 * Optionally log a blocked submission.
	 */
	private static function log_block( string $guard, string $context, string $reason ): void {
		if ( ! (bool) get_option( 'sss_log_blocked', true ) ) {
			return;
		}

		$log = get_option( 'sss_block_log', [] );

		// Keep last 100 entries.
		if ( count( $log ) >= 100 ) {
			$log = array_slice( $log, -99 );
		}

		$log[] = [
			'time'    => current_time( 'mysql' ),
			'guard'   => $guard,
			'context' => $context,
			'reason'  => $reason,
			'ip'      => self::get_ip(),
		];

		update_option( 'sss_block_log', $log, false );
	}

	/**
	 * Get the submitter's IP address.
	 */
	private static function get_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		return sanitize_text_field( $ip );
	}
}
