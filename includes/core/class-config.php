<?php
/**
 * Config loader — reads JSON definitions from the config/ directory.
 *
 * Mirrors the Petstablished\Core\Config pattern: a static singleton
 * that caches parsed JSON and exposes a simple get() API.
 *
 * @package Simple_Spam_Shield
 */

declare( strict_types=1 );

namespace SSS\Core;

final class Config {

	/** @var array<string, array<string, mixed>> */
	private static array $cache = [];

	private static string $dir = '';

	/**
	 * Initialize the config loader with the config directory path.
	 */
	public static function init( string $dir ): void {
		self::$dir   = trailingslashit( $dir );
		self::$cache = [];
	}

	/**
	 * Retrieve a value from a config file.
	 *
	 * @param string $file    Config filename without extension (e.g. 'guards').
	 * @param string $key     Top-level key within the JSON (e.g. 'guards').
	 * @param mixed  $default Fallback if the key doesn't exist.
	 * @return mixed
	 */
	public static function get( string $file, string $key, mixed $default = null ): mixed {
		self::load( $file );

		return self::$cache[ $file ][ $key ] ?? $default;
	}

	/**
	 * Retrieve the entire parsed config file.
	 */
	public static function all( string $file ): array {
		self::load( $file );

		return self::$cache[ $file ] ?? [];
	}

	/**
	 * Load and cache a JSON config file.
	 */
	private static function load( string $file ): void {
		if ( isset( self::$cache[ $file ] ) ) {
			return;
		}

		$path = self::$dir . $file . '.json';

		if ( ! file_exists( $path ) ) {
			self::$cache[ $file ] = [];
			return;
		}

		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded  = json_decode( $contents, true );

		self::$cache[ $file ] = is_array( $decoded ) ? $decoded : [];
	}
}
