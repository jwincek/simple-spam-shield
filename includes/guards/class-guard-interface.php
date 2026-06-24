<?php
/**
 * Guard interface — every spam-check guard implements this.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace Simple_Spam_Shield\Guards;

interface Guard_Interface {

	/**
	 * Run the spam check.
	 *
	 * @param array  $data    Submission data.
	 * @param string $context 'comment' | 'woo_review' | 'jetpack_form'.
	 * @return \WP_Error|true  True on pass, WP_Error on fail.
	 */
	public function check( array $data, string $context ): \WP_Error|true;

	/**
	 * Whether this guard is enabled in settings.
	 */
	public function is_enabled(): bool;

	/**
	 * Priority weight (higher = runs first).
	 */
	public function get_weight(): int;

	/**
	 * Guard slug identifier.
	 */
	public function get_slug(): string;
}
