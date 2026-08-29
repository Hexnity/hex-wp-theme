<?php
/**
 * Theme bootstrap.
 *
 * Defines theme constants and loads every include from inc/.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme version, used for cache-busting enqueued assets.
 *
 * Must always match the `Version:` header in style.css.
 */
define( 'HEX_VERSION', '1.5.23' );

/** Absolute path to the theme directory. */
define( 'HEX_THEME_DIR', get_template_directory() );

/** Theme directory URI. */
define( 'HEX_THEME_URI', get_template_directory_uri() );

require HEX_THEME_DIR . '/inc/setup.php';
require HEX_THEME_DIR . '/inc/enqueue.php';
require HEX_THEME_DIR . '/inc/template-tags.php';
require HEX_THEME_DIR . '/inc/widgets.php';
require HEX_THEME_DIR . '/inc/customizer.php';
require HEX_THEME_DIR . '/inc/security.php';
require HEX_THEME_DIR . '/inc/updater.php';
require HEX_THEME_DIR . '/inc/child-theme.php';
require HEX_THEME_DIR . '/inc/style-settings.php';
require HEX_THEME_DIR . '/inc/google-fonts.php';

if ( is_admin() ) {
	require HEX_THEME_DIR . '/inc/admin/settings.php';
	require HEX_THEME_DIR . '/inc/admin/menu.php';
	require HEX_THEME_DIR . '/inc/admin/handlers.php';
}
