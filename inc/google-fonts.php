<?php
/**
 * Google Fonts integration: the admin pastes one or more Google Fonts
 * embed links (exactly what fonts.google.com's own "Get embed code"
 * panel gives you) into a Theme Options field; this theme parses the
 * family names out of the stylesheet URL(s) and feeds them into the
 * Theme Options font-family fields' searchable picker, and enqueues
 * the stylesheet(s) on the front end. No Google Fonts API key or API
 * request is ever used — everything is derived from the URL(s) the
 * admin already has.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the raw "Google Fonts" textarea submission. The admin may
 * paste a bare URL, several URLs, or the whole embed snippet
 * (including the two <link rel="preconnect"> tags) — only genuine
 * fonts.googleapis.com/css2 stylesheet URLs are extracted and kept;
 * everything else (the preconnect tags, stray text) is discarded.
 *
 * @param string $value Raw submitted value.
 * @return string One sanitized URL per line.
 */
function hex_sanitize_google_fonts_urls( $value ) {
	preg_match_all( '#https://fonts\.googleapis\.com/css2\?[^\s"\'<>]+#', (string) $value, $matches );

	$urls = array_unique( $matches[0] );

	return implode( "\n", array_map( 'esc_url_raw', $urls ) );
}

/**
 * The admin-configured Google Fonts stylesheet URLs.
 *
 * @return string[] One entry per stored URL.
 */
function hex_get_google_fonts_urls() {
	$raw = get_option( 'hex_google_fonts_urls', '' );

	if ( '' === $raw ) {
		return array();
	}

	return array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
}

/**
 * Parse the distinct font-family names out of the configured URLs'
 * `family` query parameters, e.g. "Inter:ital,wght@0,400" -> "Inter".
 *
 * @return string[] Unique family names, in the order first seen.
 */
function hex_get_google_font_families() {
	$families = array();

	foreach ( hex_get_google_fonts_urls() as $url ) {
		$query = wp_parse_url( $url, PHP_URL_QUERY );

		if ( ! $query || ! preg_match_all( '/family=([^&]+)/', $query, $matches ) ) {
			continue;
		}

		foreach ( $matches[1] as $raw_family ) {
			$decoded = urldecode( str_replace( '+', ' ', $raw_family ) );
			$name    = trim( explode( ':', $decoded )[0] );

			if ( '' !== $name ) {
				$families[ $name ] = $name;
			}
		}
	}

	return array_values( $families );
}

/**
 * Enqueue every configured Google Fonts stylesheet on the front end.
 *
 * @return void
 */
function hex_enqueue_google_fonts() {
	foreach ( hex_get_google_fonts_urls() as $index => $url ) {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- an external Google-hosted URL isn't ours to version; Google handles its own cache-busting.
		wp_enqueue_style( 'hex-google-fonts-' . $index, $url, array(), null );
	}
}
add_action( 'wp_enqueue_scripts', 'hex_enqueue_google_fonts' );

/**
 * Add the same two preconnect resource hints Google's own embed
 * snippet uses, but only when at least one Google Fonts URL is
 * configured — matches fonts.google.com's own generated markup
 * (googleapis.com plain, gstatic.com with crossorigin).
 *
 * @param array  $urls          Existing resource-hint URLs for this relation type.
 * @param string $relation_type 'dns-prefetch', 'preconnect', etc.
 * @return array
 */
function hex_google_fonts_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type || empty( hex_get_google_fonts_urls() ) ) {
		return $urls;
	}

	$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
	$urls[] = array(
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => 'anonymous',
	);

	return $urls;
}
add_filter( 'wp_resource_hints', 'hex_google_fonts_resource_hints', 10, 2 );

/**
 * Render the shared <datalist> of Google Font family names that every
 * 'font'-type Theme Options field's searchable input points to via
 * its list="" attribute.
 *
 * @return void
 */
function hex_render_google_fonts_datalist() {
	?>
	<datalist id="hex-google-fonts-list">
		<?php foreach ( hex_get_google_font_families() as $family ) : ?>
			<option value="<?php echo esc_attr( $family ); ?>"></option>
		<?php endforeach; ?>
	</datalist>
	<?php
}
