<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Guards\Link_Limit;

final class LinkLimitTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['sss_test_options'] = [ 'simple_spam_shield_link_limit_max' => 3 ];
	}

	private function guard(): Link_Limit {
		return new Link_Limit( 'link_limit', [ 'max_links' => 3 ] );
	}

	public function test_blocks_content_with_too_many_links(): void {
		$content = 'a http://a.com b http://b.com c http://c.com d https://d.com';
		$result  = $this->guard()->check( [ 'content' => $content ], 'comment' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_allows_content_at_the_limit(): void {
		$content = 'see http://a.com and http://b.com and http://c.com';
		$this->assertTrue( $this->guard()->check( [ 'content' => $content ], 'comment' ) );
	}

	public function test_allows_content_with_no_links(): void {
		$this->assertTrue( $this->guard()->check( [ 'content' => 'a perfectly normal comment' ], 'comment' ) );
	}

	public function test_allows_empty_content(): void {
		$this->assertTrue( $this->guard()->check( [ 'content' => '' ], 'comment' ) );
	}

	public function test_respects_a_configured_max_from_options(): void {
		$GLOBALS['sss_test_options']['simple_spam_shield_link_limit_max'] = 1;
		$content                                                         = 'http://a.com and http://b.com';
		$this->assertInstanceOf( WP_Error::class, $this->guard()->check( [ 'content' => $content ], 'comment' ) );
	}
}
