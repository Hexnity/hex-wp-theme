<?php
/**
 * Admin-post handlers for the Updates and Child Theme pages' manual actions.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle "Check for Updates Now".
 *
 * @return void
 */
function hex_handle_check_updates() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'hex' ), 403 );
	}

	check_admin_referer( 'hex_check_updates' );

	set_transient( 'hex_updates_log', hex_check_for_theme_update(), 60 );

	wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-updates' ) );
	exit;
}
add_action( 'admin_post_hex_check_updates', 'hex_handle_check_updates' );

/**
 * Handle "Check & Update Now".
 *
 * @return void
 */
function hex_handle_perform_update() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'hex' ), 403 );
	}

	check_admin_referer( 'hex_perform_update' );

	set_transient( 'hex_updates_log', hex_perform_theme_update(), 60 );

	wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-updates' ) );
	exit;
}
add_action( 'admin_post_hex_perform_update', 'hex_handle_perform_update' );

/**
 * Handle "Fetch & Install Child Theme" — installs from the saved
 * repository/branch (hex_get_child_github_repo() / hex_get_child_github_branch()),
 * after validating it's actually a child theme of this theme.
 *
 * @return void
 */
function hex_handle_install_child_theme() {
	if ( ! current_user_can( 'edit_themes' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'hex' ), 403 );
	}

	check_admin_referer( 'hex_install_child_theme' );

	$repo = hex_get_child_github_repo();
	if ( '' === $repo ) {
		set_transient( 'hex_child_theme_log', __( 'Save a child theme GitHub repository first.', 'hex' ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-child-theme' ) );
		exit;
	}

	$result = hex_install_child_theme_from_repo( $repo, hex_get_child_github_branch() );

	if ( is_wp_error( $result ) ) {
		set_transient( 'hex_child_theme_log', $result->get_error_message(), 60 );
	} else {
		set_transient(
			'hex_child_theme_log',
			sprintf(
				/* translators: %s: Child theme slug. */
				__( 'Child theme installed: %s. Activate it under Appearance > Themes.', 'hex' ),
				$result
			),
			60
		);
	}

	wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-child-theme' ) );
	exit;
}
add_action( 'admin_post_hex_install_child_theme', 'hex_handle_install_child_theme' );

/**
 * Handle "Check for Child Theme Updates Now".
 *
 * @return void
 */
function hex_handle_check_child_updates() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'hex' ), 403 );
	}

	check_admin_referer( 'hex_check_child_updates' );

	set_transient( 'hex_child_theme_log', hex_check_for_child_theme_update(), 60 );

	wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-child-theme' ) );
	exit;
}
add_action( 'admin_post_hex_check_child_updates', 'hex_handle_check_child_updates' );

/**
 * Handle "Check & Update Child Theme Now".
 *
 * @return void
 */
function hex_handle_perform_child_update() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'hex' ), 403 );
	}

	check_admin_referer( 'hex_perform_child_update' );

	set_transient( 'hex_child_theme_log', hex_perform_child_theme_update(), 60 );

	wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-child-theme' ) );
	exit;
}
add_action( 'admin_post_hex_perform_child_update', 'hex_handle_perform_child_update' );

/**
 * Handle "Save Style Settings" — sanitizes every submitted design
 * token against the effective schema (hex_get_effective_style_schema(),
 * which includes any auto-detected custom tokens), rewrites the
 * active child theme's theme-options.css file with the full result,
 * and saves the (still DB-backed) Google Fonts field in the same
 * request since its repeater lives in this same form. See
 * knoladge/child-theme-css-token-architecture.md.
 *
 * @return void
 */
function hex_handle_save_style_options() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'hex' ), 403 );
	}

	check_admin_referer( 'hex_save_style_options' );

	if ( ! hex_is_child_theme_active() ) {
		set_transient( 'hex_theme_options_log', __( 'Style settings require an active child theme.', 'hex' ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-theme-options' ) );
		exit;
	}

	$schema  = hex_get_effective_style_schema();
	$current = hex_get_effective_style_values();

	$submitted = array();
	foreach ( array_keys( $schema ) as $key ) {
		$field_name        = hex_style_field_name( $key );
		$submitted[ $key ] = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above via check_admin_referer().
	}

	$result = hex_sanitize_submitted_style_tokens( $submitted, $schema, $current );

	$google_fonts_raw = isset( $_POST['hex_google_fonts_urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hex_google_fonts_urls'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above via check_admin_referer().
	update_option( 'hex_google_fonts_urls', hex_sanitize_google_fonts_urls( $google_fonts_raw ) );

	$css = hex_build_style_tokens_css( $result['tokens'], $schema );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	global $wp_filesystem;

	if ( ! WP_Filesystem() || ! $wp_filesystem->put_contents( hex_style_tokens_file_path(), $css, FS_CHMOD_FILE ) ) {
		set_transient( 'hex_theme_options_log', __( 'Could not write theme-options.css — check that the active child theme\'s directory is writable.', 'hex' ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-theme-options' ) );
		exit;
	}

	if ( $result['rejected'] ) {
		set_transient(
			'hex_theme_options_log',
			sprintf(
				/* translators: %s: comma-separated list of rejected field labels. */
				__( 'Saved. Kept previous values for: %s.', 'hex' ),
				implode( ', ', $result['rejected'] )
			),
			60
		);
	} else {
		set_transient( 'hex_theme_options_log', __( 'Style settings saved.', 'hex' ), 60 );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=hex-theme-theme-options' ) );
	exit;
}
add_action( 'admin_post_hex_save_style_options', 'hex_handle_save_style_options' );
