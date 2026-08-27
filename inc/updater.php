<?php
/**
 * GitHub-based theme self-updater.
 *
 * Uses YahnisElsts/plugin-update-checker (vendored at
 * inc/lib/plugin-update-checker/, loaded via its own
 * Composer-independent standalone bootstrap — not from the
 * top-level vendor/, since generic deploy tooling can silently
 * drop folders literally named "vendor").
 *
 * The GitHub repository, branch, and access token are all
 * admin-configured (Settings → Theme → Updates); nothing is
 * hardcoded here, so no credential ships inside the theme's source.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the configured GitHub repository, as "owner/repo".
 *
 * @return string Empty string when not yet configured.
 */
function hex_get_github_repo() {
	return trim( (string) get_option( 'hex_github_repo', '' ) );
}

/**
 * Get the configured branch to track, defaulting to "main".
 *
 * @return string
 */
function hex_get_github_branch() {
	$branch = trim( (string) get_option( 'hex_github_branch', '' ) );

	return '' !== $branch ? $branch : 'main';
}

/**
 * Get the admin-configured GitHub access token ("Activation Key").
 *
 * @return string Empty string when not set (public repo, or not yet configured).
 */
function hex_get_activation_key() {
	return (string) get_option( 'hex_activation_key', '' );
}

/**
 * Build (and cache, for this request) the update checker instance.
 *
 * @param string|null $repo_override Optional "owner/repo" to use instead of the saved option (used by the Settings "test connection" action).
 * @return \YahnisElsts\PluginUpdateChecker\v5\Vcs\BaseChecker|null Null when no repo is configured or the vendored library is missing.
 */
function hex_get_update_checker( $repo_override = null ) {
	static $checker = null;

	$repo = null !== $repo_override ? trim( $repo_override ) : hex_get_github_repo();

	if ( '' === $repo ) {
		return null;
	}

	if ( null !== $checker && null === $repo_override ) {
		return $checker;
	}

	$lib_file = HEX_THEME_DIR . '/inc/lib/plugin-update-checker/plugin-update-checker.php';
	if ( ! file_exists( $lib_file ) ) {
		return null;
	}

	require_once $lib_file;

	if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
		return null;
	}

	$instance = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/' . $repo . '/',
		HEX_THEME_DIR . '/style.css',
		get_template()
	);

	$instance->setBranch( hex_get_github_branch() );

	$activation_key = hex_get_activation_key();
	if ( '' !== $activation_key ) {
		$instance->setAuthentication( $activation_key );
	}

	$instance->getVcsApi()->enableReleaseAssets();

	if ( null === $repo_override ) {
		$checker = $instance;
	}

	return $instance;
}

/*
 * Instantiate on every request (hooked to 'init' rather than a bare
 * file-scope call, to avoid running consequential code at include
 * time), matching the library's documented usage pattern: this
 * registers its own background update check and Themes-page notice.
 * It's a no-op (returns null immediately) until a repository is
 * configured.
 */
add_action( 'init', 'hex_get_update_checker' );

/**
 * Force an immediate remote check, bypassing the library's ~12h throttle.
 *
 * @return string One-line, human-readable result.
 */
function hex_check_for_theme_update() {
	$checker = hex_get_update_checker();

	if ( ! $checker ) {
		return __( 'Update checker is not available. Save a GitHub repository under Updates first.', 'hex' );
	}

	$update = $checker->checkForUpdates();

	if ( $update ) {
		return sprintf(
			/* translators: %s: New version number. */
			__( 'Update available: version %s', 'hex' ),
			$update->version
		);
	}

	return sprintf(
		/* translators: %s: Current theme version. */
		__( 'You are running the latest version (%s).', 'hex' ),
		wp_get_theme( get_template() )->get( 'Version' )
	);
}

/**
 * Check for, and if available install, a theme update from GitHub.
 *
 * Uses WordPress's own upgrader machinery (Theme_Upgrader +
 * Automatic_Upgrader_Skin) — the same mechanism behind the native
 * "Update Now" link on the Themes screen.
 *
 * @return string One-line, human-readable result.
 */
function hex_perform_theme_update() {
	$checker = hex_get_update_checker();

	if ( ! $checker ) {
		return __( 'Update checker is not available. Save a GitHub repository under Updates first.', 'hex' );
	}

	$update = $checker->checkForUpdates();

	if ( ! $update ) {
		return sprintf(
			/* translators: %s: Current theme version. */
			__( 'You are running the latest version (%s).', 'hex' ),
			wp_get_theme( get_template() )->get( 'Version' )
		);
	}

	if ( ! function_exists( 'wp_update_themes' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}
	wp_update_themes();

	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->upgrade( get_template() );

	if ( is_wp_error( $result ) ) {
		return $result->get_error_message();
	}

	if ( ! $result ) {
		return __( 'Theme update failed. See the WordPress site health log for details.', 'hex' );
	}

	return sprintf(
		/* translators: %s: New version number. */
		__( 'Theme updated to version %s.', 'hex' ),
		$update->version
	);
}
