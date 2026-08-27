<?php
/**
 * Admin-configurable design tokens — a YOOtheme-Pro-scale schema
 * (typography, spacing, colors, buttons, forms, cards, sections,
 * global radius, tables, alerts, badges, icons). The single schema
 * drives Settings API registration, sanitization, field rendering,
 * and the runtime CSS custom properties printed on the front end —
 * see assets/css/src/site-theme.css for how Tailwind consumes them,
 * and features/design-system.md for the full reference on which
 * class to use for what.
 *
 * Loaded unconditionally (not gated behind is_admin()) because the
 * front end needs hex_render_style_css_vars() on every request.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turn a snake_case schema-key fragment into a human label, e.g.
 * "hover_background" -> "Hover Background". Used by the loop-built
 * parts of the schema to avoid writing ~100 labels by hand.
 *
 * @param string $key Snake_case fragment.
 * @return string
 */
function hex_style_humanize_key( $key ) {
	return ucwords( str_replace( '_', ' ', $key ) );
}

/**
 * The fixed set of box-shadow presets a "shadow" field can select
 * from. Admins pick a keyword (e.g. "md"); the real box-shadow CSS
 * behind it is fixed here, not admin-editable — box-shadow syntax
 * (parens, commas, slashes) can't safely round-trip through a plain
 * text field the way a length or color can.
 *
 * @return array<string,string> Keyword => CSS box-shadow value.
 */
function hex_get_shadow_presets() {
	return array(
		'none' => 'none',
		'sm'   => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
		'md'   => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
		'lg'   => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
		'xl'   => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
	);
}

/**
 * The master schema: every style token this theme exposes — ~146
 * fields across 12 groups. Built with loops for the repetitive
 * families (heading levels, button/alert/badge variants, spacing-like
 * scales) rather than ~146 individual hand-written array literals, so
 * adding a new family member is a one-line change.
 *
 * Each entry's option name is "hex_style_{$key}"; its CSS custom
 * property name is "--hex-{$key with underscores as hyphens}".
 *
 * Field types: 'length' (CSS length incl. optional leading "-" and
 * bare "0"), 'number' (bare unitless decimal, e.g. line-height),
 * 'color' (hex color), 'weight' (100-900 step 100), 'shadow' (a
 * hex_get_shadow_presets() keyword), 'font' (a safe font-family list).
 *
 * @return array<string,array{group:string,type:string,default:string,label:string}>
 */
