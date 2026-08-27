<?php
/**
 * Theme Customizer settings.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 * @return void
 */
function hex_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'hex_layout_options',
		array(
			'title'    => __( 'Layout Options', 'hex' ),
			'priority' => 130,
		)
	);

	$wp_customize->add_setting(
		'hex_footer_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'hex_sanitize_footer_text',
		)
	);

	$wp_customize->add_control(
		'hex_footer_text',
		array(
			'label'    => __( 'Footer Text', 'hex' ),
			'section'  => 'hex_layout_options',
			'type'     => 'text',
			'priority' => 10,
		)
	);
}
add_action( 'customize_register', 'hex_customize_register' );

/**
 * Sanitize the custom footer text setting.
 *
 * Strips all HTML tags; a footer credit line has no legitimate
 * use for markup and this avoids storing unescaped HTML.
 *
 * @param string $input Raw value submitted by the Customizer.
 * @return string Sanitized value.
 */
function hex_sanitize_footer_text( $input ) {
	return sanitize_text_field( wp_strip_all_tags( (string) $input ) );
}
