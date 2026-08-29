<?php
/**
 * Google Fonts integration: the "Font Library" — four Theme Options
 * fields (Heading/Body/Accent/Mono), each a <select> built from a
 * curated, hardcoded list of common Google Fonts
 * (hex_get_common_google_fonts()). No typing, no embed link, no API
 * key or request — the theme derives everything (the stored
 * font-family value and the Google Fonts stylesheet URL) from the
 * chosen font's own entry in that list.
 *
 * This theme previously also had a free-text "paste a Google Fonts
 * embed link" picker feeding body_font_family/heading_font_family —
 * removed per explicit user request ("keep only that option and
 * remove all other font options") once the Font Library covered the
 * same need with a simpler, mistake-proof UI.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the same two preconnect resource hints Google's own embed
 * snippet uses, but only when the Font Library has at least one font
 * selected — matches fonts.google.com's own generated markup
 * (googleapis.com plain, gstatic.com with crossorigin).
 *
 * @param array  $urls          Existing resource-hint URLs for this relation type.
 * @param string $relation_type 'dns-prefetch', 'preconnect', etc.
 * @return array
 */
function hex_google_fonts_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type ) {
		return $urls;
	}

	if ( ! hex_get_font_library_selection() ) {
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
 * The "Font Library" — a curated, hardcoded list of common Google
 * Fonts, keyed by a stable slug. Each Theme Options "Font Library"
 * field (Heading/Body/Accent/Mono — see hex_get_style_schema()) is a
 * <select> built from this list rather than a free-text field: the
 * admin picks a font, and this theme derives both the stored
 * CSS-variable value ('stack', a ready-to-use font-family list) and
 * the Google Fonts stylesheet URL needed to actually load it.
 *
 * 'stack' is what gets stored as the field's value (and therefore
 * what --hex-font-{slot} resolves to) — it must satisfy
 * hex_is_safe_font_value()'s charset (letters, spaces, commas,
 * hyphens, apostrophes only), which every entry here does. 'weights'
 * is the Google Fonts CSS2 API's "wght@" list for that family, used
 * only when building the enqueue URL in hex_enqueue_font_library().
 *
 * @return array<string,array{name:string,category:string,stack:string,weights:string}>
 */