function hex_get_style_schema() {
	$schema = array();

	// Typography: heading levels — size, line-height, letter-spacing, weight, margin-bottom.
	$heading_defaults = array(
		'h1' => array( '2.5rem', '1.2', '-0.02em', '700', '1.5rem' ),
		'h2' => array( '2rem', '1.25', '-0.01em', '700', '1.25rem' ),
		'h3' => array( '1.75rem', '1.3', '-0.01em', '600', '1rem' ),
		'h4' => array( '1.5rem', '1.35', '0', '600', '1rem' ),
		'h5' => array( '1.25rem', '1.4', '0', '600', '0.75rem' ),
		'h6' => array( '1rem', '1.4', '0.02em', '600', '0.75rem' ),
	);
	foreach ( $heading_defaults as $level => $defaults ) {
		list( $size, $line_height, $letter_spacing, $weight, $margin ) = $defaults;
		$upper = strtoupper( $level );

		$schema[ "{$level}_size" ]           = array(
			'group'   => 'typography',
			'type'    => 'length',
			'default' => $size,
			/* translators: %s: Heading level, e.g. H1. */
			'label'   => sprintf( __( '%s Size', 'hex' ), $upper ),
		);
		$schema[ "{$level}_line_height" ]    = array(
			'group'   => 'typography',
			'type'    => 'number',
			'default' => $line_height,
			/* translators: %s: Heading level, e.g. H1. */
			'label'   => sprintf( __( '%s Line Height', 'hex' ), $upper ),
		);
		$schema[ "{$level}_letter_spacing" ] = array(
			'group'   => 'typography',
			'type'    => 'length',
			'default' => $letter_spacing,
			/* translators: %s: Heading level, e.g. H1. */
			'label'   => sprintf( __( '%s Letter Spacing', 'hex' ), $upper ),
		);
		$schema[ "{$level}_weight" ]         = array(
			'group'   => 'typography',
			'type'    => 'weight',
			'default' => $weight,
			/* translators: %s: Heading level, e.g. H1. */
			'label'   => sprintf( __( '%s Font Weight', 'hex' ), $upper ),
		);
		$schema[ "{$level}_margin_bottom" ]  = array(
			'group'   => 'typography',
			'type'    => 'length',
			'default' => $margin,
			/* translators: %s: Heading level, e.g. H1. */
			'label'   => sprintf( __( '%s Margin Bottom', 'hex' ), $upper ),
		);
	}

	// Typography: body-level text styles.
	$text_style_defaults = array(
		'body'  => array( '1rem', '1.6', null, null ),
		'lead'  => array( '1.25rem', '1.6', null, null ),
		'large' => array( '1.125rem', '1.6', null, null ),
		'small' => array( '0.875rem', '1.5', null, null ),
		'meta'  => array( '0.8125rem', '1.4', '0.03em', '600' ),
	);
	foreach ( $text_style_defaults as $style => $defaults ) {
		list( $size, $line_height, $letter_spacing, $weight ) = $defaults;
		$label_name = ucfirst( $style );

		$schema[ "{$style}_size" ]        = array(
			'group'   => 'typography',
			'type'    => 'length',
			'default' => $size,
			/* translators: %s: Text style name, e.g. Lead. */
			'label'   => sprintf( __( '%s Text Size', 'hex' ), $label_name ),
		);
		$schema[ "{$style}_line_height" ] = array(
			'group'   => 'typography',
			'type'    => 'number',
			'default' => $line_height,
			/* translators: %s: Text style name, e.g. Lead. */
			'label'   => sprintf( __( '%s Line Height', 'hex' ), $label_name ),
		);
		if ( null !== $letter_spacing ) {
			$schema[ "{$style}_letter_spacing" ] = array(
				'group'   => 'typography',
				'type'    => 'length',
				'default' => $letter_spacing,
				/* translators: %s: Text style name, e.g. Lead. */
				'label'   => sprintf( __( '%s Letter Spacing', 'hex' ), $label_name ),
			);
		}
		if ( null !== $weight ) {
			$schema[ "{$style}_weight" ] = array(
				'group'   => 'typography',
				'type'    => 'weight',
				'default' => $weight,
				/* translators: %s: Text style or button/badge/alert variant name. */
				'label'   => sprintf( __( '%s Font Weight', 'hex' ), $label_name ),
			);
		}
	}

	$schema['body_font_family']    = array(
		'group'   => 'typography',
		'type'    => 'font',
		'default' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
		'label'   => __( 'Body Font Family', 'hex' ),
	);
	$schema['heading_font_family'] = array(
		'group'   => 'typography',
		'type'    => 'font',
		'default' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
		'label'   => __( 'Heading Font Family', 'hex' ),
	);
	$schema['link_color']          = array(
		'group'   => 'typography',
		'type'    => 'color',
		'default' => '#2563eb',
		'label'   => __( 'Link Color', 'hex' ),
	);
	$schema['link_hover_color']    = array(
		'group'   => 'typography',
		'type'    => 'color',
		'default' => '#1d4ed8',
		'label'   => __( 'Link Hover Color', 'hex' ),
	);

	// Spacing.
	$spacing_defaults = array(
		'xs'  => '0.5rem',
		'sm'  => '1rem',
		'md'  => '1.5rem',
		'lg'  => '2rem',
		'xl'  => '3rem',
		'2xl' => '4rem',
	);
	foreach ( $spacing_defaults as $key => $default ) {
		$schema[ "spacing_{$key}" ] = array(
			'group'   => 'spacing',
			'type'    => 'length',
			'default' => $default,
			/* translators: %s: Spacing scale key, e.g. XS. */
			'label'   => sprintf( __( 'Spacing — %s', 'hex' ), strtoupper( $key ) ),
		);
	}

	// Colors: the full palette.
	$color_defaults = array(
		'primary'            => '#2563eb',
		'secondary'          => '#475569',
		'tertiary'           => '#7c3aed',
		'accent'             => '#16a34a',
		'success'            => '#16a34a',
		'warning'            => '#d97706',
		'danger'             => '#dc2626',
		'info'               => '#0ea5e9',
		'muted'              => '#f1f5f9',
		'emphasis'           => '#111827',
		'body_background'    => '#ffffff',
		'body_text'          => '#1f2937',
		'inverse_background' => '#111827',
		'inverse_text'       => '#f9fafb',
		'border'             => '#e5e7eb',
	);
	foreach ( $color_defaults as $key => $default ) {
		$schema[ "color_{$key}" ] = array(
			'group'   => 'colors',
			'type'    => 'color',
			'default' => $default,
			'label'   => hex_style_humanize_key( $key ),
		);
	}

	// Buttons: variants (default/primary/secondary/danger each get background+color+hover pair).
	$button_variants = array(
		'default'   => array( '#e5e7eb', '#111827', '#d1d5db', '#111827' ),
		'primary'   => array( '#2563eb', '#ffffff', '#1d4ed8', '#ffffff' ),
		'secondary' => array( '#475569', '#ffffff', '#334155', '#ffffff' ),
		'danger'    => array( '#dc2626', '#ffffff', '#b91c1c', '#ffffff' ),
	);
	foreach ( $button_variants as $variant => $defaults ) {
		list( $bg, $color, $hover_bg, $hover_color ) = $defaults;
		$label_name                                  = ucfirst( $variant );

		$schema[ "button_{$variant}_background" ]       = array(
			'group'   => 'buttons',
			'type'    => 'color',
			'default' => $bg,
			/* translators: %s: Button variant name, e.g. Primary. */
			'label'   => sprintf( __( '%s Background', 'hex' ), $label_name ),
		);
		$schema[ "button_{$variant}_color" ]            = array(
			'group'   => 'buttons',
			'type'    => 'color',
			'default' => $color,
			/* translators: %s: Button variant name, e.g. Primary. */
			'label'   => sprintf( __( '%s Text', 'hex' ), $label_name ),
		);
		$schema[ "button_{$variant}_hover_background" ] = array(
			'group'   => 'buttons',
			'type'    => 'color',
			'default' => $hover_bg,
			/* translators: %s: Button variant name, e.g. Primary. */
			'label'   => sprintf( __( '%s Hover Background', 'hex' ), $label_name ),
		);
		$schema[ "button_{$variant}_hover_color" ]      = array(
			'group'   => 'buttons',
			'type'    => 'color',
			'default' => $hover_color,
			/* translators: %s: Button variant name, e.g. Primary. */
			'label'   => sprintf( __( '%s Hover Text', 'hex' ), $label_name ),
		);
	}
	$schema['button_text_color']       = array(
		'group'   => 'buttons',
		'type'    => 'color',
		'default' => '#2563eb',
		'label'   => __( 'Text Button Color', 'hex' ),
	);
	$schema['button_text_hover_color'] = array(
		'group'   => 'buttons',
		'type'    => 'color',
		'default' => '#1d4ed8',
		'label'   => __( 'Text Button Hover Color', 'hex' ),
	);
	$schema['button_radius']           = array(
		'group'   => 'buttons',
		'type'    => 'length',
		'default' => '0.375rem',
		'label'   => __( 'Button Radius', 'hex' ),
	);
	$schema['button_font_weight']      = array(
		'group'   => 'buttons',
		'type'    => 'weight',
		'default' => '600',
		'label'   => __( 'Button Font Weight', 'hex' ),
	);
	$button_padding_defaults           = array(
		'padding_x'    => '1.25rem',
		'padding_y'    => '0.625rem',
		'padding_x_sm' => '0.75rem',
		'padding_y_sm' => '0.375rem',
		'padding_x_lg' => '1.75rem',
		'padding_y_lg' => '0.875rem',
	);
	foreach ( $button_padding_defaults as $key => $default ) {
		$schema[ "button_{$key}" ] = array(
			'group'   => 'buttons',
			'type'    => 'length',
			'default' => $default,
			/* translators: %s: Humanized field name, e.g. Padding X. */
			'label'   => sprintf( __( 'Button %s', 'hex' ), hex_style_humanize_key( $key ) ),
		);
	}

	// Forms.
	$form_color_defaults = array(
		'background'         => '#ffffff',
		'border_color'       => '#d1d5db',
		'focus_border_color' => '#2563eb',
		'placeholder_color'  => '#9ca3af',
		'label_color'        => '#374151',
	);
	foreach ( $form_color_defaults as $key => $default ) {
		$schema[ "form_{$key}" ] = array(
			'group'   => 'forms',
			'type'    => 'color',
			'default' => $default,
			/* translators: %s: Humanized field name, e.g. Border Color. */
			'label'   => sprintf( __( 'Form %s', 'hex' ), hex_style_humanize_key( $key ) ),
		);
	}
	$schema['form_radius']    = array(
		'group'   => 'forms',
		'type'    => 'length',
		'default' => '0.375rem',
		'label'   => __( 'Form Field Radius', 'hex' ),
	);
	$schema['form_padding_x'] = array(
		'group'   => 'forms',
		'type'    => 'length',
		'default' => '0.75rem',
		'label'   => __( 'Form Field Padding X', 'hex' ),
	);
	$schema['form_padding_y'] = array(
		'group'   => 'forms',
		'type'    => 'length',
		'default' => '0.5rem',
		'label'   => __( 'Form Field Padding Y', 'hex' ),
	);

	// Cards.
	$schema['card_background']   = array(
		'group'   => 'cards',
		'type'    => 'color',
		'default' => '#ffffff',
		'label'   => __( 'Card Background', 'hex' ),
	);
	$schema['card_border_color'] = array(
		'group'   => 'cards',
		'type'    => 'color',
		'default' => '#e5e7eb',
		'label'   => __( 'Card Border Color', 'hex' ),
	);
	$schema['card_radius']       = array(
		'group'   => 'cards',
		'type'    => 'length',
		'default' => '0.5rem',
		'label'   => __( 'Card Radius', 'hex' ),
	);
	$schema['card_padding']      = array(
		'group'   => 'cards',
		'type'    => 'length',
		'default' => '1.5rem',
		'label'   => __( 'Card Padding', 'hex' ),
	);
	$schema['card_shadow']       = array(
		'group'   => 'cards',
		'type'    => 'shadow',
		'default' => 'sm',
		'label'   => __( 'Card Shadow', 'hex' ),
	);
	$schema['card_shadow_hover'] = array(
		'group'   => 'cards',
		'type'    => 'shadow',
		'default' => 'md',
		'label'   => __( 'Card Hover Shadow', 'hex' ),
	);

	// Sections (page-section backgrounds + vertical padding scale).
	$section_bg_defaults = array(
		'default'   => '#ffffff',
		'muted'     => '#f8fafc',
		'primary'   => '#2563eb',
		'secondary' => '#475569',
	);
	foreach ( $section_bg_defaults as $key => $default ) {
		$schema[ "section_background_{$key}" ] = array(
			'group'   => 'sections',
			'type'    => 'color',
			'default' => $default,
			/* translators: %s: Section background variant name, e.g. Muted. */
			'label'   => sprintf( __( 'Section Background — %s', 'hex' ), ucfirst( $key ) ),
		);
	}
	$section_padding_defaults = array(
		'sm' => '2rem',
		'md' => '3rem',
		'lg' => '4rem',
		'xl' => '6rem',
	);
	foreach ( $section_padding_defaults as $key => $default ) {
		$schema[ "section_padding_{$key}" ] = array(
			'group'   => 'sections',
			'type'    => 'length',
			'default' => $default,
			/* translators: %s: Section padding scale key, e.g. SM. */
			'label'   => sprintf( __( 'Section Padding — %s', 'hex' ), strtoupper( $key ) ),
		);
	}

	// Global radius scale — deliberately named to match (and override)
	// Tailwind's own built-in rounded-sm/md/lg scale, so this one
	// setting affects every existing and future use of those utilities
	// site-wide, the way YOOtheme's own global radius setting does.
	$radius_defaults = array(
		'sm' => '0.25rem',
		'md' => '0.5rem',
		'lg' => '1rem',
	);
	foreach ( $radius_defaults as $key => $default ) {
		$schema[ "radius_{$key}" ] = array(
			'group'   => 'global',
			'type'    => 'length',
			'default' => $default,
			/* translators: %s: Radius scale key, e.g. SM. */
			'label'   => sprintf( __( 'Radius — %s', 'hex' ), strtoupper( $key ) ),
		);
	}

	// Tables.
	$table_defaults = array(
		'border_color'      => '#e5e7eb',
		'stripe_background' => '#f9fafb',
		'header_background' => '#f3f4f6',
		'header_color'      => '#111827',
	);
	foreach ( $table_defaults as $key => $default ) {
		$schema[ "table_{$key}" ] = array(
			'group'   => 'tables',
			'type'    => 'color',
			'default' => $default,
			/* translators: %s: Humanized field name, e.g. Header Background. */
			'label'   => sprintf( __( 'Table %s', 'hex' ), hex_style_humanize_key( $key ) ),
		);
	}

	// Alerts (state messages).
	$alert_states = array(
		'primary' => array( '#eff6ff', '#1e3a8a' ),
		'success' => array( '#f0fdf4', '#166534' ),
		'warning' => array( '#fffbeb', '#92400e' ),
		'danger'  => array( '#fef2f2', '#991b1b' ),
	);
	foreach ( $alert_states as $state => $defaults ) {
		list( $bg, $color )                    = $defaults;
		$schema[ "alert_{$state}_background" ] = array(
			'group'   => 'alerts',
			'type'    => 'color',
			'default' => $bg,
			/* translators: %s: Alert state name, e.g. Success. */
			'label'   => sprintf( __( '%s Alert Background', 'hex' ), ucfirst( $state ) ),
		);
		$schema[ "alert_{$state}_color" ]      = array(
			'group'   => 'alerts',
			'type'    => 'color',
			'default' => $color,
			/* translators: %s: Alert state name, e.g. Success. */
			'label'   => sprintf( __( '%s Alert Text', 'hex' ), ucfirst( $state ) ),
		);
	}

	// Badges.
	$badge_variants = array(
		'default' => array( '#f3f4f6', '#111827' ),
		'primary' => array( '#dbeafe', '#1e40af' ),
		'success' => array( '#dcfce7', '#166534' ),
		'warning' => array( '#fef3c7', '#92400e' ),
		'danger'  => array( '#fee2e2', '#991b1b' ),
	);
	foreach ( $badge_variants as $variant => $defaults ) {
		list( $bg, $color )                      = $defaults;
		$schema[ "badge_{$variant}_background" ] = array(
			'group'   => 'badges',
			'type'    => 'color',
			'default' => $bg,
			/* translators: %s: Badge variant name, e.g. Success. */
			'label'   => sprintf( __( '%s Badge Background', 'hex' ), ucfirst( $variant ) ),
		);
		$schema[ "badge_{$variant}_color" ]      = array(
			'group'   => 'badges',
			'type'    => 'color',
			'default' => $color,
			/* translators: %s: Badge variant name, e.g. Success. */
			'label'   => sprintf( __( '%s Badge Text', 'hex' ), ucfirst( $variant ) ),
		);
	}

	// Icons.
	$schema['icon_color']       = array(
		'group'   => 'icons',
		'type'    => 'color',
		'default' => '#475569',
		'label'   => __( 'Icon Color', 'hex' ),
	);
	$schema['icon_hover_color'] = array(
		'group'   => 'icons',
		'type'    => 'color',
		'default' => '#2563eb',
		'label'   => __( 'Icon Hover Color', 'hex' ),
	);
	$icon_size_defaults         = array(
		'sm' => '1rem',
		'md' => '1.5rem',
		'lg' => '2rem',
	);
	foreach ( $icon_size_defaults as $key => $default ) {
		$schema[ "icon_size_{$key}" ] = array(
			'group'   => 'icons',
			'type'    => 'length',
			'default' => $default,
			/* translators: %s: Icon size scale key, e.g. SM. */
			'label'   => sprintf( __( 'Icon Size — %s', 'hex' ), strtoupper( $key ) ),
		);
	}

	return $schema;
}

