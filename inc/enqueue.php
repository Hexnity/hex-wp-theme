<?php
/**
 * Front-end and editor asset registration.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue theme stylesheet and scripts on the front end.
 *
 * @return void
 */
function hex_enqueue_assets() {
	wp_enqueue_style( 'hex-style', get_stylesheet_uri(), array(), HEX_VERSION );

	wp_enqueue_style(
		'hex-animate',
		HEX_THEME_URI . '/assets/vendor/animate.min.css',
		array( 'hex-style' ),
		HEX_VERSION
	);

	wp_enqueue_style(
		'hex-tailwind',
		HEX_THEME_URI . '/assets/css/tailwind.css',
		array( 'hex-style', 'hex-animate' ),
		HEX_VERSION
	);

	wp_enqueue_script(
		'hex-navigation',
		HEX_THEME_URI . '/assets/js/navigation.js',
		array(),
		HEX_VERSION,
		true
	);

	if ( is_singular() && comments_open() ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'hex_enqueue_assets' );
