<?php
/**
 * Tests for inc/style-settings.php — the admin-configurable design
 * token schema, its CSS custom property output, and its sanitize
 * callbacks.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_get_style_schema
 * @covers ::hex_get_style_groups
 * @covers ::hex_style_option_name
 * @covers ::hex_style_css_var_name
 * @covers ::hex_get_style_value
 * @covers ::hex_get_all_style_values
 * @covers ::hex_is_safe_style_value
 * @covers ::hex_render_style_css_vars
 * @covers ::hex_style_key_from_option_name
 * @covers ::hex_sanitize_style_length
 * @covers ::hex_sanitize_style_number
 * @covers ::hex_sanitize_style_color
 * @covers ::hex_sanitize_style_weight
 * @covers ::hex_sanitize_style_shadow
 * @covers ::hex_sanitize_style_font
 * @covers ::hex_style_sanitize_callback_for_type
 * @covers ::hex_get_shadow_presets
 * @covers ::hex_style_humanize_key
 * @covers ::hex_is_safe_font_value
 */
class StyleSettingsTest extends TestCase {

	public function test_schema_has_at_least_a_hundred_fields() {
		$this->assertGreaterThanOrEqual( 100, count( hex_get_style_schema() ) );
	}

	public function test_every_schema_field_belongs_to_a_documented_group() {
		$documented_groups = array_keys( hex_get_style_groups() );

		foreach ( hex_get_style_schema() as $key => $field ) {
			$this->assertContains( $field['group'], $documented_groups, "Field '{$key}' has an undocumented group '{$field['group']}'." );
		}
	}

	public function test_every_documented_group_has_at_least_one_field() {
		$used_groups = array_unique( array_column( hex_get_style_schema(), 'group' ) );

		foreach ( array_keys( hex_get_style_groups() ) as $group ) {
			$this->assertContains( $group, $used_groups, "Group '{$group}' has no fields." );
		}
	}

	public function test_every_schema_field_has_a_sanitize_callback_for_its_type() {
		foreach ( hex_get_style_schema() as $key => $field ) {
			$callback = hex_style_sanitize_callback_for_type( $field['type'] );
			$this->assertTrue( function_exists( $callback ), "Field '{$key}' (type '{$field['type']}') resolves to a non-existent sanitize callback '{$callback}'." );
		}
	}

	public function test_schema_includes_all_six_heading_levels() {
		$schema = hex_get_style_schema();

		foreach ( array( 'h1_size', 'h2_size', 'h3_size', 'h4_size', 'h5_size', 'h6_size' ) as $key ) {
			$this->assertArrayHasKey( $key, $schema );
			$this->assertSame( 'typography', $schema[ $key ]['group'] );
		}
	}

	public function test_option_name_is_prefixed() {
		$this->assertSame( 'hex_style_h1_size', hex_style_option_name( 'h1_size' ) );
	}

	public function test_css_var_name_converts_underscores_to_hyphens() {
		$this->assertSame( '--hex-color-primary', hex_style_css_var_name( 'color_primary' ) );
	}

	public function test_get_style_value_falls_back_to_schema_default() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_h1_size', '2.5rem' )
			->andReturn( '2.5rem' );

