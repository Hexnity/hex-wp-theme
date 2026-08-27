<?php
/**
 * Static consistency checks: required template files exist, the
 * three page templates match what hex_get_page_templates() declares,
 * and style.css's version stays in sync with functions.php.
 *
 * These catch the exact class of drift the versioning rule in
 * claude.md warns about, without needing a live WordPress request.
 *
 * @package Hex
 */

use PHPUnit\Framework\TestCase;

class ThemeFilesTest extends TestCase {

	/**
	 * @dataProvider required_template_files_provider
	 */
	public function test_required_template_file_exists( string $file ) {
		$this->assertFileExists( HEX_THEME_DIR . '/' . $file );
	}

	public static function required_template_files_provider(): array {
		return array(
			array( 'style.css' ),
			array( 'functions.php' ),
			array( 'header.php' ),
			array( 'footer.php' ),
			array( 'index.php' ),
			array( 'page.php' ),
			array( 'single.php' ),
			array( 'archive.php' ),
			array( 'search.php' ),
			array( '404.php' ),
			array( 'comments.php' ),
		);
	}

	public function test_every_declared_page_template_file_exists_with_matching_template_name_header() {
		foreach ( hex_get_page_templates() as $file => $label ) {
			$path = HEX_THEME_DIR . '/' . $file;
			$this->assertFileExists( $path );

			$header = file_get_contents( $path, false, null, 0, 1024 );
			$this->assertMatchesRegularExpression(
				'/Template Name:\s*' . preg_quote( $label, '/' ) . '/',
				$header,
				"{$file} must declare \"Template Name: {$label}\" to match hex_get_page_templates()."
			);
		}
	}

	public function test_style_css_version_matches_hex_version_constant() {
		$style_css = file_get_contents( HEX_THEME_DIR . '/style.css', false, null, 0, 2048 );

		$this->assertMatchesRegularExpression( '/^Version:\s*(.+)$/m', $style_css );
		preg_match( '/^Version:\s*(.+)$/m', $style_css, $matches );

		$this->assertSame(
			HEX_VERSION,
			trim( $matches[1] ),
			"style.css's Version header must always match the HEX_VERSION constant in functions.php."
		);
	}
}
