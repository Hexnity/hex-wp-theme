<?php
/**
 * Tests for inc/setup.php's data and filter-application helpers.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_get_page_templates
 * @covers ::hex_content_width
 * @covers ::hex_nav_menu_link_attributes
 */
class SetupTest extends TestCase {

	public function test_get_page_templates_declares_exactly_the_three_required_templates() {
		$templates = hex_get_page_templates();

		$this->assertSame(
			array(
				'template-default.php'    => 'Default',
				'template-full-width.php' => 'Full Width',
				'template-canvas.php'     => 'Canvas',
			),
			$templates
		);
	}

	public function test_content_width_applies_the_hex_content_width_filter() {
		WP_Mock::onFilter( 'hex_content_width' )
			->with( 1200 )
			->reply( 900 );

		hex_content_width();

		$this->assertSame( 900, $GLOBALS['content_width'] );
	}

	public function test_nav_menu_link_attributes_adds_nav_link_class_for_primary_menu() {
		$args = (object) array( 'theme_location' => 'primary' );

		$atts = hex_nav_menu_link_attributes( array(), null, $args );

		$this->assertSame( 'nav-link', $atts['class'] );
	}

	public function test_nav_menu_link_attributes_appends_to_an_existing_class() {
		$args = (object) array( 'theme_location' => 'footer' );

		$atts = hex_nav_menu_link_attributes( array( 'class' => 'existing-class' ), null, $args );

		$this->assertSame( 'existing-class nav-link', $atts['class'] );
	}

	public function test_nav_menu_link_attributes_leaves_other_menus_untouched() {
		$args = (object) array( 'theme_location' => 'social' );

		$atts = hex_nav_menu_link_attributes( array( 'class' => 'existing-class' ), null, $args );

		$this->assertSame( array( 'class' => 'existing-class' ), $atts );
	}

	public function test_nav_menu_link_attributes_leaves_menus_with_no_theme_location_untouched() {
		$args = (object) array();

		$atts = hex_nav_menu_link_attributes( array(), null, $args );

		$this->assertSame( array(), $atts );
	}
}
