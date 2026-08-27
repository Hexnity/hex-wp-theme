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