function hex_get_common_google_fonts() {
	return array(
		// Sans Serif.
		'inter'             => array(
			'name'     => 'Inter',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Inter', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'roboto'            => array(
			'name'     => 'Roboto',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Roboto', sans-serif",
			'weights'  => '400;500;700',
		),
		'open-sans'         => array(
			'name'     => 'Open Sans',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Open Sans', sans-serif",
			'weights'  => '400;600;700',
		),
		'lato'              => array(
			'name'     => 'Lato',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Lato', sans-serif",
			'weights'  => '400;700',
		),
		'montserrat'        => array(
			'name'     => 'Montserrat',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Montserrat', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'poppins'           => array(
			'name'     => 'Poppins',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Poppins', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'source-sans-3'     => array(
			'name'     => 'Source Sans 3',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Source Sans 3', sans-serif",
			'weights'  => '400;600;700',
		),
		'nunito'            => array(
			'name'     => 'Nunito',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Nunito', sans-serif",
			'weights'  => '400;600;700',
		),
		'raleway'           => array(
			'name'     => 'Raleway',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Raleway', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'work-sans'         => array(
			'name'     => 'Work Sans',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Work Sans', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'karla'             => array(
			'name'     => 'Karla',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Karla', sans-serif",
			'weights'  => '400;500;700',
		),
		'dm-sans'           => array(
			'name'     => 'DM Sans',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'DM Sans', sans-serif",
			'weights'  => '400;500;700',
		),
		'manrope'           => array(
			'name'     => 'Manrope',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Manrope', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'space-grotesk'     => array(
			'name'     => 'Space Grotesk',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Space Grotesk', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'outfit'            => array(
			'name'     => 'Outfit',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Outfit', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'plus-jakarta-sans' => array(
			'name'     => 'Plus Jakarta Sans',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Plus Jakarta Sans', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'ibm-plex-sans'     => array(
			'name'     => 'IBM Plex Sans',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'IBM Plex Sans', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'rubik'             => array(
			'name'     => 'Rubik',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Rubik', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'noto-sans'         => array(
			'name'     => 'Noto Sans',
			'category' => __( 'Sans Serif', 'hex' ),
			'stack'    => "'Noto Sans', sans-serif",
			'weights'  => '400;500;700',
		),
		// Serif.
		'playfair-display'  => array(
			'name'     => 'Playfair Display',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Playfair Display', serif",
			'weights'  => '400;600;700',
		),
		'merriweather'      => array(
			'name'     => 'Merriweather',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Merriweather', serif",
			'weights'  => '400;700',
		),
		'lora'              => array(
			'name'     => 'Lora',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Lora', serif",
			'weights'  => '400;600;700',
		),
		'pt-serif'          => array(
			'name'     => 'PT Serif',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'PT Serif', serif",
			'weights'  => '400;700',
		),
		'noto-serif'        => array(
			'name'     => 'Noto Serif',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Noto Serif', serif",
			'weights'  => '400;700',
		),
		'ibm-plex-serif'    => array(
			'name'     => 'IBM Plex Serif',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'IBM Plex Serif', serif",
			'weights'  => '400;500;600;700',
		),
		'crimson-text'      => array(
			'name'     => 'Crimson Text',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Crimson Text', serif",
			'weights'  => '400;600;700',
		),
		'eb-garamond'       => array(
			'name'     => 'EB Garamond',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'EB Garamond', serif",
			'weights'  => '400;600;700',
		),
		'libre-baskerville' => array(
			'name'     => 'Libre Baskerville',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Libre Baskerville', serif",
			'weights'  => '400;700',
		),
		'cormorant'         => array(
			'name'     => 'Cormorant',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Cormorant', serif",
			'weights'  => '400;600;700',
		),
		'bitter'            => array(
			'name'     => 'Bitter',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Bitter', serif",
			'weights'  => '400;600;700',
		),
		'instrument-serif'  => array(
			'name'     => 'Instrument Serif',
			'category' => __( 'Serif', 'hex' ),
			'stack'    => "'Instrument Serif', serif",
			'weights'  => '400',
		),
		// Monospace.
		'jetbrains-mono'    => array(
			'name'     => 'JetBrains Mono',
			'category' => __( 'Monospace', 'hex' ),
			'stack'    => "'JetBrains Mono', monospace",
			'weights'  => '400;500;700',
		),
		'fira-code'         => array(
			'name'     => 'Fira Code',
			'category' => __( 'Monospace', 'hex' ),
			'stack'    => "'Fira Code', monospace",
			'weights'  => '400;500;600;700',
		),
		'roboto-mono'       => array(
			'name'     => 'Roboto Mono',
			'category' => __( 'Monospace', 'hex' ),
			'stack'    => "'Roboto Mono', monospace",
			'weights'  => '400;500;700',
		),
		'source-code-pro'   => array(
			'name'     => 'Source Code Pro',
			'category' => __( 'Monospace', 'hex' ),
			'stack'    => "'Source Code Pro', monospace",
			'weights'  => '400;500;600;700',
		),
		'ibm-plex-mono'     => array(
			'name'     => 'IBM Plex Mono',
			'category' => __( 'Monospace', 'hex' ),
			'stack'    => "'IBM Plex Mono', monospace",
			'weights'  => '400;500;600;700',
		),
		'space-mono'        => array(
			'name'     => 'Space Mono',
			'category' => __( 'Monospace', 'hex' ),
			'stack'    => "'Space Mono', monospace",
			'weights'  => '400;700',
		),
		// Display.
		'bebas-neue'        => array(
			'name'     => 'Bebas Neue',
			'category' => __( 'Display', 'hex' ),
			'stack'    => "'Bebas Neue', sans-serif",
			'weights'  => '400',
		),
		'oswald'            => array(
			'name'     => 'Oswald',
			'category' => __( 'Display', 'hex' ),
			'stack'    => "'Oswald', sans-serif",
			'weights'  => '400;500;600;700',
		),
		'anton'             => array(
			'name'     => 'Anton',
			'category' => __( 'Display', 'hex' ),
			'stack'    => "'Anton', sans-serif",
			'weights'  => '400',
		),
		'abril-fatface'     => array(
			'name'     => 'Abril Fatface',
			'category' => __( 'Display', 'hex' ),
			'stack'    => "'Abril Fatface', serif",
			'weights'  => '400',
		),
		'josefin-sans'      => array(
			'name'     => 'Josefin Sans',
			'category' => __( 'Display', 'hex' ),
			'stack'    => "'Josefin Sans', sans-serif",
			'weights'  => '400;500;600;700',
		),
	);
}

/**
 * Find a Font Library entry by its exact 'stack' value (what's stored
 * in a "font_{slot}" Theme Options field).
 *
 * @param string $stack A font-family stack, e.g. "'Inter', sans-serif".
 * @return array{name:string,category:string,stack:string,weights:string}|null
 */
function hex_get_google_font_by_stack( $stack ) {
	foreach ( hex_get_common_google_fonts() as $font ) {
		if ( $font['stack'] === $stack ) {
			return $font;
		}
	}

	return null;
}

/**
 * The leading font-family name out of a stack string, e.g.
 * "'Instrument Serif', Georgia, 'Times New Roman', serif" ->
 * "Instrument Serif". Handles both a quoted first entry and a bare
 * one (no surrounding quotes needed for a single-word name).
 *
 * @param string $stack A font-family list.
 * @return string
 */
function hex_font_stack_primary_name( $stack ) {
	$first = trim( explode( ',', (string) $stack )[0] );

	return trim( $first, " \t\n\r\0\x0B'\"" );
}

/**
 * Find a Font Library entry by its primary font name, case-insensitive,
 * regardless of what fallback fonts follow it in the stack.
 *
 * Exists for one reason: a hand-edited theme-options.css can carry a
 * longer fallback chain than this list's own canonical stack for the
 * same font (e.g. a saved "'Inter', ui-sans-serif, system-ui,
 * -apple-system, sans-serif" instead of this list's own "'Inter',
 * sans-serif") — hex_get_google_font_by_stack()'s exact match would
 * find nothing, and the admin dropdown would silently show
 * "— Use Default —" even though a real value is set and actively
 * rendering on the front end. This is the looser lookup
 * hex_render_style_field()'s 'google_font' case uses just to decide
 * which <option> to mark selected; sanitizing and enqueuing still use
 * the strict hex_get_google_font_by_stack() exact match.
 *
 * @param string $stack A font-family list; only its first entry is used.
 * @return array{name:string,category:string,stack:string,weights:string}|null
 */
function hex_get_google_font_by_name( $stack ) {
	$name = hex_font_stack_primary_name( $stack );

	if ( '' === $name ) {
		return null;
	}

	foreach ( hex_get_common_google_fonts() as $font ) {
		if ( 0 === strcasecmp( $font['name'], $name ) ) {
			return $font;
		}
	}

	return null;
}

/**
 * Which Font Library <select> option a Theme Options field's current
 * value should show as selected. An exact stack match wins outright;
 * failing that, a name-only match (see hex_get_google_font_by_name())
 * is used instead, so a hand-edited theme-options.css value with a
 * longer fallback chain than this list's own canonical stack still
 * shows the right font selected rather than silently falling back to
 * "— Use Default —". Anything matching neither is returned unchanged
 * (an unrecognized font, or ''), which correctly selects no option.
 *
 * Pure function: no I/O — used by hex_render_style_field()'s
 * 'google_font' case, factored out purely so it's directly testable.
 *
 * @param string $value The field's current value, e.g. from hex_get_style_value().
 * @return string A known stack to compare options against, or the original value if nothing matched.
 */
function hex_resolve_google_font_field_selection( $value ) {
	if ( '' === $value || hex_get_google_font_by_stack( $value ) ) {
		return $value;
	}

	$matched = hex_get_google_font_by_name( $value );

	return $matched ? $matched['stack'] : $value;
}

/**
 * The theme's four "Font Library" slots (font_heading/_body/_accent/_mono)
 * currently resolved to a real Font Library entry — i.e. non-empty and
 * matching a known 'stack'. Used both to decide whether preconnect
 * hints are needed and to build the enqueue URL below.
 *
 * @return array<string,array{name:string,category:string,stack:string,weights:string}> Keyed by slot.
 */
function hex_get_font_library_selection() {
	$selected = array();

	foreach ( array( 'font_heading', 'font_body', 'font_accent', 'font_mono' ) as $slot ) {
		$stack = hex_get_style_value( $slot );

		if ( '' === $stack ) {
			continue;
		}

		$font = hex_get_google_font_by_stack( $stack );

		if ( null !== $font ) {
			$selected[ $slot ] = $font;
		}
	}

	return $selected;
}

/**
 * Build the single combined Google Fonts CSS2 URL covering every
 * distinct family in a Font Library selection — one HTTP request even
 * if all four slots are filled, fewer if any families repeat (e.g.
 * Heading and Accent both set to the same font). Pure function: no
 * I/O, so it's testable without mocking WordPress or the filesystem —
 * see hex_enqueue_font_library() for the impure wrapper that actually
 * reads the current selection and enqueues this.
 *
 * @param array<string,array{name:string,category:string,stack:string,weights:string}> $selected As returned by hex_get_font_library_selection().
 * @return string The full stylesheet URL, or '' when $selected is empty.
 */
function hex_build_font_library_url( array $selected ) {
	if ( ! $selected ) {
		return '';
	}

	$families = array();
	foreach ( $selected as $font ) {
		// Same family selected in more than one slot -- de-duplicate by name.
		$families[ $font['name'] ] = $font['weights'];
	}

	$params = array();
	foreach ( $families as $name => $weights ) {
		$params[] = 'family=' . str_replace( ' ', '+', $name ) . ':wght@' . $weights;
	}

	return 'https://fonts.googleapis.com/css2?' . implode( '&', $params ) . '&display=swap';
}

/**
 * Enqueue the combined Google Fonts stylesheet (hex_build_font_library_url())
 * for whatever is currently selected across the four Font Library
 * slots. Does nothing when none are selected.
 *
 * @return void
 */
function hex_enqueue_font_library() {
	$url = hex_build_font_library_url( hex_get_font_library_selection() );

	if ( '' === $url ) {
		return;
	}

	// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- an external Google-hosted URL isn't ours to version; Google handles its own cache-busting.
	wp_enqueue_style( 'hex-font-library', $url, array(), null );
}
add_action( 'wp_enqueue_scripts', 'hex_enqueue_font_library' );
