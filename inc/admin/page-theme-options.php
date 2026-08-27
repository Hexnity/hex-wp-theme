<?php
/**
 * Theme Options page: the full design-token schema (~146 fields
 * across 12 groups — typography, spacing, colors, buttons, forms,
 * cards, sections, global radius, tables, alerts, badges, icons), in
 * a left-category / right-panel tabbed layout (assets/js/admin.js
 * drives the tab switching and the color-swatch/text-field sync).
 * Editing is gated on an active child theme, so style customizations
 * always live somewhere that survives a parent theme update — the
 * values themselves still apply on the front end regardless (see
 * inc/style-settings.php).
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
	$groups = hex_get_style_groups();
	$count  = count( hex_get_style_schema() );
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

	<?php settings_errors(); ?>

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

	<form method="post" action="options.php">
		<?php settings_fields( 'hex_style_options' ); ?>
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
							class="hex-tab-btn flex shrink-0 items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-gray-400! transition-colors hover:bg-gray-800 hover:text-gray-200! lg:w-full"
							data-hex-tab-target="<?php echo esc_attr( $group ); ?>"
						>
							<?php echo esc_html( $label ); ?>
						</button>
					<?php endforeach; ?>
				</nav>

				<div class="min-w-0 flex-1">
					<?php foreach ( $groups as $group => $label ) : ?>
						<div class="hex-tab-panel rounded-xl border border-gray-800 bg-gray-900" data-hex-tab-panel="<?php echo esc_attr( $group ); ?>">
							<div class="border-b border-gray-800 px-6 py-4">
								<h2 class="m-0 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php echo esc_html( $label ); ?></h2>
							</div>
							<?php if ( 'typography' === $group ) : ?>
								<?php hex_render_google_fonts_field(); ?>
							<?php endif; ?>
							<div class="grid grid-cols-1 gap-x-6 gap-y-5 p-6 sm:grid-cols-2">
								<?php hex_render_style_group_fields( $group ); ?>
							</div>
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
