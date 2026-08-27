<?php
/**
 * Tests for the per-template title/chrome visibility logic in
 * inc/template-tags.php.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_should_show_title
 * @covers ::hex_should_show_chrome
 */
class TemplateTagsTest extends TestCase {

	public function test_title_shows_on_non_page_contexts() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( false );

		$this->assertTrue( hex_should_show_title() );
	}

	public function test_title_shows_on_default_template() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_page_template_slug' )->once()->andReturn( 'template-default.php' );

		$this->assertTrue( hex_should_show_title() );
	}

	public function test_title_hidden_on_full_width_template() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_page_template_slug' )->once()->andReturn( 'template-full-width.php' );

		$this->assertFalse( hex_should_show_title() );
	}

	public function test_title_hidden_on_canvas_template() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_page_template_slug' )->once()->andReturn( 'template-canvas.php' );

		$this->assertFalse( hex_should_show_title() );
	}

	public function test_chrome_shows_on_non_page_contexts() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( false );

		$this->assertTrue( hex_should_show_chrome() );
	}

	public function test_chrome_shows_on_full_width_template() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_page_template_slug' )->once()->andReturn( 'template-full-width.php' );

		$this->assertTrue( hex_should_show_chrome() );
	}

	public function test_chrome_hidden_only_on_canvas_template() {
		WP_Mock::userFunction( 'is_page' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_page_template_slug' )->once()->andReturn( 'template-canvas.php' );

		$this->assertFalse( hex_should_show_chrome() );
	}
}
