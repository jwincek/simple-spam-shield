<?php
/**
 * Keyword Block guard — rejects submissions containing blocked keywords.
 *
 * Keywords are stored one per line in the sss_blocked_keywords option.
 * Each keyword is matched case-insensitively as a whole word boundary.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Guards;

final class Keyword_Block extends Abstract_Guard {

	public function check( array $data, string $context ): \WP_Error|true {
		$keywords_raw = get_option( 'sss_blocked_keywords', '' );

		if ( empty( $keywords_raw ) ) {
			return true;
		}

		$content = strtolower( $data['content'] ?? $data['comment'] ?? '' );
		$author  = strtolower( $data['author'] ?? $data['author_name'] ?? '' );
		$email   = strtolower( $data['email'] ?? $data['author_email'] ?? '' );
		$haystack = "{$content} {$author} {$email}";

		if ( empty( trim( $haystack ) ) ) {
			return true;
		}

		$keywords = array_filter(
			array_map( 'trim', explode( "\n", $keywords_raw ) )
		);

		foreach ( $keywords as $keyword ) {
			$keyword = strtolower( $keyword );

			// Use word boundary matching for single words, substring for phrases.
			if ( str_contains( $keyword, ' ' ) ) {
				// Multi-word phrase: substring match.
				if ( str_contains( $haystack, $keyword ) ) {
					return $this->fail(
						__( 'Submission rejected — contains blocked content.', 'simple-spam-shield' )
					);
				}
			} else {
				// Single word: word boundary match.
				if ( preg_match( '/\b' . preg_quote( $keyword, '/' ) . '\b/i', $haystack ) ) {
					return $this->fail(
						__( 'Submission rejected — contains blocked content.', 'simple-spam-shield' )
					);
				}
			}
		}

		return true;
	}
}
