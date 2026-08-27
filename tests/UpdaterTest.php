<?php
/**
 * Tests for inc/updater.php and the settings sanitize callbacks in
 * inc/admin/settings.php. Only the theme's own decision logic is
 * covered here — building a real update checker against the vendored
 * third-party library is deliberately out of scope (see
 * knoladge/wp-mock-unit-testing.md).
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_get_github_branch
 * @covers ::hex_get_update_checker
 * @covers ::hex_check_for_theme_update
 * @covers ::hex_perform_theme_update
 * @covers ::hex_sanitize_github_repo
 * @covers ::hex_sanitize_github_branch
 */
class UpdaterTest extends TestCase {

	public function test_branch_defaults_to_main_when_unset() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_github_branch', '' )
			->andReturn( '' );

		$this->assertSame( 'main', hex_get_github_branch() );
	}

	public function test_branch_returns_the_configured_value() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_github_branch', '' )
			->andReturn( 'develop' );

		$this->assertSame( 'develop', hex_get_github_branch() );
	}

	public function test_update_checker_is_null_when_no_repo_is_configured() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_github_repo', '' )
			->andReturn( '' );

		$this->assertNull( hex_get_update_checker() );
	}

	public function test_check_for_update_reports_not_configured() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_github_repo', '' )
			->andReturn( '' );

		$this->assertStringContainsString(
			'Save a GitHub repository',
			hex_check_for_theme_update()
		);
	}

	public function test_perform_update_reports_not_configured() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_github_repo', '' )
			->andReturn( '' );

		$this->assertStringContainsString(
			'Save a GitHub repository',
			hex_perform_theme_update()
		);
	}

	public function test_sanitize_repo_accepts_owner_slash_repo() {
		$this->assertSame( 'acme/my-theme', hex_sanitize_github_repo( ' acme/my-theme ' ) );
	}

	public function test_sanitize_repo_allows_blank_without_recording_an_error() {
		$this->assertSame( '', hex_sanitize_github_repo( '   ' ) );
	}

	public function test_sanitize_repo_rejects_invalid_format() {
		WP_Mock::userFunction( 'add_settings_error' )->once();

		$this->assertSame( '', hex_sanitize_github_repo( 'not a repo!!' ) );
	}

	public function test_sanitize_branch_trims_and_delegates_to_sanitize_text_field() {
		WP_Mock::userFunction( 'sanitize_text_field' )
			->once()
			->andReturnUsing( fn( $value ) => trim( (string) $value ) );

		$this->assertSame( 'develop', hex_sanitize_github_branch( '  develop  ' ) );
	}
}