/**
 * The distinct groups in the schema, in display order, with labels.
 *
 * @return array<string,string>
 */
function hex_get_style_groups() {
	return array(
		'typography' => __( 'Typography', 'hex' ),
		'spacing'    => __( 'Spacing', 'hex' ),
		'colors'     => __( 'Colors', 'hex' ),
		'buttons'    => __( 'Buttons', 'hex' ),
		'forms'      => __( 'Forms', 'hex' ),
		'cards'      => __( 'Cards', 'hex' ),
		'sections'   => __( 'Sections', 'hex' ),
		'global'     => __( 'Global', 'hex' ),
		'tables'     => __( 'Tables', 'hex' ),
		'alerts'     => __( 'Alerts', 'hex' ),
		'badges'     => __( 'Badges', 'hex' ),
		'icons'      => __( 'Icons', 'hex' ),
	);
}

/**
 * The option name for a schema key.
 *
 * @param string $key Schema key, e.g. 'h1_size'.
 * @return string
 */
function hex_style_option_name( $key ) {
	return 'hex_style_' . $key;
}

/**
 * The CSS custom property name for a schema key.
 *
 * @param string $key Schema key, e.g. 'h1_size'.
 * @return string e.g. '--hex-h1-size'.
 */
function hex_style_css_var_name( $key ) {
	return '--hex-' . str_replace( '_', '-', $key );
}

