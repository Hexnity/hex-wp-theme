<?php
/**
 * Settings API registration for the Updates page (main repo/branch/token)
 * and the Child Theme page (separate child repo/branch/token). The
 * Theme Options page's design tokens (inc/style-settings.php,
 * features/design-system.md) are NOT registered here — they're saved
 * via a dedicated admin-post handler
 * (hex_handle_save_style_options(), inc/admin/handlers.php) into a
 * CSS file in the active child theme, not the Settings API/database;
 * this file only renders those fields
 * (hex_render_style_field()/hex_render_style_group_fields()). All
 * admin-configured — nothing is hardcoded in the theme's source.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the "hex_updates" settings group and its fields.
 *
 * @return void
 */
function hex_register_update_settings() {
	register_setting(
		'hex_updates',
		'hex_github_repo',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hex_sanitize_github_repo',
			'default'           => '',
		)
	);

	register_setting(
		'hex_updates',
		'hex_github_branch',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hex_sanitize_github_branch',
			'default'           => 'main',
		)
	);

	register_setting(
		'hex_updates',
		'hex_activation_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	add_settings_section(
		'hex_updates_github',
		__( 'GitHub Repository', 'hex' ),
		'__return_false',
		'hex-theme-updates'
	);

	add_settings_field(
		'hex_github_repo',
		__( 'Repository', 'hex' ),
		'hex_render_github_repo_field',
		'hex-theme-updates',
		'hex_updates_github'
	);

	add_settings_field(
		'hex_github_branch',
		__( 'Branch', 'hex' ),
		'hex_render_github_branch_field',
		'hex-theme-updates',
		'hex_updates_github'
	);

	add_settings_field(
		'hex_activation_key',
		__( 'Activation Key', 'hex' ),
		'hex_render_activation_key_field',
		'hex-theme-updates',
		'hex_updates_github'
	);
}
add_action( 'admin_init', 'hex_register_update_settings' );

/**
 * Validate the "owner/repo" format; reject and warn rather than
 * silently store something the update checker can't use.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitized value, or '' when invalid.
 */
function hex_sanitize_github_repo( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $value ) ) {
		add_settings_error(
			'hex_github_repo',
			'hex_github_repo_invalid',
			__( 'Enter the repository as owner/repo (e.g. my-org/my-theme).', 'hex' )
		);

		return '';
	}

	return $value;
}

/**
 * Sanitize the tracked branch name.
 *
 * @param string $value Raw submitted value.
 * @return string
 */
function hex_sanitize_github_branch( $value ) {
	return sanitize_text_field( trim( (string) $value ) );
}

/**
 * Render the "Repository" text field.
 *
 * @return void
 */
function hex_render_github_repo_field() {
	printf(
		'<input type="text" id="hex_github_repo" name="hex_github_repo" value="%1$s" class="hex-field max-w-md" placeholder="owner/repo">
		<p class="description">%2$s</p>',
		esc_attr( hex_get_github_repo() ),
		esc_html__( 'The GitHub repository to check for theme updates, as owner/repo.', 'hex' )
	);
}

/**
 * Render the "Branch" text field.
 *
 * @return void
 */
function hex_render_github_branch_field() {
	printf(
		'<input type="text" id="hex_github_branch" name="hex_github_branch" value="%1$s" class="hex-field max-w-md" placeholder="main">',
		esc_attr( hex_get_github_branch() )
	);
}

/**
 * Render the "Activation Key" password field.
 *
 * @return void
 */
function hex_render_activation_key_field() {
	printf(
		'<input type="password" id="hex_activation_key" name="hex_activation_key" value="%1$s" class="hex-field max-w-md" autocomplete="off" placeholder="%2$s">
		<p class="description">%3$s</p>',
		esc_attr( hex_get_activation_key() ),
		esc_attr__( 'Leave blank for a public repository', 'hex' ),
		esc_html__( 'A GitHub personal access token with read access to the repository above. Required only for private repositories.', 'hex' )
	);
}

/**
 * Register the "hex_child_updates" settings group and its fields —
 * a completely separate GitHub repository/branch/token for the
 * generated child theme, independent of the parent theme's own.
 *
 * @return void
 */
