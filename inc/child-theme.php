<?php
/**
 * Fetching, validating, and installing a child theme from GitHub —
 * NOT auto-generated. The admin enters a repository (same
 * hex_child_github_repo / hex_child_github_branch / hex_child_activation_key
 * settings used for ongoing update checks), and
 * hex_install_child_theme_from_repo() fetches that repo's style.css,
 * confirms it actually declares "Template: <this theme's slug>" (i.e.
 * it really is a child theme of this theme, not just any repo), and
 * only then downloads and installs it via WP's own Theme_Upgrader.
 *
 * Plus a fully independent GitHub self-updater for whatever child
 * theme ends up installed — separate repo/branch/token and a separate
 * PucFactory checker instance from the parent theme's own updater
 * (inc/updater.php).
 *
 * Management (installing, checking, updating) lives entirely here in
 * the parent theme, not inside the child theme itself: WordPress
 * always loads the parent's functions.php even when the child is the
 * active theme, so this keeps working either way.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch a repository's style.css from GitHub and read its
 * "Theme Name:" and "Template:" headers, without installing anything.
 *
 * @param string $repo   "owner/repo".
 * @param string $branch Branch to read from.
 * @return array|WP_Error array( $theme_name, $template_slug ) on success.
 */
function hex_fetch_remote_child_style_css( $repo, $branch ) {
	$url = sprintf( 'https://raw.githubusercontent.com/%s/%s/style.css', $repo, $branch );

	$args = array( 'timeout' => 15 );

	$activation_key = hex_get_child_activation_key();
	if ( '' !== $activation_key ) {
		$args['headers'] = array( 'Authorization' => 'token ' . $activation_key );
	}

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $code ) {
		return new WP_Error(
			'hex_child_theme_fetch_failed',
			sprintf(
				/* translators: %d: HTTP response code. */
				__( 'Could not fetch style.css from that repository (HTTP %d). Check the repository, branch, and activation key.', 'hex' ),
				$code
			)
		);
	}

	$body = wp_remote_retrieve_body( $response );

	if ( ! preg_match( '/^\s*Template:\s*(.+)$/mi', $body, $template_matches ) ) {
		return new WP_Error(
			'hex_child_theme_no_template_header',
			__( 'That repository\'s style.css has no "Template:" header, so it is not a child theme at all.', 'hex' )
		);
	}

	$theme_name = '';
	if ( preg_match( '/^\s*Theme Name:\s*(.+)$/mi', $body, $name_matches ) ) {
		$theme_name = trim( $name_matches[1] );
	}

	return array( $theme_name, trim( $template_matches[1] ) );
}

/**
 * Validate that a repository is genuinely a child theme of this
 * theme (its style.css "Template:" header matches our own slug).
 *
 * @param string $repo   "owner/repo".
 * @param string $branch Branch to validate.
 * @return array|WP_Error array( $theme_name, $template_slug ) on success.
 */
function hex_validate_child_theme_repo( $repo, $branch ) {
	$result = hex_fetch_remote_child_style_css( $repo, $branch );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	list( $theme_name, $template_slug ) = $result;

	if ( strtolower( $template_slug ) !== strtolower( get_template() ) ) {
		return new WP_Error(
			'hex_child_theme_not_a_child',
			sprintf(
				/* translators: 1: Declared Template value, 2: This theme's own slug. */
				__( 'That repository declares "Template: %1$s" in style.css, which does not match this theme (%2$s) — it is not a child theme of this theme.', 'hex' ),
				$template_slug,
				get_template()
			)
		);
	}

	return array( $theme_name, $template_slug );
}

/**
 * Fetch, validate, and install a child theme from the saved GitHub
 * repository/branch — using WordPress's own Theme_Upgrader, the same
 * mechanism behind "Install Now" from a zip URL.
 *
 * @param string $repo   "owner/repo".
 * @param string $branch Branch to install.
 * @return string|WP_Error The installed theme's slug on success.
 */
function hex_install_child_theme_from_repo( $repo, $branch ) {
	$repo = trim( (string) $repo );
	if ( '' === $repo || ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo ) ) {
		return new WP_Error( 'hex_child_theme_invalid_repo', __( 'Enter the child theme repository as owner/repo.', 'hex' ) );
	}

	$branch = '' !== trim( (string) $branch ) ? trim( $branch ) : 'main';

	$validated = hex_validate_child_theme_repo( $repo, $branch );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}

	list( $theme_name, $template_slug ) = $validated;

	$slug = sanitize_title( '' !== $theme_name ? $theme_name : $repo );

	$zip_url = sprintf( 'https://github.com/%s/archive/refs/heads/%s.zip', $repo, $branch );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/theme.php';

	/*
	 * GitHub's zip extracts to "{repo}-{branch}/", not our expected
	 * slug — rename it in place before Theme_Upgrader finalizes the
	 * install. Standard, documented hook for this exact GitHub-zip
	 * naming mismatch.
	 */
	$rename_source = function ( $source, $remote_source, $upgrader, $hook_extra ) use ( $slug ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- required by the upgrader_source_selection hook's signature.
		global $wp_filesystem;

		if ( ! $wp_filesystem || ! $wp_filesystem->is_dir( $source ) ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . $slug;

		if ( untrailingslashit( $source ) === untrailingslashit( $desired ) ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $desired, true ) ) {
			return trailingslashit( $desired );
		}

		return $source;
	};

	add_filter( 'upgrader_source_selection', $rename_source, 10, 4 );
	$result = ( new Theme_Upgrader( new Automatic_Upgrader_Skin() ) )->install( $zip_url );
	remove_filter( 'upgrader_source_selection', $rename_source, 10 );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( ! $result ) {
		return new WP_Error( 'hex_child_theme_install_failed', __( 'Could not install the child theme.', 'hex' ) );
	}

	update_option( 'hex_child_theme_slug', $slug );
	update_option( 'hex_child_theme_name', '' !== $theme_name ? $theme_name : $slug );

	return $slug;
}