		$this->assertSame( '2.5rem', hex_get_style_value( 'h1_size' ) );
	}

	public function test_get_style_value_returns_empty_string_for_an_unknown_key() {
		$this->assertSame( '', hex_get_style_value( 'not_a_real_key' ) );
	}

	public function test_get_all_style_values_returns_every_schema_key() {
		WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			function ( $name, $default ) {
				return $default;
			}
		);

		$values = hex_get_all_style_values();

		$this->assertSame( array_keys( hex_get_style_schema() ), array_keys( $values ) );
		$this->assertSame( '2.5rem', $values['h1_size'] );
		$this->assertSame( '#2563eb', $values['color_primary'] );
	}

	public function test_safe_style_value_accepts_lengths_and_colors() {
		$this->assertTrue( hex_is_safe_style_value( '2.5rem' ) );
		$this->assertTrue( hex_is_safe_style_value( '#2563eb' ) );
		$this->assertTrue( hex_is_safe_style_value( '100%' ) );
	}

	public function test_safe_style_value_rejects_anything_that_could_break_out_of_a_css_declaration() {
		$this->assertFalse( hex_is_safe_style_value( '' ) );
		$this->assertFalse( hex_is_safe_style_value( '1rem; } body { display:none' ) );
		$this->assertFalse( hex_is_safe_style_value( 'url(javascript:alert(1))' ) );
		$this->assertFalse( hex_is_safe_style_value( '"1rem"' ) );
	}

	public function test_render_style_css_vars_prints_every_token_as_a_css_custom_property() {
		WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			function ( $name, $default ) {
				return $default;
			}
		);

		ob_start();
		hex_render_style_css_vars();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<style id="hex-style-vars">:root{', $output );
		$this->assertStringContainsString( '--hex-h1-size:2.5rem;', $output );
		$this->assertStringContainsString( '--hex-color-primary:#2563eb;', $output );
		$this->assertStringContainsString( '--hex-spacing-xs:0.5rem;', $output );
	}

	public function test_render_style_css_vars_skips_an_unsafe_stored_value() {
		WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			function ( $name, $default ) {
				return 'hex_style_h1_size' === $name ? '1rem;}body{display:none' : $default;
			}
		);

		ob_start();
		hex_render_style_css_vars();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'display:none', $output );
		$this->assertStringNotContainsString( '--hex-h1-size:1rem;}body', $output );
	}

	public function test_style_key_from_option_name_strips_the_prefix() {
		$this->assertSame( 'h1_size', hex_style_key_from_option_name( 'hex_style_h1_size' ) );
	}

	public function test_sanitize_style_length_accepts_a_valid_length() {
		$this->assertSame( '1.5rem', hex_sanitize_style_length( '1.5rem' ) );
		$this->assertSame( '24px', hex_sanitize_style_length( '24px' ) );
		$this->assertSame( '100%', hex_sanitize_style_length( '100%' ) );
	}

	public function test_sanitize_style_length_accepts_negative_values_and_bare_zero() {
		$this->assertSame( '-0.02em', hex_sanitize_style_length( '-0.02em' ) );
		$this->assertSame( '0', hex_sanitize_style_length( '0' ) );
	}

	public function test_sanitize_style_number_accepts_a_bare_decimal() {
		$this->assertSame( '1.5', hex_sanitize_style_number( '1.5' ) );
		$this->assertSame( '2', hex_sanitize_style_number( '2' ) );
	}

	public function test_sanitize_style_number_rejects_a_value_with_a_unit() {
		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( 'current_filter' )->once()->andReturn( 'sanitize_option_hex_style_h1_line_height' );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_h1_line_height', '1.2' )
			->andReturn( '1.2' );

		$this->assertSame( '1.2', hex_sanitize_style_number( '1.5rem' ) );
	}

	public function test_sanitize_style_weight_accepts_a_standard_weight() {
		$this->assertSame( '700', hex_sanitize_style_weight( '700' ) );
	}

	public function test_sanitize_style_weight_rejects_an_out_of_range_value() {
		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( 'current_filter' )->once()->andReturn( 'sanitize_option_hex_style_h1_weight' );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_h1_weight', '700' )
			->andReturn( '700' );

		$this->assertSame( '700', hex_sanitize_style_weight( '750' ) );
	}

	public function test_sanitize_style_shadow_accepts_a_known_preset() {
		$this->assertSame( 'lg', hex_sanitize_style_shadow( 'lg' ) );
	}

	public function test_sanitize_style_shadow_rejects_an_unknown_preset() {
		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( 'current_filter' )->once()->andReturn( 'sanitize_option_hex_style_card_shadow' );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_card_shadow', 'sm' )
			->andReturn( 'sm' );

		$this->assertSame( 'sm', hex_sanitize_style_shadow( 'huge' ) );
	}

	public function test_sanitize_style_font_accepts_a_safe_font_stack() {
		$this->assertSame( "Georgia, 'Times New Roman', serif", hex_sanitize_style_font( "Georgia, 'Times New Roman', serif" ) );
	}

	public function test_sanitize_style_font_rejects_an_unsafe_value() {
		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( 'current_filter' )->once()->andReturn( 'sanitize_option_hex_style_body_font_family' );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_body_font_family', "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" )
			->andReturn( 'sans-serif' );

		$this->assertSame( 'sans-serif', hex_sanitize_style_font( 'url(javascript:alert(1))' ) );
	}

	public function test_shadow_presets_include_none_through_extra_large() {
		$this->assertSame( array( 'none', 'sm', 'md', 'lg', 'xl' ), array_keys( hex_get_shadow_presets() ) );
	}

	public function test_render_style_css_vars_resolves_a_shadow_keyword_to_its_real_css_value() {
		WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			function ( $name, $default ) {
				return 'hex_style_card_shadow' === $name ? 'lg' : $default;
			}
		);

		ob_start();
		hex_render_style_css_vars();
		$output = ob_get_clean();

		$this->assertStringContainsString( '--hex-card-shadow:' . hex_get_shadow_presets()['lg'] . ';', $output );
	}

	public function test_humanize_key_converts_snake_case_to_title_case() {
		$this->assertSame( 'Body Background', hex_style_humanize_key( 'body_background' ) );
	}

	public function test_safe_font_value_accepts_a_quoted_font_stack_and_rejects_unsafe_input() {
		$this->assertTrue( hex_is_safe_font_value( "Georgia, 'Times New Roman', sans-serif" ) );
		$this->assertFalse( hex_is_safe_font_value( 'a; } body { display:none' ) );
	}

	public function test_sanitize_style_length_rejects_an_invalid_value_and_keeps_the_previous_one() {
		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( 'current_filter' )->once()->andReturn( 'sanitize_option_hex_style_h1_size' );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_h1_size', '2.5rem' )
			->andReturn( '3rem' );

		$this->assertSame( '3rem', hex_sanitize_style_length( 'not-a-length' ) );
	}

	public function test_sanitize_style_color_accepts_a_valid_hex_color() {
		WP_Mock::userFunction( 'sanitize_hex_color' )->once()->with( '#2563eb' )->andReturn( '#2563eb' );

		$this->assertSame( '#2563eb', hex_sanitize_style_color( '#2563eb' ) );
	}

	public function test_sanitize_style_color_rejects_an_invalid_value_and_keeps_the_previous_one() {
		WP_Mock::userFunction( 'sanitize_hex_color' )->once()->andReturn( null );
		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( 'current_filter' )->once()->andReturn( 'sanitize_option_hex_style_color_primary' );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_style_color_primary', '#2563eb' )
			->andReturn( '#112233' );

		$this->assertSame( '#112233', hex_sanitize_style_color( 'not-a-color' ) );
	}
}
