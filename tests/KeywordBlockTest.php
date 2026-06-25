<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Guards\Keyword_Block;

final class KeywordBlockTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['simple_spam_shield_test_options'] = [
			'simple_spam_shield_blocked_keywords' => "spam\nfree money",
		];
	}

	private function guard(): Keyword_Block {
		return new Keyword_Block( 'keyword_block', [] );
	}

	public function test_blocks_a_single_keyword_case_insensitively(): void {
		$result = $this->guard()->check( [ 'content' => 'This is SPAM!' ], 'comment' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_does_not_block_a_keyword_inside_a_larger_word(): void {
		// Word-boundary matching: "spammy" must not trip the "spam" keyword.
		$result = $this->guard()->check( [ 'content' => 'these spammy eggs' ], 'comment' );
		$this->assertTrue( $result );
	}

	public function test_blocks_a_multi_word_phrase_as_a_substring(): void {
		$result = $this->guard()->check( [ 'content' => 'win free money today' ], 'comment' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_allows_clean_content(): void {
		$this->assertTrue( $this->guard()->check( [ 'content' => 'lovely article, thanks' ], 'comment' ) );
	}

	public function test_allows_when_no_keywords_configured(): void {
		$GLOBALS['simple_spam_shield_test_options']['simple_spam_shield_blocked_keywords'] = '';
		$this->assertTrue( $this->guard()->check( [ 'content' => 'spam spam spam' ], 'comment' ) );
	}
}