/**
 * Get the slug of the relevant child theme, if any.
 *
 * Prefers WordPress's own live reality over our bookkeeping: if a
 * child theme is currently active at all, this code only runs
 * because that child's parent IS this theme (WordPress always loads
 * the parent's functions.php too) — so `is_child_theme()` being true
 * here is proof enough, regardless of whether that child was
 * installed through hex_install_child_theme_from_repo() or by any
 * other means (a manual copy, git clone, etc.). Only when no child is
 * currently active do we fall back to the option this theme itself
 * last wrote (the most recent one it installed).
 *
 * @return string Empty string if no child theme is active or tracked.
 */
function hex_get_child_theme_slug() {
	if ( is_child_theme() ) {
		return get_stylesheet();
	}

	return trim( (string) get_option( 'hex_child_theme_slug', '' ) );
}

/**
 * Whether a child theme of this theme is the site's currently
 * *active* theme (as opposed to installed but not switched to, or
 * not installed at all).
 *
 * @return bool
 */
function hex_is_child_theme_active() {
	return is_child_theme();
}

/**
 * This child theme's own GitHub repository, independent of the parent's.
 *
 * @return string
 */
function hex_get_child_github_repo() {
	return trim( (string) get_option( 'hex_child_github_repo', '' ) );
}

/**
 * This child theme's tracked branch, defaulting to "main".
 *
 * @return string
 */
function hex_get_child_github_branch() {
	$branch = trim( (string) get_option( 'hex_child_github_branch', '' ) );

	return '' !== $branch ? $branch : 'main';
}

/**
 * This child theme's own GitHub access token.
 *
 * @return string
 */
function hex_get_child_activation_key() {
	return (string) get_option( 'hex_child_activation_key', '' );
}

/**
 * Build (and request-cache) the child theme's own update checker.
 *
 * Uses wp_get_theme( $slug )'s own stylesheet directory rather than
 * get_stylesheet_directory(), so this works whether or not the child
 * theme is currently the *active* theme.
 *
 * @return \YahnisElsts\PluginUpdateChecker\v5\Vcs\BaseChecker|null
 */
function hex_get_child_update_checker() {
	static $checker = null;

	$slug = hex_get_child_theme_slug();
	$repo = hex_get_child_github_repo();

	if ( '' === $slug || '' === $repo ) {
		return null;
	}

	$child_theme = wp_get_theme( $slug );
	if ( ! $child_theme->exists() ) {
		return null;
	}

	if ( null !== $checker ) {
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

	$checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/' . $repo . '/',
		$child_theme->get_stylesheet_directory() . '/style.css',
		$slug
	);

	$checker->setBranch( hex_get_child_github_branch() );

	$activation_key = hex_get_child_activation_key();
	if ( '' !== $activation_key ) {
		$checker->setAuthentication( $activation_key );
	}

	$checker->getVcsApi()->enableReleaseAssets();

	return $checker;
}
add_action( 'init', 'hex_get_child_update_checker' );

/**
 * Force an immediate remote check for the child theme.
 *
 * @return string One-line, human-readable result.
 */
function hex_check_for_child_theme_update() {
	if ( '' === hex_get_child_theme_slug() ) {
		return __( 'No child theme has been installed yet.', 'hex' );
	}

	$checker = hex_get_child_update_checker();

	if ( ! $checker ) {
		return __( 'Child theme update checker is not available. Save a GitHub repository for the child theme first.', 'hex' );
	}

	$update = $checker->checkForUpdates();

	if ( $update ) {
		return sprintf(
			/* translators: %s: New version number. */
			__( 'Update available: version %s', 'hex' ),
			$update->version
		);
	}

	$child_theme = wp_get_theme( hex_get_child_theme_slug() );

	return sprintf(
		/* translators: %s: Current child theme version. */
		__( 'You are running the latest version (%s).', 'hex' ),
		$child_theme->get( 'Version' )
	);
}

/**
 * Check for, and if available install, a child theme update from GitHub.
 *
 * @return string One-line, human-readable result.
 */
function hex_perform_child_theme_update() {
	$slug = hex_get_child_theme_slug();
	if ( '' === $slug ) {
		return __( 'No child theme has been installed yet.', 'hex' );
	}

	$checker = hex_get_child_update_checker();

	if ( ! $checker ) {
		return __( 'Child theme update checker is not available. Save a GitHub repository for the child theme first.', 'hex' );
	}

	$update = $checker->checkForUpdates();

	if ( ! $update ) {
		$child_theme = wp_get_theme( $slug );

		return sprintf(
			/* translators: %s: Current child theme version. */
			__( 'You are running the latest version (%s).', 'hex' ),
			$child_theme->get( 'Version' )
		);
	}

	if ( ! function_exists( 'wp_update_themes' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}
	wp_update_themes();

	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->upgrade( $slug );

	if ( is_wp_error( $result ) ) {
		return $result->get_error_message();
	}

	if ( ! $result ) {
		return __( 'Child theme update failed. See the WordPress site health log for details.', 'hex' );
	}

	return sprintf(
		/* translators: %s: New version number. */
		__( 'Child theme updated to version %s.', 'hex' ),
		$update->version
	);
}
