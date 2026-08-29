<?php
/**
 * Tests for inc/admin/settings.php's Theme Options field renderer —
 * specifically the accordion grouping added on top of the schema's
 * optional 'subgroup' label (see inc/style-settings.php).
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_render_style_group_fields
 * @covers ::hex_render_style_fields
 * @covers ::hex_render_style_field
 */
class AdminSettingsRenderTest extends TestCase {

	/**
	 * The rendering under test always resolves the full effective
	 * schema/values, which (with no active child theme) only needs
	 * is_child_theme() mocked — everything else is pure PHP.
	 *
	 * @return void
	 */
	private function mock_wp_helpers() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );
		WP_Mock::userFunction( 'esc_attr' )->andReturnUsing( fn( $v ) => $v );
		WP_Mock::userFunction( 'esc_html' )->andReturnUsing( fn( $v ) => $v );
		WP_Mock::userFunction( 'selected' )->andReturnUsing(
			fn( $selected, $current = true, $echo = true ) => ( (string) $selected === (string) $current ) ? ' selected="selected"' : ''
		);
	}

	public function test_a_group_with_no_subgroups_renders_flat_with_no_accordion() {
		$this->mock_wp_helpers();

		ob_start();
		hex_render_style_group_fields( 'colors' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'grid grid-cols-1', $output );
		$this->assertStringNotContainsString( '<details', $output );
		$this->assertStringContainsString( 'hex_style_color_primary', $output );
	}

	public function test_a_group_with_multiple_subgroups_renders_one_accordion_item_per_subgroup() {
		$this->mock_wp_helpers();

		ob_start();
		hex_render_style_group_fields( 'alerts' );
		$output = ob_get_clean();

		$this->assertSame( 4, substr_count( $output, '<details' ), 'Expected one <details> per alert state (Primary/Success/Warning/Danger).' );
		$this->assertSame( 1, substr_count( $output, '<details class="hex-style-accordion group border-b border-gray-800 last:border-b-0" open>' ), 'Expected exactly one open-by-default accordion item.' );

		foreach ( array( 'Primary', 'Success', 'Warning', 'Danger' ) as $label ) {
			$this->assertStringContainsString( '<span>' . $label . '</span>', $output );
		}
	}

	public function test_the_first_subgroup_in_schema_order_is_the_one_left_open() {
		$this->mock_wp_helpers();

		ob_start();
		hex_render_style_group_fields( 'alerts' );
		$output = ob_get_clean();

		$open_position    = strpos( $output, ' open>' );
		$primary_position = strpos( $output, '<span>Primary</span>' );

		$this->assertLessThan( $primary_position, $open_position, 'The open <details> tag should be the one immediately preceding the Primary summary.' );
	}

	public function test_typography_group_renders_a_font_library_accordion_with_four_selects() {
		$this->mock_wp_helpers();

		ob_start();
		hex_render_style_group_fields( 'typography' );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<span>Font Library</span>', $output );

		foreach ( array( 'font_heading', 'font_body', 'font_accent', 'font_mono' ) as $key ) {
			$field_name = 'hex_style_' . $key;
			$this->assertStringContainsString( '<select id="' . $field_name . '" name="' . $field_name . '"', $output );
		}

		$this->assertStringContainsString( '<optgroup label="Sans Serif">', $output );
		$this->assertStringContainsString( '<optgroup label="Serif">', $output );
		$this->assertStringContainsString( '<optgroup label="Monospace">', $output );
		$this->assertStringContainsString( '<optgroup label="Display">', $output );
		$this->assertStringContainsString( "<option value=\"'Inter', sans-serif\"", $output );
	}

	public function test_font_library_select_defaults_to_the_use_default_option_selected() {
		$this->mock_wp_helpers();

		ob_start();
		hex_render_style_field(
			array(
				'key'  => 'font_heading',
				'type' => 'google_font',
			)
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( '<option value="" selected="selected">', $output );
	}
}
