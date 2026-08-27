<?php
/**
 * Baseline front-end hardening.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the generator meta tags that leak the WordPress version.
 *
 * @return string Empty string, replacing whatever core would have output.
 */
function hex_remove_version_generator() {
	return '';
}
add_filter( 'the_generator', 'hex_remove_version_generator' );

/**
 * Disable the X-Pingback header and related self-pingback discovery.
 *
 * @param array $headers HTTP headers WordPress is about to send.
 * @return array Filtered headers.
 */
function hex_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'hex_remove_pingback_header' );
