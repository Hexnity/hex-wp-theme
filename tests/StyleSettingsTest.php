<?php
/**
 * Tests for inc/style-settings.php — the admin-configurable design
 * token schema (plus auto-detected custom tokens), the CSS file it's
 * built into, and its sanitize callbacks.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_get_style_schema
 * @covers ::hex_get_style_groups
 * @covers ::hex_style_field_name
 * @covers ::hex_style_css_var_name
 * @covers ::hex_style_tokens_file_path
 * @covers ::hex_parse_style_tokens_css
 * @covers ::hex_get_child_theme_tokens
 * @covers ::hex_guess_style_type
 * @covers ::hex_merge_style_schema_with_tokens
 * @covers ::hex_get_effective_style_schema
 * @covers ::hex_get_effective_style_groups
 * @covers ::hex_get_style_value
 * @covers ::hex_get_effective_style_values
 * @covers ::hex_is_safe_style_value
 * @covers ::hex_get_fluid_size_pairs
 * @covers ::hex_build_fluid_clamp
 * @covers ::hex_build_style_tokens_css
 * @covers ::hex_sanitize_style_length
 * @covers ::hex_sanitize_style_number
 * @covers ::hex_sanitize_style_color
 * @covers ::hex_sanitize_style_weight
 * @covers ::hex_sanitize_style_shadow
 * @covers ::hex_sanitize_style_font
 * @covers ::hex_sanitize_style_custom
 * @covers ::hex_sanitize_submitted_style_tokens
 * @covers ::hex_style_sanitize_callback_for_type
 * @covers ::hex_get_shadow_presets
 * @covers ::hex_style_humanize_key
 * @covers ::hex_is_safe_font_value
 */
class StyleSettingsTest extends TestCase {

