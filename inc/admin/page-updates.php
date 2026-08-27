<?php
/**
 * Updates page: GitHub repository settings plus manual check/update actions.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Updates page.
 *
 * @return void
 */
function hex_render_updates_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$log = get_transient( 'hex_updates_log' );
	?>
	<?php
	hex_render_admin_shell_start(
		'updates',
		__( 'Updates', 'hex' ),
		__( 'Configure the GitHub repository this theme updates itself from.', 'hex' )
	);
	?>

	<?php settings_errors(); ?>

		<?php if ( $log ) : ?>
			<div class="mb-6 rounded-lg border border-indigo-800/50 bg-indigo-950/40 px-4 py-3 text-sm text-indigo-200!"><?php echo esc_html( $log ); ?></div>
		<?php endif; ?>

		<div class="mb-5 rounded-xl border border-gray-800 bg-gray-900 p-5">
			<h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'GitHub Repository', 'hex' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'hex_updates' );
				do_settings_sections( 'hex-theme-updates' );
				hex_render_admin_submit_button( __( 'Save Changes', 'hex' ) );
				?>
			</form>
		</div>

		<div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
			<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Manual Check', 'hex' ); ?></h2>
			<p class="mb-4 text-sm text-gray-400!"><?php esc_html_e( 'Checking bypasses the normal ~12 hour automatic-check throttle.', 'hex' ); ?></p>

			<div class="flex flex-wrap gap-3">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'hex_check_updates' ); ?>
					<input type="hidden" name="action" value="hex_check_updates">
					<button type="submit" class="hex-btn hex-btn-secondary">
						<?php esc_html_e( 'Check for Updates Now', 'hex' ); ?>
					</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hex-confirm-update">
					<?php wp_nonce_field( 'hex_perform_update' ); ?>
					<input type="hidden" name="action" value="hex_perform_update">
					<button type="submit" class="hex-btn hex-btn-primary">
						<?php esc_html_e( 'Check & Update Now', 'hex' ); ?>
					</button>
				</form>
			</div>
		</div>
	<?php
	hex_render_admin_shell_end();
}
