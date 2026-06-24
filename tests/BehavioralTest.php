<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Guards\Behavioral;

final class BehavioralTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['sss_test_options'] = [ 'simple_spam_shield_behavioral_threshold' => 0.6 ];
	}

	private function guard(): Behavioral {
		return new Behavioral( 'behavioral', [ 'threshold' => 0.6 ] );
	}

	private function payload( array $behavior ): array {
		return [ 'simple_spam_shield_behavioral_data' => (string) wp_json_encode_compat( $behavior ) ];
	}

	public function test_blocks_botlike_interaction(): void {
		// Fast submit + no mouse movement + no clicks => 0.4 + 0.3 + 0.2 = 0.9.
		$result = $this->guard()->check(
			$this->payload( [ 'time_spent' => 1, 'mouse_movements' => 0, 'clicks' => 0 ] ),
			'comment'
		);
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_allows_humanlike_interaction(): void {
		$result = $this->guard()->check(
			$this->payload( [ 'time_spent' => 30, 'mouse_movements' => 50, 'clicks' => 4 ] ),
			'comment'
		);
		$this->assertTrue( $result );
	}

	public function test_skips_when_data_absent(): void {
		$this->assertTrue( $this->guard()->check( [], 'comment' ) );
	}

	public function test_skips_on_malformed_json(): void {
		$data = [ 'simple_spam_shield_behavioral_data' => '{not valid json' ];
		$this->assertTrue( $this->guard()->check( $data, 'comment' ) );
	}
}

/**
 * Local JSON encoder so the test does not depend on a WP stub.
 */
function wp_json_encode_compat( array $data ): string {
	return json_encode( $data );
}
