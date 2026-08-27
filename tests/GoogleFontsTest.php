<?php
/**
 * Tests for inc/google-fonts.php.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_sanitize_google_fonts_urls
 * @covers ::hex_get_google_fonts_urls
 * @covers ::hex_get_google_font_families
 * @covers ::hex_enqueue_google_fonts
 * @covers ::hex_google_fonts_resource_hints
 * @covers ::hex_render_google_fonts_datalist
 */
class GoogleFontsTest extends TestCase {

	public function test_sanitize_extracts_a_bare_url() {
		WP_Mock::userFunction( 'esc_url_raw' )->andReturnUsing( fn( $url ) => $url );

		$result = hex_sanitize_google_fonts_urls( 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap' );

		$this->assertSame( 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap', $result );
	}

	public function test_sanitize_extracts_only_the_stylesheet_url_from_a_pasted_embed_snippet() {
		WP_Mock::userFunction( 'esc_url_raw' )->andReturnUsing( fn( $url ) => $url );

		$snippet = <<<HTML
			<link rel="preconnect" href="https://fonts.googleapis.com">
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;1,400&family=Raleway:wght@400&display=swap" rel="stylesheet">
			HTML;

		$result = hex_sanitize_google_fonts_urls( $snippet );

		$this->assertSame(
			'https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;1,400&family=Raleway:wght@400&display=swap',
			$result
		);
	}

	public function test_sanitize_rejects_a_url_on_a_different_host() {
		WP_Mock::userFunction( 'esc_url_raw' )->andReturnUsing( fn( $url ) => $url );

		$result = hex_sanitize_google_fonts_urls( 'https://evil.example.com/css2?family=Inter' );

		$this->assertSame( '', $result );
	}

	public function test_sanitize_dedupes_repeated_urls() {
		WP_Mock::userFunction( 'esc_url_raw' )->andReturnUsing( fn( $url ) => $url );

		$url   = 'https://fonts.googleapis.com/css2?family=Inter:wght@400';
		$value = "$url\n$url";

		$this->assertSame( $url, hex_sanitize_google_fonts_urls( $value ) );
	}

	public function test_sanitize_keeps_multiple_distinct_urls_one_per_line() {
		WP_Mock::userFunction( 'esc_url_raw' )->andReturnUsing( fn( $url ) => $url );

		$one = 'https://fonts.googleapis.com/css2?family=Inter:wght@400';
		$two = 'https://fonts.googleapis.com/css2?family=Roboto:wght@400';

		$result = hex_sanitize_google_fonts_urls( "$one\n$two" );

		$this->assertSame( array( $one, $two ), explode( "\n", $result ) );
	}

	public function test_get_urls_returns_empty_array_when_option_is_empty() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )->andReturn( '' );

		$this->assertSame( array(), hex_get_google_fonts_urls() );
	}

	public function test_get_urls_splits_the_stored_option_by_line() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( "https://fonts.googleapis.com/css2?family=Inter\nhttps://fonts.googleapis.com/css2?family=Roboto" );

		$this->assertSame(
			array(
				'https://fonts.googleapis.com/css2?family=Inter',
				'https://fonts.googleapis.com/css2?family=Roboto',
			),
			hex_get_google_fonts_urls()
		);
	}

	public function test_get_families_parses_a_single_family_from_one_url() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap' );

		$this->assertSame( array( 'Inter' ), hex_get_google_font_families() );
	}

	public function test_get_families_parses_multiple_families_from_one_url() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( 'https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;1,400&family=Raleway:wght@400&family=Roboto:wght@400&display=swap' );

		$this->assertSame( array( 'Inter', 'Raleway', 'Roboto' ), hex_get_google_font_families() );
	}

	public function test_get_families_decodes_plus_signs_in_multi_word_family_names() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400' );

		$this->assertSame( array( 'Playfair Display' ), hex_get_google_font_families() );
	}

	public function test_get_families_dedupes_the_same_family_across_urls() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( "https://fonts.googleapis.com/css2?family=Inter:wght@400\nhttps://fonts.googleapis.com/css2?family=Inter:wght@700" );

		$this->assertSame( array( 'Inter' ), hex_get_google_font_families() );
	}

	public function test_get_families_returns_empty_array_when_nothing_configured() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )->andReturn( '' );

		$this->assertSame( array(), hex_get_google_font_families() );
	}

	public function test_enqueue_registers_a_style_per_configured_url() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( "https://fonts.googleapis.com/css2?family=Inter\nhttps://fonts.googleapis.com/css2?family=Roboto" );

		WP_Mock::userFunction( 'wp_enqueue_style' )
			->with( 'hex-google-fonts-0', 'https://fonts.googleapis.com/css2?family=Inter', array(), null )
			->once();
		WP_Mock::userFunction( 'wp_enqueue_style' )
			->with( 'hex-google-fonts-1', 'https://fonts.googleapis.com/css2?family=Roboto', array(), null )
			->once();

		hex_enqueue_google_fonts();

		$this->assertConditionsMet();
	}

	public function test_enqueue_does_nothing_when_no_fonts_configured() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_enqueue_style' )->never();

		hex_enqueue_google_fonts();

		$this->assertConditionsMet();
	}

	public function test_resource_hints_adds_preconnects_when_fonts_are_configured() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( 'https://fonts.googleapis.com/css2?family=Inter' );

		$result = hex_google_fonts_resource_hints( array( 'https://example.com' ), 'preconnect' );

		$this->assertContains( 'https://example.com', $result );
		$this->assertCount( 3, $result );
		$this->assertSame( 'https://fonts.googleapis.com', $result[1]['href'] );
		$this->assertSame( 'https://fonts.gstatic.com', $result[2]['href'] );
		$this->assertSame( 'anonymous', $result[2]['crossorigin'] );
	}

	public function test_resource_hints_leaves_other_relation_types_untouched() {
		$result = hex_google_fonts_resource_hints( array( 'https://example.com' ), 'dns-prefetch' );

		$this->assertSame( array( 'https://example.com' ), $result );
	}

	public function test_resource_hints_adds_nothing_when_no_fonts_configured() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )->andReturn( '' );

		$result = hex_google_fonts_resource_hints( array(), 'preconnect' );

		$this->assertSame( array(), $result );
	}

	public function test_datalist_renders_an_option_per_family() {
		WP_Mock::userFunction( 'get_option' )->with( 'hex_google_fonts_urls', '' )
			->andReturn( 'https://fonts.googleapis.com/css2?family=Inter&family=Playfair+Display' );
		WP_Mock::userFunction( 'esc_attr' )->andReturnUsing( fn( $v ) => $v );

		ob_start();
		hex_render_google_fonts_datalist();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<datalist id="hex-google-fonts-list">', $output );
		$this->assertStringContainsString( '<option value="Inter">', $output );
		$this->assertStringContainsString( '<option value="Playfair Display">', $output );
	}
}
