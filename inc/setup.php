<?php
/**
 * Core theme setup: supports, nav menus, image sizes.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme support features and navigation menus.
 *
 * @return void
 */
function hex_setup() {
	load_theme_textdomain( 'hex', HEX_THEME_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'custom-background',
		array(
			'default-color' => 'ffffff',
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'hex' ),
			'footer'  => __( 'Footer Menu', 'hex' ),
		)
	);
}
add_action( 'after_setup_theme', 'hex_setup' );

/**
 * Set the default content width used by embeds and oEmbeds.
 *
 * @global int $content_width
 * @return void
 */
function hex_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'hex_content_width', 1200 );
}
add_action( 'after_setup_theme', 'hex_content_width', 0 );

/**
 * Registered page templates, keyed by file name.
 *
 * Single source of truth for the theme's three templates so
 * setup, tests, and documentation stay in sync.
 *
 * @return array<string,string> Map of template file => display label.
 */
function hex_get_page_templates() {
	return array(
		'template-default.php'    => __( 'Default', 'hex' ),
		'template-full-width.php' => __( 'Full Width', 'hex' ),
		'template-canvas.php'     => __( 'Canvas', 'hex' ),
	);
}
