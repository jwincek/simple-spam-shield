<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Guards\Time_Gate;

final class TimeGateTest extends TestCase {

	private string $secret = '';

	protected function setUp(): void {
		$this->secret               = str_repeat( 't', 64 );
		$GLOBALS['simple_spam_shield_test_options'] = [
			'simple_spam_shield_token_secret'      => $this->secret,
			'simple_spam_shield_time_gate_seconds' => 3,
		];
	}

	private function guard(): Time_Gate {
		return new Time_Gate( 'time_gate', [ 'min_seconds' => 3 ] );
	}

	private function token_for( int $issued ): string {
		return $issued . '.' . hash_hmac( 'sha256', (string) $issued, $this->secret );
	}

	public function test_blocks_submission_faster_than_min_seconds(): void {
		$data = [ 'simple_spam_shield_form_loaded' => $this->token_for( time() ) ];
		$this->assertInstanceOf( WP_Error::class, $this->guard()->check( $data, 'comment' ) );
	}

	public function test_allows_submission_after_min_seconds(): void {
		$data = [ 'simple_spam_shield_form_loaded' => $this->token_for( time() - 10 ) ];
		$this->assertTrue( $this->guard()->check( $data, 'comment' ) );
	}

	public function test_blocks_a_forged_token(): void {
		$data = [ 'simple_spam_shield_form_loaded' => ( time() - 10 ) . '.deadbeef' ];
		$this->assertInstanceOf( WP_Error::class, $this->guard()->check( $data, 'comment' ) );
	}

	public function test_skips_when_token_absent_on_jetpack(): void {
		$this->assertTrue( $this->guard()->check( [], 'jetpack_form' ) );
	}

	public function test_blocks_when_token_absent_on_comment(): void {
		$this->assertInstanceOf( WP_Error::class, $this->guard()->check( [], 'comment' ) );
	}
}
