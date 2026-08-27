<?php
/**
 * Settings API registration for the Updates page (main repo/branch/token),
 * the Child Theme page (separate child repo/branch/token), and the
 * Theme Options page (the full design-token schema — see
 * inc/style-settings.php and features/design-system.md). All
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
 * Register the "hex_style_options" settings group: every token in
 * hex_get_style_schema() (~146 fields). Fields are rendered directly
 * by inc/admin/page-theme-options.php (a tabbed grid layout, not the
 * default WP form-table), so this only needs the Settings API's
 * save/nonce/sanitize machinery — no add_settings_section()/
 * add_settings_field() calls.
 *
 * @return void
 */
function hex_register_style_settings() {
	foreach ( hex_get_style_schema() as $key => $field ) {
		register_setting(
			'hex_style_options',
			hex_style_option_name( $key ),
			array(
				'type'              => 'string',
				'sanitize_callback' => hex_style_sanitize_callback_for_type( $field['type'] ),
				'default'           => $field['default'],
			)
		);
	}

	register_setting(
		'hex_style_options',
		'hex_google_fonts_urls',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'hex_sanitize_google_fonts_urls',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'hex_register_style_settings' );

/**
 * Generic field renderer for every Theme Options field — a paired
 * color-swatch + text input for 'color', a <select> of 100-900 for
 * 'weight', a <select> of shadow presets for 'shadow', a plain text
 * input for 'font'/'length'/'number' (the last two just differ in
 * placeholder hint).
 *
 * @param array $args Field args: 'key' (schema key) and 'type'.
 * @return void
 */
function hex_render_style_field( $args ) {
	$key         = $args['key'];
	$type        = $args['type'];
	$option_name = hex_style_option_name( $key );
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

		default: // length, number.
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="%3$s" placeholder="%4$s">',
				esc_attr( $option_name ),
				esc_attr( $value ),
				esc_attr( $field_class ),
				esc_attr( 'number' === $type ? '1.5' : 'e.g. 1.5rem' )
			);
	}
}

/**
 * Render every field belonging to one Theme Options schema group, as
 * label+input pairs (used inside the tabbed grid layout in
 * inc/admin/page-theme-options.php).
 *
 * @param string $group Schema group key, e.g. 'typography'.
 * @return void
 */
function hex_render_style_group_fields( $group ) {
	foreach ( hex_get_style_schema() as $key => $field ) {
		if ( $field['group'] !== $group ) {
			continue;
		}
		?>
		<div class="hex-style-field">
			<label for="<?php echo esc_attr( hex_style_option_name( $key ) ); ?>" class="hex-label">
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
 * Render the "Google Fonts" textarea (one embed link/URL per line, or
 * the whole snippet from fonts.google.com) at the top of the
 * Typography panel, plus a chip list of the families it currently
 * resolves to — so every 'font'-type field's searchable picker
 * (see hex_render_google_fonts_datalist()) has something to offer
 * beyond whatever the admin types by hand.
 *
 * @return void
 */
function hex_render_google_fonts_field() {
	$families = hex_get_google_font_families();
	?>
	<div class="border-b border-gray-800 px-6 py-5">
		<label for="hex_google_fonts_urls" class="hex-label"><?php esc_html_e( 'Google Fonts', 'hex' ); ?></label>
		<p class="mb-2 text-sm text-gray-400!">
			<?php esc_html_e( 'Paste one or more Google Fonts embed links from fonts.google.com below (the whole snippet is fine, or just the stylesheet URL) — every family found is added to the font pickers below, no API key needed.', 'hex' ); ?>
		</p>
		<textarea
			id="hex_google_fonts_urls"
			name="hex_google_fonts_urls"
			rows="3"
			class="hex-field font-mono text-xs"
			placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&amp;display=swap"
		><?php echo esc_textarea( get_option( 'hex_google_fonts_urls', '' ) ); ?></textarea>
		<?php if ( $families ) : ?>
			<div class="mt-3 flex flex-wrap gap-1.5">
				<?php foreach ( $families as $family ) : ?>
					<span class="rounded-full bg-indigo-500/10 px-2.5 py-1 text-xs font-medium text-indigo-300!"><?php echo esc_html( $family ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="mt-3 text-xs text-gray-500!"><?php esc_html_e( 'No Google Fonts added yet — the font fields below just take whatever you type, e.g. a system font stack.', 'hex' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
