<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Guards\Honeypot;

final class HoneypotTest extends TestCase {

	private function guard(): Honeypot {
		return new Honeypot( 'honeypot', [] );
	}

	public function test_blocks_when_the_honeypot_field_is_filled(): void {
		$data   = [ 'simple_spam_shield_website_url' => 'http://spam.example' ];
		$result = $this->guard()->check( $data, 'comment' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_allows_when_the_honeypot_field_is_empty(): void {
		$data = [ 'simple_spam_shield_website_url' => '' ];
		$this->assertTrue( $this->guard()->check( $data, 'comment' ) );
	}

	public function test_allows_when_the_honeypot_field_is_absent(): void {
		$this->assertTrue( $this->guard()->check( [], 'comment' ) );
	}

	public function test_respects_a_custom_field_name_from_config(): void {
		$guard = new Honeypot( 'honeypot', [ 'field_name' => 'my_trap' ] );
		$this->assertInstanceOf( WP_Error::class, $guard->check( [ 'my_trap' => 'x' ], 'comment' ) );
		// The default field name no longer applies.
		$this->assertTrue( $guard->check( [ 'simple_spam_shield_website_url' => 'x' ], 'comment' ) );
	}
}
