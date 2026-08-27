<?php
/**
 * Child Theme page: fetch and install a child theme from a GitHub
 * repository (after validating it's actually a child of this theme),
 * and manage its own, independent GitHub update settings.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Child Theme page.
 *
 * @return void
 */
function hex_render_child_theme_page() {
	if ( ! current_user_can( 'edit_themes' ) ) {
		return;
	}

	$slug      = hex_get_child_theme_slug();
	$is_active = hex_is_child_theme_active();
	$log       = get_transient( 'hex_child_theme_log' );
	?>
	<?php
	hex_render_admin_shell_start(
		'child-theme',
		__( 'Child Theme', 'hex' ),
		__( 'Fetch, install, and update a child theme from your own GitHub repository.', 'hex' )
	);
	?>

	<?php settings_errors(); ?>

		<?php if ( $log ) : ?>
			<div class="mb-6 rounded-lg border border-indigo-800/50 bg-indigo-950/40 px-4 py-3 text-sm text-indigo-200!"><?php echo esc_html( $log ); ?></div>
		<?php endif; ?>

		<div class="mb-5 rounded-xl border border-gray-800 bg-gray-900 p-5">
			<h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Child Theme GitHub Repository', 'hex' ); ?></h2>
			<p class="mb-4 text-sm text-gray-400!"><?php esc_html_e( 'Used to both install and update the child theme — entirely separate from the main theme\'s own repository.', 'hex' ); ?></p>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'hex_child_updates' );
				do_settings_sections( 'hex-theme-child-theme' );
				hex_render_admin_submit_button( __( 'Save Changes', 'hex' ) );
				?>
			</form>
		</div>

		<?php if ( '' !== $slug ) : ?>
			<?php $child_theme = wp_get_theme( $slug ); ?>
			<div class="mb-5 rounded-xl border border-gray-800 bg-gray-900 p-5">
				<h2 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!">
					<?php esc_html_e( 'Current Child Theme', 'hex' ); ?>
					<?php if ( $is_active ) : ?>
						<span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold normal-case tracking-normal text-emerald-400!"><?php esc_html_e( 'Activated', 'hex' ); ?></span>
					<?php elseif ( $child_theme->exists() ) : ?>
						<span class="rounded-full bg-gray-800 px-2 py-0.5 text-xs font-semibold normal-case tracking-normal text-gray-400!"><?php esc_html_e( 'Installed, not active', 'hex' ); ?></span>
					<?php endif; ?>
				</h2>
				<?php if ( $child_theme->exists() ) : ?>
					<p class="text-sm text-gray-400!">
						<?php
						printf(
							/* translators: 1: Theme name, 2: Slug, 3: Version. */
							esc_html__( '%1$s (%2$s) — version %3$s.', 'hex' ),
							esc_html( $child_theme->get( 'Name' ) ),
							esc_html( $slug ),
							esc_html( $child_theme->get( 'Version' ) )
						);
						?>
					</p>
					<?php if ( $is_active ) : ?>
						<p class="mt-1 text-sm text-gray-400!"><?php esc_html_e( 'This child theme is the currently active theme.', 'hex' ); ?></p>
					<?php endif; ?>
					<a class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'themes.php' ) ); ?>">
						<?php echo $is_active ? esc_html__( 'Manage in Appearance', 'hex' ) : esc_html__( 'Activate in Appearance', 'hex' ); ?> &rarr;
					</a>
				<?php else : ?>
					<p class="text-sm text-gray-400!"><?php esc_html_e( 'The previously installed child theme is no longer on disk.', 'hex' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! $is_active ) : ?>
			<div class="mb-5 rounded-xl border border-gray-800 bg-gray-900 p-5">
				<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Install Child Theme', 'hex' ); ?></h2>
				<p class="mb-4 text-sm text-gray-400!"><?php esc_html_e( 'Fetches style.css from the saved repository above, confirms it declares this theme as its Template (i.e. it really is a child theme of this theme), and only then downloads and installs it. Nothing is generated locally.', 'hex' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'hex_install_child_theme' ); ?>
					<input type="hidden" name="action" value="hex_install_child_theme">
					<button type="submit" class="hex-btn hex-btn-primary">
						<?php esc_html_e( 'Fetch & Install Child Theme', 'hex' ); ?>
					</button>
				</form>
			</div>
		<?php else : ?>
			<div class="mb-5 rounded-xl border border-indigo-800/50 bg-indigo-950/40 p-5">
				<p class="text-sm text-indigo-200!"><?php esc_html_e( 'The child theme is active, so installing over it is hidden here. Use "Check & Update Now" below to update it, or deactivate it in Appearance → Themes first to install a fresh copy.', 'hex' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
			<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Manual Check', 'hex' ); ?></h2>
			<p class="mb-4 text-sm text-gray-400!"><?php esc_html_e( 'Checks the child theme\'s own repository — entirely separate from the main theme\'s update check.', 'hex' ); ?></p>

			<div class="flex flex-wrap gap-3">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'hex_check_child_updates' ); ?>
					<input type="hidden" name="action" value="hex_check_child_updates">
					<button type="submit" class="hex-btn hex-btn-secondary">
						<?php esc_html_e( 'Check for Updates Now', 'hex' ); ?>
					</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hex-confirm-update">
					<?php wp_nonce_field( 'hex_perform_child_update' ); ?>
					<input type="hidden" name="action" value="hex_perform_child_update">
					<button type="submit" class="hex-btn hex-btn-primary">
						<?php esc_html_e( 'Check & Update Now', 'hex' ); ?>
					</button>
				</form>
			</div>
		</div>
	<?php
	hex_render_admin_shell_end();
}
