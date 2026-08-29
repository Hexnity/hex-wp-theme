<?php
/**
 * Tests for inc/page-content.php — the "Page Content" JSON framework
 * (a custom table, one row per page; see that file's own header
 * docblock for the full architecture).
 *
 * hex_create_page_content_table()/hex_maybe_upgrade_page_content_table()
 * are NOT covered here: dbDelta() requires a real
 * wp-admin/includes/upgrade.php (via require_once) and performs a
 * real schema operation against a real database connection — neither
 * exists in this WP_Mock-based suite (no live WP install, no DB). Same
 * documented constraint as other genuinely I/O-bound functions
 * elsewhere in this theme (see
 * knoladge/child-theme-css-token-architecture.md).
 *
 * @package Hex
 */

use WP_Mock\Tools\TestCase;

/**
 * @covers ::hex_page_content_table_name
 * @covers ::hex_sanitize_page_content_value
 * @covers ::hex_parse_submitted_page_content
 * @covers ::hex_decode_page_content
 * @covers ::hex_encode_page_content_for_display
 * @covers ::hex_get_page_content
 * @covers ::hex_get_page_content_raw
 * @covers ::hex_save_page_content
 * @covers ::hex_delete_page_content
 * @covers ::hex_delete_page_content_on_post_delete
 * @covers ::hex_save_page_content_meta_box
 * @covers ::hex_page_content_error_notice
 */
class PageContentTest extends TestCase {

	/**
	 * Install a Mockery mock of $wpdb as the global, so DB-touching
	 * functions can be tested without a real database connection.
	 * prepare() is stubbed to just return its own SQL string unchanged
	 * (the exact SQL text isn't what these tests are checking; the
	 * branching/return-value behavior of the functions under test is).
	 *
	 * @return \Mockery\MockInterface
	 */
	private function mock_wpdb() {
		global $wpdb;
		$wpdb         = \Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( fn( $sql, ...$args ) => $sql );

		return $wpdb;
	}