/**
 * Get one style value: the saved option, or the schema default. For
 * 'shadow'-type fields this returns the raw stored keyword (e.g.
 * "md"), not the resolved CSS — resolution happens only where it's
 * actually needed, in hex_render_style_css_vars().
 *
 * @param string $key Schema key.
 * @return string
 */
function hex_get_style_value( $key ) {
	$schema = hex_get_style_schema();
	if ( ! isset( $schema[ $key ] ) ) {
		return '';
	}

	return trim( (string) get_option( hex_style_option_name( $key ), $schema[ $key ]['default'] ) );
}

/**
 * Get every style value, keyed by schema key.
 *
 * @return array<string,string>
 */
function hex_get_all_style_values() {
	$values = array();

	foreach ( array_keys( hex_get_style_schema() ) as $key ) {
		$values[ $key ] = hex_get_style_value( $key );
	}

	return $values;
}

/**
 * Whether a value is safe to print verbatim inside an inline <style>
 * block as a CSS custom property value — permits hex colors, CSS
 * lengths (incl. a leading "-" for letter-spacing), and plain
 * numbers/weights, rejects anything that could break out of the
 * declaration (quotes, parens, semicolons, url(), etc.). Sanitize
 * callbacks already enforce this at save time; this is a second,
 * independent check at output time in case an option was ever set
 * some other way. Not applied to 'shadow'-type or 'font'-type values
 * — those are handled separately in hex_render_style_css_vars().
 *
 * @param string $value Value to check.
 * @return bool
 */
