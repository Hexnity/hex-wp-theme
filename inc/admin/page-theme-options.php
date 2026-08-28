<?php
/**
 * Theme Options page: the full design-token schema (~150 fields
 * across 12 groups — typography, spacing, colors, buttons, forms,
 * cards, sections, global radius, tables, alerts, badges, icons) plus
 * any auto-detected "Custom Tokens", in a left-category / right-panel
 * tabbed layout (assets/js/admin.js drives the tab switching and the
 * color-swatch/text-field sync). Within a tab, a loop-built field
 * family (H1, Primary button, Success alert, etc.) with more than one
 * distinct 'subgroup' renders as a collapsible accordion instead of
 * one flat list — see hex_render_style_group_fields()
 * (inc/admin/settings.php). Editing is gated on an active child
 * theme, since values are saved into a CSS file inside it (see
 * inc/style-settings.php, knoladge/child-theme-css-token-architecture.md)
 * — the values themselves still apply on the front end regardless.
 * Saved via a dedicated admin-post handler
 * (hex_handle_save_style_options(), inc/admin/handlers.php), not the
 * Settings API.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Theme Options page.
 *
 * @return void
 */
function hex_render_theme_options_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$active = hex_is_child_theme_active();
	$groups = hex_get_effective_style_groups();
	$count  = count( hex_get_effective_style_schema() );
	$log    = get_transient( 'hex_theme_options_log' );
	?>
	<?php
	hex_render_admin_shell_start(
		'theme-options',
		__( 'Theme Options', 'hex' ),
		sprintf(
			/* translators: %d: Number of style settings. */
			esc_html__( '%d design tokens, applied site-wide via CSS custom properties the moment you save — no rebuild required.', 'hex' ),
			$count
		)
	);
	?>

	<?php if ( $log ) : ?>
		<div class="mb-6 rounded-lg border border-indigo-800/50 bg-indigo-950/40 px-4 py-3 text-sm text-indigo-200!"><?php echo esc_html( $log ); ?></div>
	<?php endif; ?>

	<?php if ( ! $active ) : ?>
		<div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-700/60 bg-amber-950/40 px-4 py-3 text-sm text-amber-200!">
			<span class="mt-0.5">⚠</span>
			<span>
				<?php esc_html_e( 'Theme style customization requires an active child theme, so your changes survive a parent theme update instead of being overwritten by it. Install and activate a child theme first — the fields below are disabled until then.', 'hex' ); ?>
				<a class="ml-1 font-medium text-amber-100! underline" href="<?php echo esc_url( admin_url( 'admin.php?page=hex-theme-child-theme' ) ); ?>">
					<?php esc_html_e( 'Go to Child Theme', 'hex' ); ?> &rarr;
				</a>
			</span>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'hex_save_style_options' ); ?>
		<input type="hidden" name="action" value="hex_save_style_options">
		<fieldset <?php disabled( $active, false ); ?> class="block">
			<div class="hex-options-shell flex flex-col gap-6 lg:flex-row" data-hex-tabs>
				<nav
					class="flex gap-1 overflow-x-auto rounded-xl border border-gray-800 bg-gray-900 p-2 lg:w-56 lg:shrink-0 lg:flex-col lg:overflow-visible lg:self-start"
					role="tablist"
					aria-label="<?php esc_attr_e( 'Theme Option Groups', 'hex' ); ?>"
				>
					<?php foreach ( $groups as $group => $label ) : ?>
						<button
							type="button"
							class="hex-tab-btn flex shrink-0 items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors hover:bg-gray-800! lg:w-full"
							data-hex-tab-target="<?php echo esc_attr( $group ); ?>"
						>
							<?php echo esc_html( $label ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<div class="min-w-0 flex-1">
					<?php foreach ( $groups as $group => $label ) : ?>
						<div class="hex-tab-panel rounded-xl border border-gray-800 bg-gray-900" data-hex-tab-panel="<?php echo esc_attr( $group ); ?>">
							<div class="flex items-center justify-between gap-3 border-b border-gray-800 px-6 py-4">
								<h2 class="m-0 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php echo esc_html( $label ); ?></h2>
								<?php if ( $active ) : ?>
									<a
										href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hex_reset_style_group&group=' . rawurlencode( $group ) ), 'hex_reset_style_group' ) ); ?>"
										class="text-xs font-medium text-gray-500! hover:text-gray-300!"
										onclick="return confirm('<?php echo esc_js( __( 'Reset every field in this group back to its default value? This cannot be undone.', 'hex' ) ); ?>');"
									>
										<?php esc_html_e( 'Reset to Defaults', 'hex' ); ?>
									</a>
								<?php endif; ?>
							</div>
							<?php if ( 'typography' === $group ) : ?>
								<?php hex_render_google_fonts_field(); ?>
							<?php endif; ?>
							<?php hex_render_style_group_fields( $group ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</fieldset>

		<?php hex_render_google_fonts_datalist(); ?>

		<div class="sticky bottom-0 mt-6 flex items-center gap-3 rounded-xl border border-gray-800 bg-gray-900/90 px-6 py-4 backdrop-blur">
			<?php hex_render_admin_submit_button( __( 'Save Style Settings', 'hex' ), ! $active ); ?>
			<?php if ( ! $active ) : ?>
				<span class="text-sm text-gray-500!"><?php esc_html_e( 'Disabled until a child theme is active.', 'hex' ); ?></span>
			<?php endif; ?>
		</div>
	</form>

	<?php
	hex_render_admin_shell_end();
}