	/**
	 * Emulates WP core's sanitize_hex_color() closely enough for tests
	 * exercising code paths that branch on "is this a hex color".
	 *
	 * @return void
	 */
	private function mock_sanitize_hex_color() {
		WP_Mock::userFunction( 'sanitize_hex_color' )->andReturnUsing(
			function ( $value ) {
				return (bool) preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $value ) ? $value : null;
			}
		);
	}

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

		foreach ( array( 'h1_size_mobile', 'h2_size_mobile', 'h3_size_mobile', 'h4_size_mobile', 'h5_size_mobile', 'h6_size_mobile' ) as $key ) {
			$this->assertArrayHasKey( $key, $schema );
			$this->assertSame( 'typography', $schema[ $key ]['group'] );
		}
	}

	public function test_field_name_is_prefixed() {
		$this->assertSame( 'hex_style_h1_size', hex_style_field_name( 'h1_size' ) );
	}

	public function test_css_var_name_converts_underscores_to_hyphens() {
		$this->assertSame( '--hex-color-primary', hex_style_css_var_name( 'color_primary' ) );
	}

	public function test_tokens_file_path_is_inside_the_active_theme_directory() {
		WP_Mock::userFunction( 'get_stylesheet_directory' )->once()->andReturn( '/tmp/some-child-theme' );

		$this->assertSame( '/tmp/some-child-theme/theme-options.css', hex_style_tokens_file_path() );
	}

	public function test_parse_style_tokens_css_extracts_every_declaration() {
		$css = ":root {\n\t--hex-h1-size: 3rem;\n\t--hex-color-primary: #123456;\n}\n";

		$this->assertSame(
			array(
				'h1_size'       => '3rem',
				'color_primary' => '#123456',
			),
			hex_parse_style_tokens_css( $css )
		);
	}

	public function test_parse_style_tokens_css_ignores_non_hex_properties_and_returns_empty_for_no_matches() {
		$this->assertSame( array(), hex_parse_style_tokens_css( ':root { color: red; }' ) );
		$this->assertSame( array(), hex_parse_style_tokens_css( '' ) );
	}

	public function test_get_child_theme_tokens_returns_empty_array_when_no_child_theme_active() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );

		$this->assertSame( array(), hex_get_child_theme_tokens() );
	}

	public function test_guess_style_type_detects_a_color() {
		$this->mock_sanitize_hex_color();

		$this->assertSame( 'color', hex_guess_style_type( '#2563eb' ) );
	}

	public function test_guess_style_type_detects_a_length() {
		$this->mock_sanitize_hex_color();

		$this->assertSame( 'length', hex_guess_style_type( '1.5rem' ) );
		$this->assertSame( 'length', hex_guess_style_type( '0' ) );
	}

	public function test_guess_style_type_detects_a_number() {
		$this->mock_sanitize_hex_color();

		$this->assertSame( 'number', hex_guess_style_type( '1.5' ) );
	}

	public function test_guess_style_type_detects_a_font_list() {
		$this->mock_sanitize_hex_color();

		$this->assertSame( 'font', hex_guess_style_type( "Georgia, 'Times New Roman', serif" ) );
	}

	public function test_guess_style_type_falls_back_to_custom() {
		$this->mock_sanitize_hex_color();

		$this->assertSame( 'custom', hex_guess_style_type( 'rgba(0,0,0,.5)' ) );
	}

	public function test_merge_style_schema_with_tokens_adds_a_custom_field_for_an_unknown_key() {
		$this->mock_sanitize_hex_color();

		$schema = array(
			'h1_size' => array(
				'group'   => 'typography',
				'type'    => 'length',
				'default' => '2.5rem',
				'label'   => 'H1 Size',
			),
		);
		$tokens = array(
			'h1_size'         => '3rem',
			'hero_bg_opacity' => '0.6',
		);

		$merged = hex_merge_style_schema_with_tokens( $schema, $tokens );

		$this->assertArrayNotHasKey( 'hero_bg_opacity', $schema, 'Sanity check: input schema must be untouched.' );
		$this->assertSame( 'length', $merged['h1_size']['type'], 'Existing schema fields must not be overwritten.' );
		$this->assertSame(
			array(
				'group'   => 'custom',
				'type'    => 'number',
				'default' => '0.6',
				'label'   => 'Hero Bg Opacity',
			),
			$merged['hero_bg_opacity']
		);
	}

	public function test_effective_style_schema_falls_back_to_the_static_schema_when_no_child_theme_active() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );

		$this->assertSame( hex_get_style_schema(), hex_get_effective_style_schema() );
	}

	public function test_effective_style_groups_has_no_custom_tab_when_no_custom_fields_exist() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );

		$this->assertArrayNotHasKey( 'custom', hex_get_effective_style_groups() );
	}

	public function test_get_style_value_falls_back_to_schema_default() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );

		$this->assertSame( '2.5rem', hex_get_style_value( 'h1_size_mobile' ) );
		$this->assertSame( '2.5rem', hex_get_style_value( 'h1_size_desktop' ) );
	}

	public function test_get_style_value_returns_empty_string_for_an_unknown_key() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );

		$this->assertSame( '', hex_get_style_value( 'not_a_real_key' ) );
	}

	public function test_get_effective_style_values_returns_every_schema_key() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );

		$values = hex_get_effective_style_values();

		$this->assertSame( array_keys( hex_get_style_schema() ), array_keys( $values ) );
		$this->assertSame( '2.5rem', $values['h1_size_mobile'] );
		$this->assertSame( '2.5rem', $values['h1_size_desktop'] );
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

	public function test_fluid_size_pairs_reference_real_schema_keys() {
		$schema = hex_get_style_schema();

		foreach ( hex_get_fluid_size_pairs() as $output_key => $pair ) {
			list( $mobile_key, $desktop_key ) = $pair;

			$this->assertArrayHasKey( $mobile_key, $schema, "Fluid pair for '{$output_key}' references a non-existent mobile key '{$mobile_key}'." );
			$this->assertArrayHasKey( $desktop_key, $schema, "Fluid pair for '{$output_key}' references a non-existent desktop key '{$desktop_key}'." );
			$this->assertArrayNotHasKey( $output_key, $schema, "Fluid output key '{$output_key}' must not itself be a schema field." );
		}
	}

	public function test_build_fluid_clamp_produces_the_interpolation_formula() {
		$this->assertSame(
			'clamp(1.75rem, calc(1.75rem + (2.5rem - 1.75rem) * ((100vw - 640px) / (1600px - 640px))), 2.5rem)',
			hex_build_fluid_clamp( '1.75rem', '2.5rem', '640px', '1600px' )
		);
	}

	public function test_build_fluid_clamp_collapses_to_a_flat_value_when_mobile_equals_desktop() {
		$this->assertSame( '2.5rem', hex_build_fluid_clamp( '2.5rem', '2.5rem', '640px', '1600px' ) );
	}

	public function test_build_fluid_clamp_falls_back_to_desktop_when_breakpoints_are_equal() {
		$this->assertSame( '2.5rem', hex_build_fluid_clamp( '1.75rem', '2.5rem', '640px', '640px' ) );
	}

	public function test_build_fluid_clamp_returns_null_for_an_unsafe_input() {
		$this->assertNull( hex_build_fluid_clamp( '1rem;}body{display:none', '2.5rem', '640px', '1600px' ) );
		$this->assertNull( hex_build_fluid_clamp( '1.75rem', '2.5rem', '640px', '' ) );
	}

	public function test_build_style_tokens_css_includes_every_valid_token() {
		$tokens = array(
			'heading_size'  => '2.5rem',
			'color_primary' => '#2563eb',
		);
		$schema = array(
			'heading_size'  => array( 'type' => 'length' ),
			'color_primary' => array( 'type' => 'color' ),
		);

		$css = hex_build_style_tokens_css( $tokens, $schema );

		$this->assertStringContainsString( ':root {', $css );
		$this->assertStringContainsString( '--hex-heading-size: 2.5rem;', $css );
		$this->assertStringContainsString( '--hex-color-primary: #2563eb;', $css );
	}

	public function test_build_style_tokens_css_skips_an_unsafe_value() {
		$tokens = array( 'heading_size' => '1rem;}body{display:none' );
		$schema = array( 'heading_size' => array( 'type' => 'length' ) );

		$css = hex_build_style_tokens_css( $tokens, $schema );

		$this->assertStringNotContainsString( 'display:none', $css );
	}

	public function test_build_style_tokens_css_appends_a_derived_fluid_clamp_declaration() {
		$tokens = array(
			'h1_size_mobile'          => '1.75rem',
			'h1_size_desktop'         => '2.5rem',
			'fluid_mobile_breakpoint' => '640px',
			'fluid_desktop_breakpoint' => '1600px',
		);
		$schema = array(
			'h1_size_mobile'           => array( 'type' => 'length' ),
			'h1_size_desktop'          => array( 'type' => 'length' ),
			'fluid_mobile_breakpoint'  => array( 'type' => 'length' ),
			'fluid_desktop_breakpoint' => array( 'type' => 'length' ),
		);

		$css = hex_build_style_tokens_css( $tokens, $schema );

		$this->assertStringContainsString( '--hex-h1-size-mobile: 1.75rem;', $css );
		$this->assertStringContainsString( '--hex-h1-size-desktop: 2.5rem;', $css );
		$this->assertStringContainsString(
			'--hex-h1-size: ' . hex_build_fluid_clamp( '1.75rem', '2.5rem', '640px', '1600px' ) . ';',
			$css
		);
	}

	public function test_build_style_tokens_css_never_writes_a_leftover_flat_value_for_a_fluid_output_key() {
		$tokens = array(
			'h1_size'                  => '3rem', // Leftover from before the mobile/desktop pair existed.
			'h1_size_mobile'           => '1.75rem',
			'h1_size_desktop'          => '2.5rem',
			'fluid_mobile_breakpoint'  => '640px',
			'fluid_desktop_breakpoint' => '1600px',
		);
		$schema = array(
			'h1_size'                  => array( 'type' => 'custom' ), // Would be auto-detected as 'custom' by hex_merge_style_schema_with_tokens().
			'h1_size_mobile'           => array( 'type' => 'length' ),
			'h1_size_desktop'          => array( 'type' => 'length' ),
			'fluid_mobile_breakpoint'  => array( 'type' => 'length' ),
			'fluid_desktop_breakpoint' => array( 'type' => 'length' ),
		);

		$css = hex_build_style_tokens_css( $tokens, $schema );

		$this->assertSame( 1, substr_count( $css, '--hex-h1-size:' ), 'Expected exactly one --hex-h1-size declaration (the derived clamp), not the leftover flat value.' );
		$this->assertStringNotContainsString( '--hex-h1-size: 3rem;', $css );
	}

	public function test_build_style_tokens_css_resolves_a_shadow_keyword_to_its_real_css_value() {
		$tokens = array( 'card_shadow' => 'lg' );
		$schema = array( 'card_shadow' => array( 'type' => 'shadow' ) );

		$css = hex_build_style_tokens_css( $tokens, $schema );

		$this->assertStringContainsString( '--hex-card-shadow: ' . hex_get_shadow_presets()['lg'] . ';', $css );
	}

	public function test_build_style_tokens_css_includes_a_valid_custom_value_and_skips_an_invalid_one() {
		$tokens = array(
			'hero_opacity' => '0.6',
			'hero_bg'      => 'url(javascript:alert(1))',
		);
		$schema = array(
			'hero_opacity' => array( 'type' => 'custom' ),
			'hero_bg'      => array( 'type' => 'custom' ),
		);

		$css = hex_build_style_tokens_css( $tokens, $schema );

		$this->assertStringContainsString( '--hex-hero-opacity: 0.6;', $css );
		$this->assertStringNotContainsString( 'javascript', $css );
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

	public function test_sanitize_style_length_rejects_an_invalid_value() {
		$this->assertNull( hex_sanitize_style_length( 'not-a-length' ) );
	}

	public function test_sanitize_style_number_accepts_a_bare_decimal() {
		$this->assertSame( '1.5', hex_sanitize_style_number( '1.5' ) );
		$this->assertSame( '2', hex_sanitize_style_number( '2' ) );
	}

	public function test_sanitize_style_number_rejects_a_value_with_a_unit() {
		$this->assertNull( hex_sanitize_style_number( '1.5rem' ) );
	}

	public function test_sanitize_style_color_accepts_a_valid_hex_color() {
		WP_Mock::userFunction( 'sanitize_hex_color' )->once()->with( '#2563eb' )->andReturn( '#2563eb' );

		$this->assertSame( '#2563eb', hex_sanitize_style_color( '#2563eb' ) );
	}

	public function test_sanitize_style_color_rejects_an_invalid_value() {
		WP_Mock::userFunction( 'sanitize_hex_color' )->once()->andReturn( null );

		$this->assertNull( hex_sanitize_style_color( 'not-a-color' ) );
	}

	public function test_sanitize_style_weight_accepts_a_standard_weight() {
		$this->assertSame( '700', hex_sanitize_style_weight( '700' ) );
	}

	public function test_sanitize_style_weight_rejects_an_out_of_range_value() {
		$this->assertNull( hex_sanitize_style_weight( '750' ) );
	}

	public function test_sanitize_style_shadow_accepts_a_known_preset() {
		$this->assertSame( 'lg', hex_sanitize_style_shadow( 'lg' ) );
	}

	public function test_sanitize_style_shadow_rejects_an_unknown_preset() {
		$this->assertNull( hex_sanitize_style_shadow( 'huge' ) );
	}

	public function test_sanitize_style_font_accepts_a_safe_font_stack() {
		$this->assertSame( "Georgia, 'Times New Roman', serif", hex_sanitize_style_font( "Georgia, 'Times New Roman', serif" ) );
	}

	public function test_sanitize_style_font_rejects_an_unsafe_value() {
		$this->assertNull( hex_sanitize_style_font( 'url(javascript:alert(1))' ) );
	}

	public function test_sanitize_style_custom_accepts_a_safe_value() {
		$this->assertSame( 'rgba(0,0,0,.5)', hex_sanitize_style_custom( 'rgba(0,0,0,.5)' ) );
	}

	public function test_sanitize_style_custom_rejects_url_and_quotes_and_empty_and_overlong_values() {
		$this->assertNull( hex_sanitize_style_custom( 'url(javascript:alert(1))' ) );
		$this->assertNull( hex_sanitize_style_custom( '"1rem"' ) );
		$this->assertNull( hex_sanitize_style_custom( '' ) );
		$this->assertNull( hex_sanitize_style_custom( str_repeat( 'a', 201 ) ) );
	}

	public function test_sanitize_submitted_style_tokens_accepts_valid_submissions() {
		$schema = array(
			'h1_size' => array(
				'type'    => 'length',
				'default' => '2.5rem',
				'label'   => 'H1 Size',
			),
		);

		$result = hex_sanitize_submitted_style_tokens(
			array( 'h1_size' => '3rem' ),
			$schema,
			array( 'h1_size' => '2.5rem' )
		);

		$this->assertSame( array( 'h1_size' => '3rem' ), $result['tokens'] );
		$this->assertSame( array(), $result['rejected'] );
	}

	public function test_sanitize_submitted_style_tokens_falls_back_and_reports_a_rejected_field() {
		$schema = array(
			'h1_size' => array(
				'type'    => 'length',
				'default' => '2.5rem',
				'label'   => 'H1 Size',
			),
		);

		$result = hex_sanitize_submitted_style_tokens(
			array( 'h1_size' => 'not-a-length' ),
			$schema,
			array( 'h1_size' => '3rem' )
		);

		$this->assertSame( array( 'h1_size' => '3rem' ), $result['tokens'] );
		$this->assertSame( array( 'H1 Size' ), $result['rejected'] );
	}

	public function test_shadow_presets_include_none_through_extra_large() {
		$this->assertSame( array( 'none', 'sm', 'md', 'lg', 'xl' ), array_keys( hex_get_shadow_presets() ) );
	}

	public function test_humanize_key_converts_snake_case_to_title_case() {
		$this->assertSame( 'Body Background', hex_style_humanize_key( 'body_background' ) );
	}

	public function test_safe_font_value_accepts_a_quoted_font_stack_and_rejects_unsafe_input() {
		$this->assertTrue( hex_is_safe_font_value( "Georgia, 'Times New Roman', sans-serif" ) );
		$this->assertFalse( hex_is_safe_font_value( 'a; } body { display:none' ) );
	}

	public function test_style_sanitize_callback_for_type_includes_custom() {
		$this->assertSame( 'hex_sanitize_style_custom', hex_style_sanitize_callback_for_type( 'custom' ) );
	}
}
