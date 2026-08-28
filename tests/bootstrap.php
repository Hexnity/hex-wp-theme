<?php
/**
 * PHPUnit bootstrap.
 *
 * Uses WP_Mock instead of a full WordPress test install: theme
 * functions are pure enough (decision logic, sanitization, data
 * maps) to unit test by mocking the handful of WP core functions
 * they call, with no database or WordPress checkout required.
 *
 * @package Hex
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';

WP_Mock::bootstrap();

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

define( 'HEX_VERSION', '1.5.21' );
define( 'HEX_THEME_DIR', dirname( __DIR__ ) );
define( 'HEX_THEME_URI', 'http://example.test/wp-content/themes/hex-wp-theme-template' );

/*
 * Plain, permanent stand-ins for WordPress wrappers whose own
 * behavior is never the thing under test in this suite (hook
 * registration, translation, escaping). Defined once here —
 * not as per-test WP_Mock expectations — so every test file can
 * require theme includes without re-declaring them.
 */
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ) { // phpcs:ignore
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( ...$args ) { // phpcs:ignore
		return true;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) { // phpcs:ignore
		return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
	}
}

/*
 * Minimal stand-in for WP core's WP_Error — just enough of its API
 * (constructor, get_error_message(), get_error_code()) for code that
 * returns/inspects WP_Error instances to work under test.
 */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error { // phpcs:ignore
		private $errors = array();

		public function __construct( $code = '', $message = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ][] = $message;
			}
		}

		public function get_error_message() {
			foreach ( $this->errors as $messages ) {
				return $messages[0];
			}

			return '';
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );

			return isset( $codes[0] ) ? $codes[0] : '';
		}
	}
}

foreach (
	array(
		'setup.php',
		'enqueue.php',
		'template-tags.php',
		'widgets.php',
		'customizer.php',
		'security.php',
		'updater.php',
		'child-theme.php',
		'style-settings.php',
		'google-fonts.php',
		'admin/settings.php',
	) as $hex_inc_file
) {
	require HEX_THEME_DIR . '/inc/' . $hex_inc_file;
}
