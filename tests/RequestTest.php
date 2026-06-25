<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Core\Request;

final class RequestTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['simple_spam_shield_test_options'] = [];
		$_SERVER['REMOTE_ADDR']      = '203.0.113.5';
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	public function test_uses_remote_addr_and_ignores_forwarded_header_by_default(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8';
		$this->assertSame( '203.0.113.5', Request::ip() );
	}

	public function test_honors_first_forwarded_ip_only_when_proxy_is_trusted(): void {
		$GLOBALS['simple_spam_shield_test_options']['simple_spam_shield_trust_proxy'] = true;
		$_SERVER['HTTP_X_FORWARDED_FOR']                              = '8.8.8.8, 203.0.113.5';
		$this->assertSame( '8.8.8.8', Request::ip() );
	}

	public function test_falls_back_to_remote_addr_when_forwarded_header_is_invalid(): void {
		$GLOBALS['simple_spam_shield_test_options']['simple_spam_shield_trust_proxy'] = true;
		$_SERVER['HTTP_X_FORWARDED_FOR']                              = 'not-an-ip';
		$this->assertSame( '203.0.113.5', Request::ip() );
	}

	public function test_returns_zero_ip_for_invalid_remote_addr(): void {
		$_SERVER['REMOTE_ADDR'] = 'bogus';
		$this->assertSame( '0.0.0.0', Request::ip() );
	}
}
