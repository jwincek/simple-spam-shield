<?php
/**
 * Abstract base guard — shared logic for all guards.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Guards;

abstract class Abstract_Guard implements Guard_Interface {

	protected string $slug;
	protected array $config;

	public function __construct( string $slug, array $config ) {
		$this->slug   = $slug;
		$this->config = $config;
	}

	public function get_slug(): string {
		return $this->slug;
	}

	public function get_weight(): int {
		return (int) ( $this->config['weight'] ?? 50 );
	}

	public function is_enabled(): bool {
		return (bool) get_option( "sss_{$this->slug}_enabled", $this->config['enabled_by_default'] ?? true );
	}

	/**
	 * Helper: build a WP_Error for a failed guard.
	 */
	protected function fail( string $message ): \WP_Error {
		return new \WP_Error(
			"sss_{$this->slug}_failed",
			$message,
			[ 'status' => 403 ]
		);
	}
}
