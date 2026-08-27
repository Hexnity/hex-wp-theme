<?php
/**
 * Tests for inc/child-theme.php and its settings sanitize callback.
 * The actual install (hex_install_child_theme_from_repo()'s
 * Theme_Upgrader/WP_Filesystem path) is deliberately not
 * unit-tested — see knoladge/wp-mock-unit-testing.md for why.
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_fetch_remote_child_style_css
 * @covers ::hex_validate_child_theme_repo
 * @covers ::hex_install_child_theme_from_repo
 * @covers ::hex_get_child_github_branch
 * @covers ::hex_get_child_theme_slug
 * @covers ::hex_is_child_theme_active
 * @covers ::hex_check_for_child_theme_update
 * @covers ::hex_perform_child_theme_update
 * @covers ::hex_sanitize_child_github_repo
 */
class ChildThemeTest extends TestCase {

	/**
	 * Stub the child activation key + a wp_remote_get response, since
	 * every fetch function reads the key first.
	 *
	 * @param string        $body     Response body.
	 * @param int           $code     Response status code.
	 * @param bool|WP_Error $wp_error Simulate wp_remote_get() itself failing.
	 * @return void
	 */
	private function mock_remote_style_css( $body, $code = 200, $wp_error = false ) {
		WP_Mock::userFunction( 'get_option' )
			->with( 'hex_child_activation_key', '' )
			->andReturn( '' );

		if ( $wp_error ) {
			WP_Mock::userFunction( 'wp_remote_get' )->once()->andReturn( new WP_Error( 'http_request_failed', 'timeout' ) );
			WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing( fn( $v ) => $v instanceof WP_Error );
			return;
		}

		WP_Mock::userFunction( 'wp_remote_get' )->once()->andReturn( array( 'body' => $body ) );
		WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing( fn( $v ) => $v instanceof WP_Error );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->once()->andReturn( $code );

		if ( 200 === $code ) {
			WP_Mock::userFunction( 'wp_remote_retrieve_body' )->once()->andReturn( $body );
		}
	}

	public function test_fetch_reads_theme_name_and_template_headers() {
		$this->mock_remote_style_css( "/*\nTheme Name: My Child\nTemplate: hex-wp-theme-template\n*/" );

		$result = hex_fetch_remote_child_style_css( 'acme/my-child', 'main' );

		$this->assertSame( array( 'My Child', 'hex-wp-theme-template' ), $result );
	}

	public function test_fetch_fails_on_non_200_response() {
		$this->mock_remote_style_css( '', 404 );

		$result = hex_fetch_remote_child_style_css( 'acme/my-child', 'main' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_fetch_fails_when_no_template_header_present() {
		$this->mock_remote_style_css( "/*\nTheme Name: Not A Child\n*/" );

		$result = hex_fetch_remote_child_style_css( 'acme/not-a-child', 'main' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_fetch_propagates_a_wp_remote_get_failure() {
		$this->mock_remote_style_css( '', 200, true );

		$result = hex_fetch_remote_child_style_css( 'acme/my-child', 'main' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_accepts_a_repo_declaring_this_theme_as_template() {
		WP_Mock::userFunction( 'get_template' )->andReturn( 'hex-wp-theme-template' );
		$this->mock_remote_style_css( "/*\nTheme Name: My Child\nTemplate: hex-wp-theme-template\n*/" );

		$result = hex_validate_child_theme_repo( 'acme/my-child', 'main' );

		$this->assertSame( array( 'My Child', 'hex-wp-theme-template' ), $result );
	}

	public function test_validate_rejects_a_repo_declaring_a_different_template() {
		WP_Mock::userFunction( 'get_template' )->andReturn( 'hex-wp-theme-template' );
		$this->mock_remote_style_css( "/*\nTheme Name: Someone Else's Child\nTemplate: twentytwentyfive\n*/" );

		$result = hex_validate_child_theme_repo( 'acme/wrong-child', 'main' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_install_rejects_an_invalid_repo_format_before_any_http_call() {
		$result = hex_install_child_theme_from_repo( 'not a repo!!', 'main' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_install_stops_at_validation_when_template_mismatches() {
		WP_Mock::userFunction( 'get_template' )->andReturn( 'hex-wp-theme-template' );
		$this->mock_remote_style_css( "/*\nTheme Name: Wrong\nTemplate: twentytwentyfive\n*/" );

		$result = hex_install_child_theme_from_repo( 'acme/wrong-child', 'main' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_child_branch_defaults_to_main_when_unset() {
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_child_github_branch', '' )
			->andReturn( '' );

		$this->assertSame( 'main', hex_get_child_github_branch() );
	}

	public function test_is_child_theme_active_is_false_when_no_child_is_active() {
		WP_Mock::userFunction( 'is_child_theme' )->once()->andReturn( false );

		$this->assertFalse( hex_is_child_theme_active() );
	}

	public function test_is_child_theme_active_is_true_whenever_any_child_of_this_theme_is_active() {
		// True regardless of whether it matches our own tracked option —
		// this is the actual bug this test guards against: a child theme
		// activated by any means other than hex_install_child_theme_from_repo()
		// (manual copy, git clone, etc.) must still be detected.
		WP_Mock::userFunction( 'is_child_theme' )->once()->andReturn( true );

		$this->assertTrue( hex_is_child_theme_active() );
	}

	public function test_get_child_theme_slug_prefers_the_live_active_child_over_the_stored_option() {
		WP_Mock::userFunction( 'is_child_theme' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_stylesheet' )->once()->andReturn( 'hexnity-wp-child' );

		$this->assertSame( 'hexnity-wp-child', hex_get_child_theme_slug() );
	}

	public function test_get_child_theme_slug_falls_back_to_the_stored_option_when_no_child_is_active() {
		WP_Mock::userFunction( 'is_child_theme' )->once()->andReturn( false );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_child_theme_slug', '' )
			->andReturn( 'previously-installed-child' );

		$this->assertSame( 'previously-installed-child', hex_get_child_theme_slug() );
	}

	public function test_check_for_child_update_reports_no_child_theme_yet() {
		WP_Mock::userFunction( 'is_child_theme' )->once()->andReturn( false );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_child_theme_slug', '' )
			->andReturn( '' );

		$this->assertSame( 'No child theme has been installed yet.', hex_check_for_child_theme_update() );
	}

	public function test_perform_child_update_reports_no_child_theme_yet() {
		WP_Mock::userFunction( 'is_child_theme' )->once()->andReturn( false );
		WP_Mock::userFunction( 'get_option' )
			->once()
			->with( 'hex_child_theme_slug', '' )
			->andReturn( '' );

		$this->assertSame( 'No child theme has been installed yet.', hex_perform_child_theme_update() );
	}

	public function test_sanitize_child_repo_accepts_owner_slash_repo() {
		$this->assertSame( 'acme/my-child', hex_sanitize_child_github_repo( ' acme/my-child ' ) );
	}

	public function test_sanitize_child_repo_rejects_invalid_format() {
		WP_Mock::userFunction( 'add_settings_error' )->once();

		$this->assertSame( '', hex_sanitize_child_github_repo( 'not a repo!!' ) );
	}
}