function hex_is_safe_style_value( $value ) {
	return '' !== $value && (bool) preg_match( '/^-?[#a-zA-Z0-9.%]+$/', $value );
}

/**
 * Whether a font-family value is safe to print verbatim — letters,
 * digits, spaces, commas, hyphens, and single quotes only (the
 * standard safe charset for a CSS font-family list).
 *
 * @param string $value Value to check.
 * @return bool
 */
function hex_is_safe_font_value( $value ) {
	return '' !== $value && (bool) preg_match( "/^[a-zA-Z0-9 ,\\-']+$/", $value );
}

/**
 * Print the current style values as CSS custom properties on :root.
 *
 * Runs on every front-end request regardless of whether a child theme
 * is active — the *values* always apply; only *editing* them (the
 * Theme Options admin page) is gated on an active child theme.
 *
 * @return void
 */
function hex_render_style_css_vars() {
	$schema       = hex_get_style_schema();
	$shadows      = hex_get_shadow_presets();
	$declarations = array();

	foreach ( hex_get_all_style_values() as $key => $value ) {
		$type = isset( $schema[ $key ]['type'] ) ? $schema[ $key ]['type'] : 'length';

		if ( 'shadow' === $type ) {
			if ( ! array_key_exists( $value, $shadows ) ) {
				continue;
			}
			$declarations[] = sprintf( '%s:%s;', hex_style_css_var_name( $key ), $shadows[ $value ] );
			continue;
		}

		if ( 'font' === $type ) {
			if ( ! hex_is_safe_font_value( $value ) ) {
				continue;
			}
			$declarations[] = sprintf( '%s:%s;', hex_style_css_var_name( $key ), $value );
			continue;
		}

		if ( ! hex_is_safe_style_value( $value ) ) {
			continue;
		}

		$declarations[] = sprintf( '%s:%s;', hex_style_css_var_name( $key ), $value );
	}

	if ( empty( $declarations ) ) {
		return;
	}

	printf( '<style id="hex-style-vars">:root{%s}</style>' . "\n", implode( '', $declarations ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value passed a type-appropriate safety check above.
}
add_action( 'wp_head', 'hex_render_style_css_vars' );

/**
 * Resolve a "sanitize_option_hex_style_{key}" filter's own option name
 * back to its schema key.
 *
 * @param string $option_name Full option name, e.g. 'hex_style_h1_size'.
 * @return string Schema key, e.g. 'h1_size'.
 */
function hex_style_key_from_option_name( $option_name ) {
	return preg_replace( '/^hex_style_/', '', $option_name );
}

/**
 * Get the value to fall back to when a submitted value fails
 * validation — whatever is currently saved (or the schema default),
 * so a bad submission never overwrites a good value with something
 * empty or invalid. Reads the option name from the current
 * "sanitize_option_{name}" filter context.
 *
 * @return string
 */
function hex_style_revert_from_filter() {
	$key = hex_style_key_from_option_name( str_replace( 'sanitize_option_', '', current_filter() ) );

	return hex_get_style_value( $key );
}

/**
 * Sanitize a CSS length value (rem/em/px/%, or bare "0"). Rejects and
 * keeps whatever was previously saved rather than ever storing an
 * empty/invalid value.
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_style_length( $value ) {
	$value = trim( (string) $value );

	if ( '0' === $value || preg_match( '/^-?\d+(\.\d+)?(rem|em|px|%)$/', $value ) ) {
		return $value;
	}

	add_settings_error(
		'hex_style_options',
		'hex_style_invalid_length',
		__( 'Enter a valid CSS length (e.g. 1.5rem, -0.02em, 24px, 100%, or 0) — the previous value was kept.', 'hex' )
	);

	return hex_style_revert_from_filter();
}

/**
 * Sanitize a bare unitless number (e.g. a line-height like "1.5").
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_style_number( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
		return $value;
	}

	add_settings_error(
		'hex_style_options',
		'hex_style_invalid_number',
		__( 'Enter a plain number (e.g. 1.5) — the previous value was kept.', 'hex' )
	);

	return hex_style_revert_from_filter();
}

/**
 * Sanitize a hex color value.
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_style_color( $value ) {
	$sanitized = sanitize_hex_color( $value );

	if ( null !== $sanitized && '' !== $sanitized ) {
		return $sanitized;
	}

	add_settings_error(
		'hex_style_options',
		'hex_style_invalid_color',
		__( 'Enter a valid hex color (e.g. #2563eb) — the previous value was kept.', 'hex' )
	);

	return hex_style_revert_from_filter();
}

/**
 * Sanitize a font weight (100-900, step 100).
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_style_weight( $value ) {
	$value = trim( (string) $value );

	if ( in_array( $value, array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ), true ) ) {
		return $value;
	}

	add_settings_error(
		'hex_style_options',
		'hex_style_invalid_weight',
		__( 'Choose a font weight between 100 and 900 — the previous value was kept.', 'hex' )
	);

	return hex_style_revert_from_filter();
}

/**
 * Sanitize a shadow preset keyword.
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_style_shadow( $value ) {
	$value = trim( (string) $value );

	if ( array_key_exists( $value, hex_get_shadow_presets() ) ) {
		return $value;
	}

	add_settings_error(
		'hex_style_options',
		'hex_style_invalid_shadow',
		__( 'Choose a valid shadow preset — the previous value was kept.', 'hex' )
	);

	return hex_style_revert_from_filter();
}

/**
 * Sanitize a font-family list.
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_style_font( $value ) {
	$value = trim( (string) $value );

	if ( hex_is_safe_font_value( $value ) ) {
		return $value;
	}

	add_settings_error(
		'hex_style_options',
		'hex_style_invalid_font',
		__( 'Enter a valid font-family list (letters, spaces, commas, hyphens, and quotes only) — the previous value was kept.', 'hex' )
	);

	return hex_style_revert_from_filter();
}

/**
 * The sanitize callback function name for a schema field type.
 *
 * @param string $type Field type.
 * @return string Callback function name.
 */
function hex_style_sanitize_callback_for_type( $type ) {
	$callbacks = array(
		'length' => 'hex_sanitize_style_length',
		'number' => 'hex_sanitize_style_number',
		'color'  => 'hex_sanitize_style_color',
		'weight' => 'hex_sanitize_style_weight',
		'shadow' => 'hex_sanitize_style_shadow',
		'font'   => 'hex_sanitize_style_font',
	);

	return isset( $callbacks[ $type ] ) ? $callbacks[ $type ] : 'hex_sanitize_style_length';
}
