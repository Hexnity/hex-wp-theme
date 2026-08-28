<?php
/**
 * Admin-configurable design tokens — a YOOtheme-Pro-scale schema
 * (typography, spacing, colors, buttons, forms, cards, sections,
 * global radius, tables, alerts, badges, icons). Values live in a CSS
 * file inside the active child theme (hex_style_tokens_file_path()),
 * not the database — the static schema below only supplies
 * defaults/labels/types for known keys; any other "--hex-*" custom
 * property found in that file is auto-detected as a "Custom Tokens"
 * field (see hex_merge_style_schema_with_tokens()). See
 * knoladge/child-theme-css-token-architecture.md for the full
 * architecture and assets/css/src/site-theme.css for how Tailwind
 * consumes the resulting CSS custom properties.
 *
 * Loaded unconditionally (not gated behind is_admin()) because the
 * front end needs hex_enqueue_child_theme_tokens() on every request.
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

		$schema[ "{$level}_size_mobile" ]    = array(
			'group'    => 'typography',
			'subgroup' => $upper,
			'type'     => 'length',
			'default'  => $size,
			/* translators: %s: Heading level, e.g. H1. */
			'label'    => sprintf( __( '%s Mobile Size', 'hex' ), $upper ),
		);
		$schema[ "{$level}_size_desktop" ]   = array(
			'group'    => 'typography',
			'subgroup' => $upper,
			'type'     => 'length',
			'default'  => $size,
			/* translators: %s: Heading level, e.g. H1. */
			'label'    => sprintf( __( '%s Desktop Size', 'hex' ), $upper ),
		);
		$schema[ "{$level}_line_height" ]    = array(
			'group'    => 'typography',
			'subgroup' => $upper,
			'type'     => 'number',
			'default'  => $line_height,
			/* translators: %s: Heading level, e.g. H1. */
			'label'    => sprintf( __( '%s Line Height', 'hex' ), $upper ),
		);
		$schema[ "{$level}_letter_spacing" ] = array(
			'group'    => 'typography',
			'subgroup' => $upper,
			'type'     => 'length',
			'default'  => $letter_spacing,
			/* translators: %s: Heading level, e.g. H1. */
			'label'    => sprintf( __( '%s Letter Spacing', 'hex' ), $upper ),
		);
		$schema[ "{$level}_weight" ]         = array(
			'group'    => 'typography',
			'subgroup' => $upper,
			'type'     => 'weight',
			'default'  => $weight,
			/* translators: %s: Heading level, e.g. H1. */
			'label'    => sprintf( __( '%s Font Weight', 'hex' ), $upper ),
		);
		$schema[ "{$level}_margin_bottom" ]  = array(
			'group'    => 'typography',
			'subgroup' => $upper,
			'type'     => 'length',
			'default'  => $margin,
			/* translators: %s: Heading level, e.g. H1. */
			'label'    => sprintf( __( '%s Margin Bottom', 'hex' ), $upper ),
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

		$schema[ "{$style}_size_mobile" ]  = array(
			'group'    => 'typography',
			'subgroup' => $label_name,
			'type'     => 'length',
			'default'  => $size,
			/* translators: %s: Text style name, e.g. Lead. */
			'label'    => sprintf( __( '%s Mobile Text Size', 'hex' ), $label_name ),
		);
		$schema[ "{$style}_size_desktop" ] = array(
			'group'    => 'typography',
			'subgroup' => $label_name,
			'type'     => 'length',
			'default'  => $size,
			/* translators: %s: Text style name, e.g. Lead. */
			'label'    => sprintf( __( '%s Desktop Text Size', 'hex' ), $label_name ),
		);
		$schema[ "{$style}_line_height" ]  = array(
			'group'    => 'typography',
			'subgroup' => $label_name,
			'type'     => 'number',
			'default'  => $line_height,
			/* translators: %s: Text style name, e.g. Lead. */
			'label'    => sprintf( __( '%s Line Height', 'hex' ), $label_name ),
		);
		if ( null !== $letter_spacing ) {
			$schema[ "{$style}_letter_spacing" ] = array(
				'group'    => 'typography',
				'subgroup' => $label_name,
				'type'     => 'length',
				'default'  => $letter_spacing,
				/* translators: %s: Text style name, e.g. Lead. */
				'label'    => sprintf( __( '%s Letter Spacing', 'hex' ), $label_name ),
			);
		}
		if ( null !== $weight ) {
			$schema[ "{$style}_weight" ] = array(
				'group'    => 'typography',
				'subgroup' => $label_name,
				'type'     => 'weight',
				'default'  => $weight,
				/* translators: %s: Text style or button/badge/alert variant name. */
				'label'    => sprintf( __( '%s Font Weight', 'hex' ), $label_name ),
			);
		}
	}

	$schema['body_font_family']    = array(
		'group'    => 'typography',
		'subgroup' => __( 'Fonts & Links', 'hex' ),
		'type'     => 'font',
		'default'  => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
		'label'    => __( 'Body Font Family', 'hex' ),
	);
	$schema['heading_font_family'] = array(
		'group'    => 'typography',
		'subgroup' => __( 'Fonts & Links', 'hex' ),
		'type'     => 'font',
		'default'  => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
		'label'    => __( 'Heading Font Family', 'hex' ),
	);
	$schema['link_color']          = array(
		'group'    => 'typography',
		'subgroup' => __( 'Fonts & Links', 'hex' ),
		'type'     => 'color',
		'default'  => '#2563eb',
		'label'    => __( 'Link Color', 'hex' ),
	);
	$schema['link_hover_color']    = array(
		'group'    => 'typography',
		'subgroup' => __( 'Fonts & Links', 'hex' ),
		'type'     => 'color',
		'default'  => '#1d4ed8',
		'label'    => __( 'Link Hover Color', 'hex' ),
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

	// Fluid typography breakpoints — the shared viewport range every
	// {level}_size_mobile/_desktop pair interpolates across (see
	// hex_get_fluid_size_pairs()/hex_build_fluid_clamp()).
	$schema['fluid_mobile_breakpoint']  = array(
		'group'   => 'global',
		'type'    => 'length',
		'default' => '640px',
		'label'   => __( 'Fluid Typography — Mobile Breakpoint', 'hex' ),
	);
	$schema['fluid_desktop_breakpoint'] = array(
		'group'   => 'global',
		'type'    => 'length',
		'default' => '1600px',
		'label'   => __( 'Fluid Typography — Desktop Breakpoint', 'hex' ),
	);

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
 * The form field name/id for a schema key. No longer a literal
 * wp_options name (values live in a file — see
 * hex_style_tokens_file_path()) but kept as a stable, prefixed
 * identifier for the <input>/<label for> pair.
 *
 * @param string $key Schema key, e.g. 'h1_size'.
 * @return string
 */
function hex_style_field_name( $key ) {
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
 * The path to the active child theme's design-token CSS file. Only
 * meaningful when hex_is_child_theme_active() — this always points at
 * whichever theme is *currently active*, matching the scope Theme
 * Options editing is already gated on.
 *
 * @return string
 */
function hex_style_tokens_file_path() {
	return get_stylesheet_directory() . '/theme-options.css';
}

/**
 * Parse every "--hex-{key}: {value};" custom property declaration out
 * of a CSS string (expected to be a ":root { ... }" block, but this
 * doesn't require that shape — it just finds every matching
 * declaration anywhere in the text, so hand-formatted files with
 * comments/extra whitespace/other rules around the block still work).
 * Pure function: no I/O, fully unit-testable.
 *
 * @param string $css Raw CSS file contents.
 * @return array<string,string> Schema-key (underscored) => raw value (trimmed).
 */
function hex_parse_style_tokens_css( $css ) {
	$tokens = array();

	if ( ! preg_match_all( '/--hex-([a-z0-9-]+)\s*:\s*([^;]+);/', (string) $css, $matches, PREG_SET_ORDER ) ) {
		return $tokens;
	}

	foreach ( $matches as $match ) {
		$key            = str_replace( '-', '_', $match[1] );
		$tokens[ $key ] = trim( $match[2] );
	}

	return $tokens;
}

/**
 * The active child theme's currently-stored design tokens, parsed
 * fresh from its theme-options.css file on every call. Empty when no
 * child theme is active or the file doesn't exist yet (e.g. never
 * saved, and nothing hand-added). Deliberately not cached — this is
 * admin-only UI code (a Theme Options page render, not a hot
 * front-end path), and re-parsing a small file per call is cheap
 * enough that it isn't worth the risk of a stale value surviving a
 * save within the same request.
 *
 * @return array<string,string>
 */
function hex_get_child_theme_tokens() {
	if ( ! hex_is_child_theme_active() ) {
		return array();
	}

	$path = hex_style_tokens_file_path();
	if ( ! file_exists( $path ) ) {
		return array();
	}

	return hex_parse_style_tokens_css( file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local theme file, not a remote/user-supplied path.
}

/**
 * Guess a sanitize/render type for a token found in the file but not
 * in hex_get_style_schema() — i.e. one a child-theme developer added
 * by hand. Deliberately never guesses 'weight' or 'shadow': both are
 * closed enumerations that are syntactically indistinguishable from
 * an arbitrary short string, so a wrong guess could silently coerce a
 * legitimate value into a <select> that can't represent it. Pure
 * function.
 *
 * @param string $value The token's current raw value.
 * @return string One of 'color', 'length', 'number', 'font', 'custom'.
 */
function hex_guess_style_type( $value ) {
	if ( null !== hex_sanitize_style_color( $value ) ) {
		return 'color';
	}

	if ( '0' === $value || preg_match( '/^-?\d+(\.\d+)?(rem|em|px|%)$/', $value ) ) {
		return 'length';
	}

	if ( preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
		return 'number';
	}

	if ( false !== strpos( $value, ',' ) && hex_is_safe_font_value( $value ) ) {
		return 'font';
	}

	return 'custom';
}

/**
 * Merge the static schema with any tokens found in the child theme's
 * file that aren't part of it — each becomes a new field in a
 * 'custom' group, type-guessed via hex_guess_style_type(), labeled by
 * humanizing its key. Pure function.
 *
 * @param array<string,array>  $schema Static schema, e.g. hex_get_style_schema().
 * @param array<string,string> $tokens Parsed file tokens, e.g. hex_get_child_theme_tokens().
 * @return array<string,array>
 */
function hex_merge_style_schema_with_tokens( array $schema, array $tokens ) {
	foreach ( $tokens as $key => $value ) {
		if ( isset( $schema[ $key ] ) ) {
			continue;
		}

		$schema[ $key ] = array(
			'group'   => 'custom',
			'type'    => hex_guess_style_type( $value ),
			'default' => $value,
			'label'   => hex_style_humanize_key( $key ),
		);
	}

	return $schema;
}

/**
 * The effective schema: the static schema plus any auto-detected
 * custom tokens, recomputed on every call — see
 * hex_get_child_theme_tokens() for why this isn't cached.
 *
 * @return array<string,array>
 */
function hex_get_effective_style_schema() {
	return hex_merge_style_schema_with_tokens( hex_get_style_schema(), hex_get_child_theme_tokens() );
}

/**
 * The effective groups for the current request: the static 12 groups,
 * plus a 'Custom Tokens' group appended only if at least one
 * auto-detected field actually needs it (never an empty tab).
 *
 * @return array<string,string>
 */
function hex_get_effective_style_groups() {
	$groups = hex_get_style_groups();

	foreach ( hex_get_effective_style_schema() as $field ) {
		if ( 'custom' === $field['group'] ) {
			$groups['custom'] = __( 'Custom Tokens', 'hex' );
			break;
		}
	}

	return $groups;
}

/**
 * Get one style value: whatever the active child theme's file
 * currently has for this key, or the schema default. For
 * 'shadow'-type fields this returns the raw stored keyword (e.g.
 * "md"), not the resolved CSS — resolution happens only where it's
 * actually needed, in hex_build_style_tokens_css().
 *
 * @param string $key Schema key.
 * @return string
 */
function hex_get_style_value( $key ) {
	$schema = hex_get_effective_style_schema();
	if ( ! isset( $schema[ $key ] ) ) {
		return '';
	}

	$tokens = hex_get_child_theme_tokens();

	return trim( (string) ( isset( $tokens[ $key ] ) ? $tokens[ $key ] : $schema[ $key ]['default'] ) );
}

/**
 * Get every effective style value, keyed by schema key.
 *
 * @return array<string,string>
 */
function hex_get_effective_style_values() {
	$values = array();

	foreach ( array_keys( hex_get_effective_style_schema() ) as $key ) {
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
 * independent check when the file is (re)written in
 * hex_build_style_tokens_css(), in case a value ever got into the
 * file some other way (e.g. hand-edited). Not applied to 'shadow'-type
 * or 'font'-type or 'custom'-type values — those have their own checks.
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
 * Which computed "--hex-{output}" fluid-typography vars are derived
 * from which mobile/desktop schema key pair — see
 * hex_build_fluid_clamp(). The output keys here are NOT schema
 * entries themselves; they only ever appear as an appended,
 * output-only declaration in hex_build_style_tokens_css().
 *
 * @return array<string,array{0:string,1:string}> Output key => array( mobile key, desktop key ).
 */
function hex_get_fluid_size_pairs() {
	$pairs = array();

	foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'body', 'lead', 'large', 'small', 'meta' ) as $key ) {
		$pairs[ "{$key}_size" ] = array( "{$key}_size_mobile", "{$key}_size_desktop" );
	}

	return $pairs;
}

/**
 * Build a fluid CSS clamp() expression that linearly interpolates a
 * property between a mobile value (at the mobile breakpoint) and a
 * desktop value (at the desktop breakpoint), with no media queries —
 * the standard CSS-only fluid-type formula. Pure function.
 *
 * Collapses to a flat value when mobile === desktop (no need for a
 * clamp() at all), and falls back to the desktop value if the two
 * breakpoints are equal (avoids a division by zero in the browser).
 *
 * @param string $mobile     Value at the mobile breakpoint, e.g. '1.75rem'.
 * @param string $desktop    Value at the desktop breakpoint, e.g. '2.5rem'.
 * @param string $mobile_bp  Mobile breakpoint, e.g. '640px'.
 * @param string $desktop_bp Desktop breakpoint, e.g. '1600px'.
 * @return string|null The clamp() expression (or flat value), or null if any input is unsafe.
 */
function hex_build_fluid_clamp( $mobile, $desktop, $mobile_bp, $desktop_bp ) {
	foreach ( array( $mobile, $desktop, $mobile_bp, $desktop_bp ) as $value ) {
		if ( ! hex_is_safe_style_value( $value ) ) {
			return null;
		}
	}

	if ( $mobile === $desktop ) {
		return $mobile;
	}

	if ( $mobile_bp === $desktop_bp ) {
		return $desktop;
	}

	return sprintf(
		'clamp(%1$s, calc(%1$s + (%2$s - %1$s) * ((100vw - %3$s) / (%4$s - %3$s))), %2$s)',
		$mobile,
		$desktop,
		$mobile_bp,
		$desktop_bp
	);
}

/**
 * Build the full contents of the child theme's design-token CSS file
 * from a set of token values — re-validates every value by its
 * schema type before writing (shadow keywords resolved to their real
 * CSS, everything else re-checked with the same safety predicates
 * hex_get_style_value() callers rely on), silently omitting anything
 * that fails so one bad value can't corrupt the rest of the file.
 * Also appends a derived fluid clamp() declaration for every pair in
 * hex_get_fluid_size_pairs() — see hex_build_fluid_clamp(). Pure
 * function: no I/O.
 *
 * @param array<string,string> $tokens Key => value, e.g. hex_get_effective_style_values() merged with submitted overrides.
 * @param array<string,array>  $schema Effective schema, e.g. hex_get_effective_style_schema().
 * @return string Full CSS file contents, including the ":root {}" block.
 */
function hex_build_style_tokens_css( array $tokens, array $schema ) {
	$shadows      = hex_get_shadow_presets();
	$fluid_pairs  = hex_get_fluid_size_pairs();
	$declarations = array();

	foreach ( $tokens as $key => $value ) {
		if ( isset( $fluid_pairs[ $key ] ) ) {
			// Output-only key (e.g. a leftover "h1_size" from before this
			// pair existed) — never written verbatim, always recomputed below.
			continue;
		}

		$type = isset( $schema[ $key ]['type'] ) ? $schema[ $key ]['type'] : 'length';

		if ( 'shadow' === $type ) {
			if ( ! array_key_exists( $value, $shadows ) ) {
				continue;
			}
			$declarations[] = sprintf( "\t%s: %s;", hex_style_css_var_name( $key ), $shadows[ $value ] );
			continue;
		}

		if ( 'font' === $type ) {
			if ( ! hex_is_safe_font_value( $value ) ) {
				continue;
			}
			$declarations[] = sprintf( "\t%s: %s;", hex_style_css_var_name( $key ), $value );
			continue;
		}

		if ( 'custom' === $type ) {
			if ( null === hex_sanitize_style_custom( $value ) ) {
				continue;
			}
			$declarations[] = sprintf( "\t%s: %s;", hex_style_css_var_name( $key ), $value );
			continue;
		}

		if ( ! hex_is_safe_style_value( $value ) ) {
			continue;
		}

		$declarations[] = sprintf( "\t%s: %s;", hex_style_css_var_name( $key ), $value );
	}

	$mobile_bp  = isset( $tokens['fluid_mobile_breakpoint'] ) ? $tokens['fluid_mobile_breakpoint'] : '';
	$desktop_bp = isset( $tokens['fluid_desktop_breakpoint'] ) ? $tokens['fluid_desktop_breakpoint'] : '';

	foreach ( $fluid_pairs as $output_key => $pair ) {
		list( $mobile_key, $desktop_key ) = $pair;

		if ( ! isset( $tokens[ $mobile_key ], $tokens[ $desktop_key ] ) ) {
			continue;
		}

		$clamp = hex_build_fluid_clamp( $tokens[ $mobile_key ], $tokens[ $desktop_key ], $mobile_bp, $desktop_bp );

		if ( null === $clamp ) {
			continue;
		}

		$declarations[] = sprintf( "\t%s: %s;", hex_style_css_var_name( $output_key ), $clamp );
	}

	return sprintf(
		"/**\n * Hexnity WP Theme Options — design tokens.\n *\n * Managed by the Theme Options admin page, which fully regenerates\n * this file on every save. You can also hand-edit it or add new\n * \"--hex-*\" custom properties yourself — anything new here is\n * auto-detected on the next Theme Options page load and appears as\n * an editable field under \"Custom Tokens\". Reference a token from\n * your own CSS as var(--hex-your-key, fallback).\n *\n * See knoladge/child-theme-css-token-architecture.md.\n */\n:root {\n%s\n}\n",
		implode( "\n", $declarations )
	);
}

/**
 * Enqueue the active child theme's design-token CSS file directly, if
 * one has been saved (or hand-created). Nothing is enqueued when no
 * child theme is active or the file doesn't exist yet — the front end
 * still renders correctly in that case, since every var(--hex-key,
 * default) usage in assets/css/src/site-theme.css carries its own
 * independent CSS-level fallback.
 *
 * @return void
 */
function hex_enqueue_child_theme_tokens() {
	if ( ! hex_is_child_theme_active() ) {
		return;
	}

	$path = hex_style_tokens_file_path();
	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_enqueue_style(
		'hex-theme-options-tokens',
		get_stylesheet_directory_uri() . '/theme-options.css',
		array( 'hex-tailwind' ),
		filemtime( $path )
	);
}
add_action( 'wp_enqueue_scripts', 'hex_enqueue_child_theme_tokens' );

/**
 * Sanitize a CSS length value (rem/em/px/%, or bare "0").
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_length( $value ) {
	$value = trim( (string) $value );

	if ( '0' === $value || preg_match( '/^-?\d+(\.\d+)?(rem|em|px|%)$/', $value ) ) {
		return $value;
	}

	return null;
}

/**
 * Sanitize a bare unitless number (e.g. a line-height like "1.5").
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_number( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
		return $value;
	}

	return null;
}

/**
 * Sanitize a hex color value.
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_color( $value ) {
	$sanitized = sanitize_hex_color( $value );

	if ( null !== $sanitized && '' !== $sanitized ) {
		return $sanitized;
	}

	return null;
}

/**
 * Sanitize a font weight (100-900, step 100).
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_weight( $value ) {
	$value = trim( (string) $value );

	if ( in_array( $value, array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ), true ) ) {
		return $value;
	}

	return null;
}

/**
 * Sanitize a shadow preset keyword.
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_shadow( $value ) {
	$value = trim( (string) $value );

	if ( array_key_exists( $value, hex_get_shadow_presets() ) ) {
		return $value;
	}

	return null;
}

/**
 * Sanitize a font-family list.
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_font( $value ) {
	$value = trim( (string) $value );

	if ( hex_is_safe_font_value( $value ) ) {
		return $value;
	}

	return null;
}

/**
 * Sanitize a generic "custom" token value — an auto-detected field
 * whose real shape we don't know, so this is deliberately narrower
 * than a "just about any CSS value" charset: no ":" at all (kills
 * "javascript:"/"data:"/"url(...)" pseudo-protocols outright), no
 * quotes (a font-family list should use the 'font' type instead), and
 * an explicit reject on a "url(" substring even though parens are
 * otherwise allowed (needed for e.g. "rgba(0,0,0,.5)" or
 * "calc(...)"). Capped at 200 characters against pathological input.
 *
 * @param string $value Raw submitted value.
 * @return string|null Sanitized value, or null if invalid.
 */
function hex_sanitize_style_custom( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value || strlen( $value ) > 200 ) {
		return null;
	}

	if ( false !== stripos( $value, 'url(' ) ) {
		return null;
	}

	if ( preg_match( '/^[a-zA-Z0-9 .,+\-\/#%()]+$/', $value ) ) {
		return $value;
	}

	return null;
}

/**
 * Sanitize a set of submitted style-token values against the
 * effective schema — each value is sanitized by its type's callback;
 * anything that fails falls back to its current value, and the
 * field's label is recorded so the caller can report which fields
 * were rejected. Pure function: no I/O, no superglobal access.
 *
 * @param array<string,string> $submitted Raw submitted values, keyed by schema key.
 * @param array<string,array>  $schema    Effective schema, e.g. hex_get_effective_style_schema().
 * @param array<string,string> $current   Current values to fall back to, e.g. hex_get_effective_style_values().
 * @return array{tokens: array<string,string>, rejected: string[]}
 */
function hex_sanitize_submitted_style_tokens( array $submitted, array $schema, array $current ) {
	$tokens   = array();
	$rejected = array();

	foreach ( $schema as $key => $field ) {
		$raw_value = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
		$callback  = hex_style_sanitize_callback_for_type( $field['type'] );
		$sanitized = call_user_func( $callback, $raw_value );

		if ( null === $sanitized ) {
			$sanitized  = isset( $current[ $key ] ) ? $current[ $key ] : $field['default'];
			$rejected[] = $field['label'];
		}

		$tokens[ $key ] = $sanitized;
	}

	return array(
		'tokens'   => $tokens,
		'rejected' => $rejected,
	);
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
		'custom' => 'hex_sanitize_style_custom',
	);

	return isset( $callbacks[ $type ] ) ? $callbacks[ $type ] : 'hex_sanitize_style_length';
}
