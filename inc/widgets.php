<?php
/**
 * Widget area (sidebar) registration.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the theme's widget areas.
 *
 * @return void
 */
function hex_register_widget_areas() {
	register_sidebar(
		array(
			'name'          => __( 'Footer', 'hex' ),
			'id'            => 'footer-1',
			'description'   => __( 'Widgets in this area appear in the site footer.', 'hex' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'hex_register_widget_areas' );
