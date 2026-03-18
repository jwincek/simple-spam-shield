<?php
/**
 * Link Limit guard — rejects submissions containing too many URLs.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Guards;

final class Link_Limit extends Abstract_Guard {

	public function check( array $data, string $context ): \WP_Error|true {
		$max_links = (int) get_option( 'sss_link_limit_max', $this->config['max_links'] ?? 3 );
		$content   = $data['content'] ?? $data['comment'] ?? '';

		if ( empty( $content ) ) {
			return true;
		}

		// Count URLs using a simple regex.
		$count = preg_match_all( '#https?://[^\s<>"\']+#i', $content );

		if ( $count > $max_links ) {
			return $this->fail(
				sprintf(
					/* translators: %d: maximum allowed links */
					__( 'Submission rejected — too many links (max %d).', 'simple-spam-shield' ),
					$max_links
				)
			);
		}

		return true;
	}
}
