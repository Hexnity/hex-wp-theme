<?php
/**
 * Tests for the baseline hardening filters in inc/security.php.
 *
 * @package Hex
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers ::hex_remove_version_generator
 * @covers ::hex_remove_pingback_header
 */
class SecurityTest extends TestCase {

	public function test_generator_tag_is_blanked() {
		$this->assertSame( '', hex_remove_version_generator() );
	}

	public function test_pingback_header_is_removed_without_touching_others() {
		$headers = array(
			'X-Pingback' => 'https://example.test/xmlrpc.php',
			'Link'       => '<https://example.test/wp-json/>; rel="https://api.w.org/"',
		);

		$filtered = hex_remove_pingback_header( $headers );

		$this->assertArrayNotHasKey( 'X-Pingback', $filtered );
		$this->assertArrayHasKey( 'Link', $filtered );
	}

	public function test_missing_pingback_header_is_a_no_op() {
		$headers = array( 'Link' => 'value' );

		$this->assertSame( $headers, hex_remove_pingback_header( $headers ) );
	}
}
