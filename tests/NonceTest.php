<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Guards\Nonce;

/**
 * The "nonce" guard now verifies the HMAC signature of the form token
 * (the slug is kept as "nonce" for settings compatibility).
 */
final class NonceTest extends TestCase {

	private string $secret = '';

	protected function setUp(): void {
		$this->secret               = str_repeat( 'n', 64 );
		$GLOBALS['sss_test_options'] = [ 'simple_spam_shield_token_secret' => $this->secret ];
	}

	private function guard(): Nonce {
		return new Nonce( 'nonce', [] );
	}

	private function valid_token(): string {
		$issued = time();
		return $issued . '.' . hash_hmac( 'sha256', (string) $issued, $this->secret );
	}

	public function test_allows_a_validly_signed_token(): void {
		$data = [ Nonce::FIELD => $this->valid_token() ];
		$this->assertTrue( $this->guard()->check( $data, 'comment' ) );
	}

	public function test_blocks_a_forged_token(): void {
		$data = [ Nonce::FIELD => '123.deadbeef' ];
		$this->assertInstanceOf( WP_Error::class, $this->guard()->check( $data, 'comment' ) );
	}

	public function test_blocks_a_missing_token_on_comment(): void {
		$this->assertInstanceOf( WP_Error::class, $this->guard()->check( [], 'comment' ) );
	}

	public function test_skips_a_missing_token_on_jetpack(): void {
		$this->assertTrue( $this->guard()->check( [], 'jetpack_form' ) );
	}
}
