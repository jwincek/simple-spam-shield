<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;
use Simple_Spam_Shield\Core\Database_Manager;

final class DatabaseManagerTest extends TestCase {

	public function test_no_filters_returns_empty_where_and_params(): void {
		[ $where, $params ] = Database_Manager::build_filter( [] );
		$this->assertSame( '', $where );
		$this->assertSame( [], $params );
	}

	public function test_guard_filter_only(): void {
		[ $where, $params ] = Database_Manager::build_filter( [ 'guard' => 'honeypot' ] );
		$this->assertSame( ' WHERE guard = %s', $where );
		$this->assertSame( [ 'honeypot' ], $params );
	}

	public function test_context_filter_only(): void {
		[ $where, $params ] = Database_Manager::build_filter( [ 'context' => 'comment' ] );
		$this->assertSame( ' WHERE context = %s', $where );
		$this->assertSame( [ 'comment' ], $params );
	}

	public function test_both_filters_are_combined_with_and(): void {
		[ $where, $params ] = Database_Manager::build_filter(
			[
				'guard'   => 'keyword_block',
				'context' => 'jetpack_form',
			]
		);
		$this->assertSame( ' WHERE guard = %s AND context = %s', $where );
		$this->assertSame( [ 'keyword_block', 'jetpack_form' ], $params );
	}

	public function test_empty_string_filters_are_ignored(): void {
		[ $where, $params ] = Database_Manager::build_filter(
			[
				'guard'   => '',
				'context' => '',
			]
		);
		$this->assertSame( '', $where );
		$this->assertSame( [], $params );
	}

	public function test_filter_values_are_bound_not_interpolated(): void {
		// A malicious value must travel as a bound param, never inlined.
		$evil               = "x'; DROP TABLE wp_users; --";
		[ $where, $params ] = Database_Manager::build_filter( [ 'guard' => $evil ] );
		$this->assertSame( ' WHERE guard = %s', $where );
		$this->assertSame( [ $evil ], $params );
	}
}