	/**
	 * hex_save_page_content_meta_box() always sanitizes the nonce field
	 * before verifying it; mocked as a passthrough here (not asserted on)
	 * since sanitize_text_field()'s own behavior is covered elsewhere
	 * (CustomizerTest, UpdaterTest) and isn't what these tests are about.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnUsing( fn( $v ) => $v );
	}

	public function tearDown(): void {
		global $wpdb;
		$wpdb = null;

		parent::tearDown();
	}

	public function test_table_name_uses_the_site_prefix() {
		$this->mock_wpdb();

		$this->assertSame( 'wp_hex_page_content', hex_page_content_table_name() );
	}

	public function test_sanitize_page_content_value_sanitizes_a_string_leaf() {
		WP_Mock::userFunction( 'wp_kses_post' )->with( '<script>alert(1)</script>Hello' )->andReturn( 'Hello' );

		$this->assertSame( 'Hello', hex_sanitize_page_content_value( '<script>alert(1)</script>Hello' ) );
	}

	public function test_sanitize_page_content_value_recurses_into_nested_arrays() {
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnUsing( fn( $v ) => 'clean:' . $v );

		$result = hex_sanitize_page_content_value(
			array(
				'hero' => array(
					'heading' => 'Title',
					'items'   => array( 'One', 'Two' ),
				),
			)
		);

		$this->assertSame(
			array(
				'hero' => array(
					'heading' => 'clean:Title',
					'items'   => array( 'clean:One', 'clean:Two' ),
				),
			),
			$result
		);
	}

	public function test_sanitize_page_content_value_leaves_non_string_scalars_and_null_unchanged() {
		$this->assertSame( 42, hex_sanitize_page_content_value( 42 ) );
		$this->assertSame( 4.5, hex_sanitize_page_content_value( 4.5 ) );
		$this->assertTrue( hex_sanitize_page_content_value( true ) );
		$this->assertNull( hex_sanitize_page_content_value( null ) );
	}

	public function test_parse_submitted_page_content_accepts_a_valid_json_object() {
		$this->assertSame(
			array( 'hero_heading' => 'Welcome' ),
			hex_parse_submitted_page_content( '{"hero_heading":"Welcome"}' )
		);
	}

	public function test_parse_submitted_page_content_accepts_a_valid_json_array() {
		$this->assertSame( array( 'One', 'Two' ), hex_parse_submitted_page_content( '["One","Two"]' ) );
	}

	public function test_parse_submitted_page_content_rejects_malformed_json() {
		$this->assertNull( hex_parse_submitted_page_content( '{not valid json' ) );
	}

	public function test_parse_submitted_page_content_rejects_a_bare_json_scalar() {
		// Valid JSON, but not an object/array -- json_decode(..., true)
		// would return a plain string/number/bool, not something a page
		// template could meaningfully key into.
		$this->assertNull( hex_parse_submitted_page_content( '"just a string"' ) );
		$this->assertNull( hex_parse_submitted_page_content( '42' ) );
	}

	public function test_parse_submitted_page_content_rejects_an_oversized_submission() {
		$huge = '{"a":"' . str_repeat( 'x', 500001 ) . '"}';

		$this->assertNull( hex_parse_submitted_page_content( $huge ) );
	}

	public function test_decode_page_content_parses_valid_json() {
		$this->assertSame( array( 'a' => 1 ), hex_decode_page_content( '{"a":1}' ) );
	}

	public function test_decode_page_content_returns_empty_array_for_invalid_json() {
		$this->assertSame( array(), hex_decode_page_content( 'not json' ) );
	}

	public function test_encode_page_content_for_display_returns_empty_object_for_no_content() {
		$this->assertSame( '{}', hex_encode_page_content_for_display( array() ) );
	}

	public function test_encode_page_content_for_display_pretty_prints_content() {
		$json = hex_encode_page_content_for_display( array( 'hero_heading' => 'Welcome' ) );

		$this->assertStringContainsString( "\"hero_heading\": \"Welcome\"", $json );
	}

	public function test_get_page_content_returns_empty_array_for_a_falsy_page_id() {
		$this->assertSame( array(), hex_get_page_content( 0 ) );
	}

	public function test_get_page_content_returns_empty_array_when_no_row_exists() {
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_row' )->andReturn( null );

		$this->assertSame( array(), hex_get_page_content( 42 ) );
	}

	public function test_get_page_content_decodes_the_stored_row() {
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_row' )->andReturn( (object) array( 'content' => '{"hero_heading":"Welcome"}' ) );

		$this->assertSame( array( 'hero_heading' => 'Welcome' ), hex_get_page_content( 42 ) );
	}

	public function test_get_page_content_defaults_to_the_current_post_when_no_id_given() {
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 7 );

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_row' )->andReturn( (object) array( 'content' => '{"x":1}' ) );

		$this->assertSame( array( 'x' => 1 ), hex_get_page_content() );
	}

	public function test_get_page_content_raw_returns_pretty_json_for_a_saved_page() {
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_row' )->andReturn( (object) array( 'content' => '{"a":1}' ) );

		$this->assertStringContainsString( '"a": 1', hex_get_page_content_raw( 9 ) );
	}

	public function test_get_page_content_raw_returns_empty_object_for_an_unsaved_page() {
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_row' )->andReturn( null );

		$this->assertSame( '{}', hex_get_page_content_raw( 9 ) );
	}

	public function test_save_page_content_rejects_a_falsy_page_id() {
		$this->assertFalse( hex_save_page_content( 0, array( 'a' => '1' ) ) );
	}

	public function test_save_page_content_inserts_a_new_row() {
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnUsing( fn( $v ) => $v );
		WP_Mock::userFunction( 'current_time' )->with( 'mysql' )->andReturn( '2026-08-29 12:00:00' );

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_var' )->andReturn( null ); // No existing row.
		$wpdb->shouldReceive( 'insert' )
			->with(
				'wp_hex_page_content',
				array(
					'page_id'    => 5,
					'content'    => '{"hero_heading":"Welcome"}',
					'created_at' => '2026-08-29 12:00:00',
					'updated_at' => '2026-08-29 12:00:00',
				),
				array( '%d', '%s', '%s', '%s' )
			)
			->andReturn( 1 );

		$this->assertTrue( hex_save_page_content( 5, array( 'hero_heading' => 'Welcome' ) ) );
	}

	public function test_save_page_content_updates_an_existing_row() {
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnUsing( fn( $v ) => $v );
		WP_Mock::userFunction( 'current_time' )->with( 'mysql' )->andReturn( '2026-08-29 12:00:00' );

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_var' )->andReturn( '17' ); // Existing row id.
		$wpdb->shouldReceive( 'update' )
			->with(
				'wp_hex_page_content',
				array(
					'content'    => '{"hero_heading":"Updated"}',
					'updated_at' => '2026-08-29 12:00:00',
				),
				array( 'page_id' => 5 ),
				array( '%s', '%s' ),
				array( '%d' )
			)
			->andReturn( 1 );
		$wpdb->shouldNotReceive( 'insert' );

		$this->assertTrue( hex_save_page_content( 5, array( 'hero_heading' => 'Updated' ) ) );
	}

	public function test_save_page_content_returns_false_when_the_query_fails() {
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnUsing( fn( $v ) => $v );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2026-08-29 12:00:00' );

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->andReturn( false );

		$this->assertFalse( hex_save_page_content( 5, array( 'a' => '1' ) ) );
	}

	public function test_save_page_content_sanitizes_before_storing() {
		WP_Mock::userFunction( 'wp_kses_post' )->with( '<script>bad</script>Hi' )->andReturn( 'Hi' );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2026-08-29 12:00:00' );

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );
		$wpdb->shouldReceive( 'insert' )
			->with(
				\Mockery::any(),
				\Mockery::on( fn( $data ) => '{"greeting":"Hi"}' === $data['content'] ),
				\Mockery::any()
			)
			->andReturn( 1 );

		hex_save_page_content( 5, array( 'greeting' => '<script>bad</script>Hi' ) );

		$this->assertConditionsMet();
	}

	public function test_delete_page_content_rejects_a_falsy_page_id() {
		$this->assertFalse( hex_delete_page_content( 0 ) );
	}

	public function test_delete_page_content_deletes_the_row() {
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'delete' )
			->with( 'wp_hex_page_content', array( 'page_id' => 5 ), array( '%d' ) )
			->andReturn( 1 );

		$this->assertTrue( hex_delete_page_content( 5 ) );
	}

	public function test_delete_page_content_on_post_delete_removes_the_matching_row() {
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'delete' )
			->with( 'wp_hex_page_content', array( 'page_id' => 12 ), array( '%d' ) )
			->andReturn( 1 );

		hex_delete_page_content_on_post_delete( 12 );

		$this->assertConditionsMet();
	}

	public function test_save_meta_box_returns_early_without_a_valid_nonce() {
		$_POST = array( 'hex_page_content_nonce' => 'bad-nonce' );
		WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( false );

		// No $wpdb mocked at all -- if the function proceeded past the
		// nonce check, hitting `global $wpdb` on a null value would error.
		hex_save_page_content_meta_box( 5 );

		$_POST = array();
		$this->assertConditionsMet();
	}

	public function test_save_meta_box_sets_an_error_transient_for_invalid_json() {
		$_POST = array(
			'hex_page_content_nonce' => 'good-nonce',
			'hex_page_content_json'  => '{not valid',
		);
		WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->andReturn( true );
		WP_Mock::userFunction( '__' )->andReturnUsing( fn( $text ) => $text );
		WP_Mock::userFunction( 'set_transient' )
			->with( 'hex_page_content_error_5', \Mockery::type( 'string' ), 60 )
			->once()
			->andReturn( true );

		hex_save_page_content_meta_box( 5 );

		$_POST = array();
		$this->assertConditionsMet();
	}

	public function test_save_meta_box_saves_valid_json() {
		$_POST = array(
			'hex_page_content_nonce' => 'good-nonce',
			'hex_page_content_json'  => '{"hero_heading":"Welcome"}',
		);
		WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->andReturn( true );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnUsing( fn( $v ) => $v );
		WP_Mock::userFunction( 'current_time' )->andReturn( '2026-08-29 12:00:00' );
		WP_Mock::userFunction( 'set_transient' )->never();

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->andReturn( 1 );

		hex_save_page_content_meta_box( 5 );

		$_POST = array();
		$this->assertConditionsMet();
	}

	public function test_save_meta_box_does_nothing_without_capability() {
		$_POST = array( 'hex_page_content_nonce' => 'good-nonce' );
		WP_Mock::userFunction( 'wp_verify_nonce' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->andReturn( false );

		hex_save_page_content_meta_box( 5 );

		$_POST = array();
		$this->assertConditionsMet();
	}

	public function test_error_notice_does_nothing_off_the_page_edit_screen() {
		WP_Mock::userFunction( 'get_current_screen' )->andReturn( (object) array( 'post_type' => 'post' ) );

		ob_start();
		hex_page_content_error_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_error_notice_renders_and_clears_a_pending_message() {
		$_GET = array( 'post' => '5' );
		WP_Mock::userFunction( 'get_current_screen' )->andReturn( (object) array( 'post_type' => 'page' ) );
		WP_Mock::userFunction( 'get_transient' )->with( 'hex_page_content_error_5' )->andReturn( 'The JSON was invalid.' );
		WP_Mock::userFunction( 'delete_transient' )->with( 'hex_page_content_error_5' )->once()->andReturn( true );
		WP_Mock::userFunction( 'esc_html' )->andReturnUsing( fn( $v ) => $v );

		ob_start();
		hex_page_content_error_notice();
		$output = ob_get_clean();

		$_GET = array();
		$this->assertStringContainsString( 'notice-error', $output );
		$this->assertStringContainsString( 'The JSON was invalid.', $output );
	}
}
