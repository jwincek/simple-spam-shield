<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Core\Token;

final class TokenTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['simple_spam_shield_test_options'] = [
			'simple_spam_shield_token_secret' => str_repeat( 'k', 64 ),
		];
	}

	public function test_issue_returns_signed_timestamp_token(): void {
		$this->assertMatchesRegularExpression( '/^\d+\.[0-9a-f]{64}$/', Token::issue() );
	}

	public function test_verify_accepts_a_valid_token_and_returns_issue_time(): void {
		$issued = Token::verify( Token::issue() );
		$this->assertIsInt( $issued );
		$this->assertEqualsWithDelta( time(), $issued, 2 );
	}

	public function test_verify_rejects_a_tampered_signature(): void {
		$this->assertFalse( Token::verify( Token::issue() . 'x' ) );
	}

	public function test_verify_rejects_a_forged_token(): void {
		$this->assertFalse( Token::verify( '100.deadbeef' ) );
	}

	public function test_verify_rejects_a_swapped_issue_time(): void {
		// Reuse a valid signature with a different timestamp — must fail.
		[ , $signature ] = explode( '.', Token::issue(), 2 );
		$this->assertFalse( Token::verify( ( time() - 999 ) . '.' . $signature ) );
	}

	public function test_verify_rejects_empty_and_malformed_input(): void {
		$this->assertFalse( Token::verify( '' ) );
		$this->assertFalse( Token::verify( 'no-separator' ) );
		$this->assertFalse( Token::verify( 'abc.' . str_repeat( '0', 64 ) ) );
	}

	public function test_secret_is_generated_and_persisted_when_absent(): void {
		$GLOBALS['simple_spam_shield_test_options'] = [];
		$token = Token::issue();
		// A secret should now exist and the freshly issued token verifies.
		$this->assertArrayHasKey( 'simple_spam_shield_token_secret', $GLOBALS['simple_spam_shield_test_options'] );
		$this->assertIsInt( Token::verify( $token ) );
	}
}
