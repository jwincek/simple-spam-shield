<?php
/**
 * PHPUnit bootstrap — lightweight WordPress stubs + plugin autoloader.
 *
 * These are fast, dependency-free unit tests for the plugin's pure logic
 * (token signing, IP resolution, guard decisions). They do not require a
 * WordPress install or a database; only the handful of WP functions the
 * tested classes call are stubbed below.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

define( 'SSS_PLUGIN_ROOT', dirname( __DIR__ ) );

// Satisfies the `if ( ! defined( 'ABSPATH' ) ) exit;` direct-access guards.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', SSS_PLUGIN_ROOT . '/' );
}

// --- In-memory option store ------------------------------------------------
$GLOBALS['sss_test_options'] = [];

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		return $GLOBALS['sss_test_options'][ $key ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value, $autoload = null ) {
		$GLOBALS['sss_test_options'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'add_option' ) ) {
	function add_option( $key, $value = '', $deprecated = '', $autoload = 'yes' ) {
		$GLOBALS['sss_test_options'][ $key ] = $value;
		return true;
	}
}

// --- Misc WP helpers -------------------------------------------------------
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		return substr( str_repeat( 'aB3$xY7!', (int) ceil( $length / 8 ) ), 0, (int) $length );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string ) {
		return trim( strip_tags( (string) $string ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		$str = is_string( $str ) ? $str : '';
		return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( $str ) ) );
	}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return is_string( $str ) ? wp_strip_all_tags( $str ) : '';
	}
}

// --- WP_Error --------------------------------------------------------------
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_code() {
			return $this->code;
		}
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

// --- Plugin autoloader (mirrors the plugin's own) --------------------------
spl_autoload_register( function ( $class ) {
	if ( ! str_starts_with( $class, 'Simple_Spam_Shield\\' ) ) {
		return;
	}
	$relative = substr( $class, strlen( 'Simple_Spam_Shield\\' ) );
	$parts    = explode( '\\', $relative );
	$name     = array_pop( $parts );
	$dir      = strtolower( implode( '/', $parts ) );
	$file     = SSS_PLUGIN_ROOT . '/includes/' . $dir . '/class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );
