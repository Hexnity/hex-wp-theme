<?php
/**
 * Tests for inc/google-fonts.php — the Font Library (the theme's sole
 * font-selection mechanism; the older free-text/URL-paste picker was
 * removed, see knoladge/google-fonts-picker.md).
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_google_fonts_resource_hints
 * @covers ::hex_get_common_google_fonts
 * @covers ::hex_get_google_font_by_stack
 * @covers ::hex_font_stack_primary_name
 * @covers ::hex_get_google_font_by_name
 * @covers ::hex_resolve_google_font_field_selection
 * @covers ::hex_get_font_library_selection
 * @covers ::hex_build_font_library_url
 * @covers ::hex_enqueue_font_library
 */
class GoogleFontsTest extends TestCase {

	/*
	 * hex_google_fonts_resource_hints()'s "at least one Font Library
	 * font is selected -> add preconnects" branch is NOT covered here:
	 * it depends on hex_get_font_library_selection() -> hex_get_style_value()
	 * -> a real saved theme-options.css read (is_child_theme() true +
	 * file_exists()/file_get_contents()), and this suite can't mock
	 * project-defined functions or native filesystem calls (confirmed:
	 * WP_Mock::userFunction() silently has no effect on a
	 * project-defined function like hex_get_style_value() — it isn't a
	 * WP-core function WP_Mock knows to intercept). Same documented
	 * constraint as hex_get_child_theme_tokens() elsewhere — see
	 * knoladge/child-theme-css-token-architecture.md. The URL-building
	 * logic this branch would use is still fully covered directly via
	 * hex_build_font_library_url()'s own tests below (a pure function
	 * that takes a selection as a parameter instead of reading it).
	 */

	public function test_resource_hints_leaves_other_relation_types_untouched() {
		$result = hex_google_fonts_resource_hints( array( 'https://example.com' ), 'dns-prefetch' );

		$this->assertSame( array( 'https://example.com' ), $result );
	}

	public function test_resource_hints_adds_nothing_when_no_font_selected() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$result = hex_google_fonts_resource_hints( array(), 'preconnect' );

