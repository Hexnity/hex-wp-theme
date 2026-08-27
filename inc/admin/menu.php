<?php
/**
 * Theme management admin menu: Dashboard, Updates, About, Child
 * Theme, and Theme Options pages under a single top-level menu named
 * after the active theme.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the top-level menu and its submenu pages.
 *
 * Stores the resulting hook suffixes in a static so
 * hex_enqueue_admin_assets() only loads assets on these screens.
 *
 * @return void
 */
function hex_register_admin_menu() {
	/*
	 * Deliberately a short fixed label, not wp_get_theme()->get( 'Name' )
	 * — the user asked for the sidebar menu specifically to read
	 * "Hexnity WP" regardless of the full theme name in style.css
	 * ("Hexnity WP AI Theme"). Other places (banner alt text, About
	 * page) still use the real theme name.
	 */
	$menu_label = 'Hexnity WP';

	$hooks   = array();
	$hooks[] = add_menu_page(
		$menu_label,
		$menu_label,
		'manage_options',
		'hex-theme',
		'hex_render_dashboard_page',
		'dashicons-art',
		61
	);

	$hooks[] = add_submenu_page(
		'hex-theme',
		__( 'Dashboard', 'hex' ),
		__( 'Dashboard', 'hex' ),
		'manage_options',
		'hex-theme',
		'hex_render_dashboard_page'
	);

	$hooks[] = add_submenu_page(
		'hex-theme',
		__( 'Updates', 'hex' ),
		__( 'Updates', 'hex' ),
		'manage_options',
		'hex-theme-updates',
		'hex_render_updates_page'
	);

	$hooks[] = add_submenu_page(
		'hex-theme',
		__( 'About', 'hex' ),
		__( 'About', 'hex' ),
		'manage_options',
		'hex-theme-about',
		'hex_render_about_page'
	);

	$hooks[] = add_submenu_page(
		'hex-theme',
		__( 'Child Theme', 'hex' ),
		__( 'Child Theme', 'hex' ),
		'edit_themes',
		'hex-theme-child-theme',
		'hex_render_child_theme_page'
	);

	$hooks[] = add_submenu_page(
		'hex-theme',
		__( 'Theme Options', 'hex' ),
		__( 'Theme Options', 'hex' ),
		'edit_theme_options',
		'hex-theme-theme-options',
		'hex_render_theme_options_page'
	);

	hex_admin_screen_hooks( $hooks );
}
add_action( 'admin_menu', 'hex_register_admin_menu' );

/**
 * Store (or retrieve) this menu's screen hook suffixes.
 *
 * @param array $hooks Hook suffixes to store; omit to just read them back.
 * @return array
 */
function hex_admin_screen_hooks( $hooks = null ) {
	static $stored = array();

	if ( null !== $hooks ) {
		$stored = array_filter( $hooks );
	}

	return $stored;
}

/**
 * Enqueue the admin CSS/JS, but only on this theme's own screens.
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
function hex_enqueue_admin_assets( $hook ) {
	if ( ! in_array( $hook, hex_admin_screen_hooks(), true ) ) {
		return;
	}

	wp_enqueue_style( 'hex-admin', HEX_THEME_URI . '/assets/css/tailwind-admin.css', array(), HEX_VERSION );
	wp_enqueue_script( 'hex-admin', HEX_THEME_URI . '/assets/js/admin.js', array(), HEX_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'hex_enqueue_admin_assets' );

require HEX_THEME_DIR . '/inc/admin/partials.php';
require HEX_THEME_DIR . '/inc/admin/page-dashboard.php';
require HEX_THEME_DIR . '/inc/admin/page-updates.php';
require HEX_THEME_DIR . '/inc/admin/page-about.php';
require HEX_THEME_DIR . '/inc/admin/page-child-theme.php';
require HEX_THEME_DIR . '/inc/admin/page-theme-options.php';