function hex_register_child_theme_settings() {
	register_setting(
		'hex_child_updates',
		'hex_child_github_repo',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hex_sanitize_child_github_repo',
			'default'           => '',
		)
	);

	register_setting(
		'hex_child_updates',
		'hex_child_github_branch',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hex_sanitize_github_branch',
			'default'           => 'main',
		)
	);

	register_setting(
		'hex_child_updates',
		'hex_child_activation_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	add_settings_section(
		'hex_child_updates_github',
		__( 'Child Theme GitHub Repository', 'hex' ),
		'__return_false',
		'hex-theme-child-theme'
	);

	add_settings_field(
		'hex_child_github_repo',
		__( 'Repository', 'hex' ),
		'hex_render_child_github_repo_field',
		'hex-theme-child-theme',
		'hex_child_updates_github'
	);

	add_settings_field(
		'hex_child_github_branch',
		__( 'Branch', 'hex' ),
		'hex_render_child_github_branch_field',
		'hex-theme-child-theme',
		'hex_child_updates_github'
	);

	add_settings_field(
		'hex_child_activation_key',
		__( 'Activation Key', 'hex' ),
		'hex_render_child_activation_key_field',
		'hex-theme-child-theme',
		'hex_child_updates_github'
	);
}
add_action( 'admin_init', 'hex_register_child_theme_settings' );

/**
 * Validate the child theme's "owner/repo" format. A separate function
 * from hex_sanitize_github_repo() only so settings errors are recorded
 * under a distinct identifier — the validation itself is identical.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitized value, or '' when invalid.
 */
function hex_sanitize_child_github_repo( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $value ) ) {
		add_settings_error(
			'hex_child_github_repo',
			'hex_child_github_repo_invalid',
			__( 'Enter the repository as owner/repo (e.g. my-org/my-theme).', 'hex' )
		);

		return '';
	}

	return $value;
}

/**
 * Render the child theme "Repository" text field.
 *
 * @return void
 */
function hex_render_child_github_repo_field() {
	printf(
		'<input type="text" id="hex_child_github_repo" name="hex_child_github_repo" value="%1$s" class="hex-field max-w-md" placeholder="owner/repo">
		<p class="description">%2$s</p>',
		esc_attr( hex_get_child_github_repo() ),
		esc_html__( 'The GitHub repository to install and check updates from, as owner/repo. Its style.css must declare this theme as its Template. Independent of the main theme\'s repository.', 'hex' )
	);
}

/**
 * Render the child theme "Branch" text field.
 *
 * @return void
 */
function hex_render_child_github_branch_field() {
	printf(
		'<input type="text" id="hex_child_github_branch" name="hex_child_github_branch" value="%1$s" class="hex-field max-w-md" placeholder="main">',
		esc_attr( hex_get_child_github_branch() )
	);
}

/**
 * Render the child theme "Activation Key" password field.
 *
 * @return void
 */
function hex_render_child_activation_key_field() {
	printf(
		'<input type="password" id="hex_child_activation_key" name="hex_child_activation_key" value="%1$s" class="hex-field max-w-md" autocomplete="off" placeholder="%2$s">
		<p class="description">%3$s</p>',
		esc_attr( hex_get_child_activation_key() ),
		esc_attr__( 'Leave blank for a public repository', 'hex' ),
		esc_html__( 'A GitHub personal access token with read access to the child theme\'s repository above.', 'hex' )
	);
}

/**
 * Generic field renderer for every Theme Options field — a paired
 * color-swatch + text input for 'color', a <select> of 100-900 for
 * 'weight', a <select> of shadow presets for 'shadow', a plain text
 * input for 'font'/'length'/'number'/'custom' (differing only in
 * placeholder hint).
 *
 * @param array $args Field args: 'key' (schema key) and 'type'.
 * @return void
 */
