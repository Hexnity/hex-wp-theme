<?php
/**
 * Tests for the Customizer footer-text sanitize callback.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_sanitize_footer_text
 */
class CustomizerTest extends TestCase {

	public function test_html_tags_are_stripped() {
		WP_Mock::userFunction( 'wp_strip_all_tags' )
			->once()
			->with( '<strong>Acme</strong> Inc.' )
			->andReturnUsing( fn( $value ) => strip_tags( $value ) );

		WP_Mock::userFunction( 'sanitize_text_field' )
			->once()
			->andReturnUsing( fn( $value ) => trim( (string) $value ) );

		$this->assertSame( 'Acme Inc.', hex_sanitize_footer_text( '<strong>Acme</strong> Inc.' ) );
	}

	public function test_non_string_input_is_cast_to_string() {
		WP_Mock::userFunction( 'wp_strip_all_tags' )->once()->andReturnUsing( fn( $value ) => (string) $value );
		WP_Mock::userFunction( 'sanitize_text_field' )->once()->andReturnUsing( fn( $value ) => (string) $value );

		$this->assertSame( '2026', hex_sanitize_footer_text( 2026 ) );
	}
}