		$this->assertSame( array(), $result );
	}

	public function test_common_fonts_list_is_well_formed() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$fonts = hex_get_common_google_fonts();

		$this->assertNotEmpty( $fonts );

		foreach ( $fonts as $slug => $font ) {
			$this->assertIsString( $slug );
			$this->assertArrayHasKey( 'name', $font );
			$this->assertArrayHasKey( 'category', $font );
			$this->assertArrayHasKey( 'stack', $font );
			$this->assertArrayHasKey( 'weights', $font );
			$this->assertTrue(
				hex_is_safe_font_value( $font['stack'] ),
				"Font '{$slug}' has a stack that would be rejected by hex_is_safe_font_value(): {$font['stack']}"
			);
		}
	}

	public function test_common_fonts_list_has_no_duplicate_stacks() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$stacks = array_column( hex_get_common_google_fonts(), 'stack' );

		$this->assertSame( $stacks, array_unique( $stacks ) );
	}

	public function test_get_google_font_by_stack_finds_a_known_font() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$font = hex_get_google_font_by_stack( "'Inter', sans-serif" );

		$this->assertNotNull( $font );
		$this->assertSame( 'Inter', $font['name'] );
	}

	public function test_get_google_font_by_stack_returns_null_for_an_unknown_stack() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$this->assertNull( hex_get_google_font_by_stack( 'Comic Sans MS, cursive' ) );
	}

	public function test_font_stack_primary_name_extracts_a_quoted_leading_name() {
		$this->assertSame(
			'Instrument Serif',
			hex_font_stack_primary_name( "'Instrument Serif', Georgia, 'Times New Roman', serif" )
		);
	}

	public function test_font_stack_primary_name_extracts_a_bare_leading_name() {
		$this->assertSame( 'Georgia', hex_font_stack_primary_name( 'Georgia, serif' ) );
	}

	public function test_get_google_font_by_name_matches_regardless_of_fallback_chain() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		// A hand-edited theme-options.css value can carry a longer fallback
		// chain than this list's own canonical stack for the same font.
		$font = hex_get_google_font_by_name( "'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif" );

		$this->assertNotNull( $font );
		$this->assertSame( 'Inter', $font['name'] );
		$this->assertSame( "'Inter', sans-serif", $font['stack'], 'The matched entry\'s own canonical stack, not the hand-typed one.' );
	}

	public function test_get_google_font_by_name_is_case_insensitive() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$font = hex_get_google_font_by_name( 'inter, sans-serif' );

		$this->assertNotNull( $font );
		$this->assertSame( 'Inter', $font['name'] );
	}

	public function test_get_google_font_by_name_returns_null_for_an_unknown_name() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$this->assertNull( hex_get_google_font_by_name( 'Comic Sans MS, cursive' ) );
	}

	public function test_resolve_google_font_field_selection_returns_empty_value_unchanged() {
		$this->assertSame( '', hex_resolve_google_font_field_selection( '' ) );
	}

	public function test_resolve_google_font_field_selection_returns_an_exact_stack_match_unchanged() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$this->assertSame(
			"'Inter', sans-serif",
			hex_resolve_google_font_field_selection( "'Inter', sans-serif" )
		);
	}

	public function test_resolve_google_font_field_selection_normalizes_a_hand_typed_longer_fallback_chain() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$this->assertSame(
			"'Instrument Serif', serif",
			hex_resolve_google_font_field_selection( "'Instrument Serif', Georgia, 'Times New Roman', serif" )
		);
	}

	public function test_resolve_google_font_field_selection_returns_an_unrecognized_value_unchanged() {
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$this->assertSame(
			'Comic Sans MS, cursive',
			hex_resolve_google_font_field_selection( 'Comic Sans MS, cursive' )
		);
	}

	public function test_font_library_selection_is_empty_when_nothing_saved() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );

		$this->assertSame( array(), hex_get_font_library_selection() );
	}

	public function test_build_font_library_url_returns_empty_string_for_no_selection() {
		$this->assertSame( '', hex_build_font_library_url( array() ) );
	}

	public function test_build_font_library_url_builds_one_family_param() {
		$selected = array(
			'font_heading' => array(
				'name'     => 'Inter',
				'category' => 'Sans Serif',
				'stack'    => "'Inter', sans-serif",
				'weights'  => '400;500;600;700',
			),
		);

		$this->assertSame(
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			hex_build_font_library_url( $selected )
		);
	}

	public function test_build_font_library_url_combines_distinct_families_into_one_request() {
		$selected = array(
			'font_heading' => array(
				'name'     => 'Playfair Display',
				'category' => 'Serif',
				'stack'    => "'Playfair Display', serif",
				'weights'  => '400;600;700',
			),
			'font_body'    => array(
				'name'     => 'Inter',
				'category' => 'Sans Serif',
				'stack'    => "'Inter', sans-serif",
				'weights'  => '400;500;600;700',
			),
		);

		$this->assertSame(
			'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap',
			hex_build_font_library_url( $selected )
		);
	}

	public function test_build_font_library_url_dedupes_the_same_family_across_slots() {
		$inter = array(
			'name'     => 'Inter',
			'category' => 'Sans Serif',
			'stack'    => "'Inter', sans-serif",
			'weights'  => '400;500;600;700',
		);

		$selected = array(
			'font_heading' => $inter,
			'font_accent'  => $inter,
		);

		$this->assertSame(
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			hex_build_font_library_url( $selected )
		);
	}

	public function test_enqueue_font_library_does_nothing_when_nothing_selected() {
		WP_Mock::userFunction( 'is_child_theme' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );
		WP_Mock::userFunction( 'wp_enqueue_style' )->with( 'hex-font-library', \Mockery::any(), \Mockery::any(), \Mockery::any() )->never();

		hex_enqueue_font_library();

		$this->assertConditionsMet();
	}
}