function hex_render_style_field( $args ) {
	$key         = $args['key'];
	$type        = $args['type'];
	$option_name = hex_style_field_name( $key );
	$value       = hex_get_style_value( $key );
	$field_class = 'hex-field';

	switch ( $type ) {
		case 'color':
			printf(
				'<span class="flex items-center gap-2">
					<input type="color" value="%2$s" class="hex-color-swatch" data-hex-color-for="%1$s">
					<input type="text" id="%1$s" name="%1$s" value="%2$s" class="hex-color-text %3$s">
				</span>',
				esc_attr( $option_name ),
				esc_attr( $value ),
				esc_attr( $field_class )
			);
			break;

		case 'weight':
			echo '<select id="' . esc_attr( $option_name ) . '" name="' . esc_attr( $option_name ) . '" class="' . esc_attr( $field_class ) . '">';
			foreach ( array( '300', '400', '500', '600', '700', '800', '900' ) as $weight ) {
				printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $weight ), selected( $value, $weight, false ) );
			}
			echo '</select>';
			break;

		case 'shadow':
			$labels = array(
				'none' => __( 'None', 'hex' ),
				'sm'   => __( 'Small', 'hex' ),
				'md'   => __( 'Medium', 'hex' ),
				'lg'   => __( 'Large', 'hex' ),
				'xl'   => __( 'Extra Large', 'hex' ),
			);
			echo '<select id="' . esc_attr( $option_name ) . '" name="' . esc_attr( $option_name ) . '" class="' . esc_attr( $field_class ) . '">';
			foreach ( $labels as $preset => $label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $preset ), selected( $value, $preset, false ), esc_html( $label ) );
			}
			echo '</select>';
			break;

		case 'font':
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" list="hex-google-fonts-list" placeholder="e.g. Georgia, serif or a Google Font name">',
				esc_attr( $option_name ),
				esc_attr( $value ),
				esc_attr( $field_class )
			);
			break;

		default: // length, number, custom.
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" placeholder="%4$s">',
				esc_attr( $option_name ),
				esc_attr( $value ),
				esc_attr( $field_class ),
				esc_attr( hex_style_field_placeholder_for_type( $type ) )
			);
	}
}

/**
 * The placeholder hint for a plain-text-input field type.
 *
 * @param string $type Field type.
 * @return string
 */
function hex_style_field_placeholder_for_type( $type ) {
	$placeholders = array(
		'number' => '1.5',
		'custom' => 'e.g. rgba(0,0,0,.5)',
	);

	return isset( $placeholders[ $type ] ) ? $placeholders[ $type ] : 'e.g. 1.5rem';
}

/**
 * Render one label+input pair per field (the body of both branches of
 * hex_render_style_group_fields() below).
 *
 * @param array<string,array> $fields Schema key => field, already filtered to one group/subgroup.
 * @return void
 */
function hex_render_style_fields( array $fields ) {
	foreach ( $fields as $key => $field ) {
		?>
		<div class="hex-style-field">
			<label for="<?php echo esc_attr( hex_style_field_name( $key ) ); ?>" class="hex-label">
				<?php echo esc_html( $field['label'] ); ?>
			</label>
			<?php
			hex_render_style_field(
				array(
					'key'  => $key,
					'type' => $field['type'],
				)
			);
			?>
		</div>
		<?php
	}
}

/**
 * Render every field belonging to one Theme Options schema group
 * (used inside the tabbed layout in inc/admin/page-theme-options.php).
 * Fields are bucketed by their schema entry's optional 'subgroup'
 * label (see hex_get_style_schema()'s docblock): a group with two or
 * more distinct subgroups renders as a collapsible accordion — one
 * `<details>` per subgroup (only the first open by default) — so
 * families like H1/H2/.../Body/Lead/... or the four button variants
 * don't all run together in one long list. A group with zero or one
 * subgroup (most of them — colors, spacing, forms, cards, global,
 * tables, icons, auto-detected custom tokens) renders exactly as
 * before: one flat two-column grid, no accordion chrome.
 *
 * @param string $group Schema group key, e.g. 'typography'.
 * @return void
 */
