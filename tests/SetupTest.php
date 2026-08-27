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
}