function hex_render_style_group_fields( $group ) {
	$buckets = array();

	foreach ( hex_get_effective_style_schema() as $key => $field ) {
		if ( $field['group'] !== $group ) {
			continue;
		}

		$subgroup                     = isset( $field['subgroup'] ) ? $field['subgroup'] : '';
		$buckets[ $subgroup ][ $key ] = $field;
	}

	if ( count( $buckets ) <= 1 ) {
		echo '<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-6 sm:grid-cols-2">';
		foreach ( $buckets as $fields ) {
			hex_render_style_fields( $fields );
		}
		echo '</div>';
		return;
	}

	$is_first = true;

	foreach ( $buckets as $subgroup => $fields ) {
		?>
		<details class="hex-style-accordion group border-b border-gray-800 last:border-b-0" <?php echo $is_first ? 'open' : ''; ?>>
			<summary class="flex cursor-pointer select-none items-center justify-between gap-2 px-6 py-3 text-sm font-semibold text-white! marker:hidden [&::-webkit-details-marker]:hidden">
				<span><?php echo esc_html( $subgroup ); ?></span>
				<svg class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-150 group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
					<polyline points="5 7.5 10 12.5 15 7.5" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</summary>
			<div class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 pb-6 pt-1 sm:grid-cols-2">
				<?php hex_render_style_fields( $fields ); ?>
			</div>
		</details>
		<?php
		$is_first = false;
	}
}

/**
 * Render the "Google Fonts" repeater (one embed link/URL per row, or
 * the whole snippet from fonts.google.com pasted into a single row)
 * at the top of the Typography panel, plus a chip list of the
 * families it currently resolves to — so every 'font'-type field's
 * searchable picker (see hex_render_google_fonts_datalist()) has
 * something to offer beyond whatever the admin types by hand.
 *
 * The repeater rows are a client-side convenience only
 * (assets/js/admin.js keeps them synced into a single hidden field on
 * every change, live-updating the shared datalist and the chip list
 * as it goes) — the field actually submitted is still the same
 * newline-joined "hex_google_fonts_urls" string
 * hex_sanitize_google_fonts_urls() has always expected, so storage
 * and sanitizing are unchanged.
 *
 * @return void
 */
function hex_render_google_fonts_field() {
	$families = hex_get_google_font_families();
	$urls     = hex_get_google_fonts_urls();

	if ( ! $urls ) {
		$urls = array( '' );
	}
	?>
	<div class="border-b border-gray-800 px-6 py-5" data-hex-google-fonts>
		<label class="hex-label"><?php esc_html_e( 'Google Fonts', 'hex' ); ?></label>
		<p class="mb-2 text-sm text-gray-400!">
			<?php esc_html_e( 'Paste a Google Fonts embed link from fonts.google.com into each row below (the whole snippet is fine, or just the stylesheet URL) — every family found is added to the font pickers below as you type, no API key needed.', 'hex' ); ?>
		</p>

		<div class="space-y-2" data-hex-google-fonts-rows>
			<?php foreach ( $urls as $url ) : ?>
				<div class="hex-google-fonts-row flex items-center gap-2">
					<input
						type="text"
						class="hex-field font-mono text-xs"
						value="<?php echo esc_attr( $url ); ?>"
						placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&amp;display=swap"
						data-hex-google-fonts-url
					>
					<button type="button" class="hex-btn hex-btn-secondary shrink-0 px-3!" data-hex-remove-font-row aria-label="<?php esc_attr_e( 'Remove font', 'hex' ); ?>">&times;</button>
				</div>
			<?php endforeach; ?>
		</div>

		<button type="button" class="hex-btn hex-btn-secondary mt-3" data-hex-add-font-row>
			+ <?php esc_html_e( 'Add Font', 'hex' ); ?>
		</button>

		<textarea id="hex_google_fonts_urls" name="hex_google_fonts_urls" class="hidden" data-hex-google-fonts-hidden><?php echo esc_textarea( get_option( 'hex_google_fonts_urls', '' ) ); ?></textarea>

		<div class="mt-3 flex flex-wrap gap-1.5" data-hex-google-fonts-chips>
			<?php foreach ( $families as $family ) : ?>
				<span class="rounded-full bg-indigo-500/10 px-2.5 py-1 text-xs font-medium text-indigo-300!"><?php echo esc_html( $family ); ?></span>
			<?php endforeach; ?>
		</div>
		<p class="mt-2 text-xs text-gray-500! <?php echo $families ? 'hidden' : ''; ?>" data-hex-google-fonts-empty>
			<?php esc_html_e( 'No Google Fonts added yet — the font fields below just take whatever you type, e.g. a system font stack.', 'hex' ); ?>
		</p>
	</div>
	<?php
}
