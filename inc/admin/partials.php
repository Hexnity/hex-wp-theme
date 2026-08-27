<?php
/**
 * Shared admin dashboard chrome: a branded sidebar (logo, icon nav,
 * version footer) plus a content pane with its own header bar, used by
 * every one of the theme's five admin pages so they read as one
 * cohesive app rather than five separate settings screens.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The five theme admin pages, in nav order.
 *
 * @return array<string, array{0: string, 1: string}> Map of nav key => array( label, menu slug ).
 */
function hex_admin_nav_items() {
	return array(
		'dashboard'     => array( __( 'Dashboard', 'hex' ), 'hex-theme' ),
		'updates'       => array( __( 'Updates', 'hex' ), 'hex-theme-updates' ),
		'child-theme'   => array( __( 'Child Theme', 'hex' ), 'hex-theme-child-theme' ),
		'theme-options' => array( __( 'Theme Options', 'hex' ), 'hex-theme-theme-options' ),
		'about'         => array( __( 'About', 'hex' ), 'hex-theme-about' ),
	);
}

/**
 * Print one of a fixed set of inline SVG icons. Built from plain
 * shapes (rect/circle/line) rather than copied bezier path data, so
 * every icon is guaranteed to render correctly with no external icon
 * font or CDN dependency.
 *
 * @param string $name    One of 'dashboard', 'updates', 'child-theme', 'theme-options', 'about', 'check'.
 * @param string $classes Utility classes for the <svg> element.
 * @return void
 */
function hex_render_admin_icon( $name, $classes = 'h-5 w-5' ) {
	$icons = array(
		'dashboard'     => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/>',
		'updates'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2" stroke-linecap="round" stroke-linejoin="round"/>',
		'child-theme'   => '<rect x="4" y="4" width="12" height="12" rx="2"/><rect x="8" y="8" width="12" height="12" rx="2"/>',
		'theme-options' => '<line x1="4" y1="6" x2="20" y2="6" stroke-linecap="round"/><circle cx="9" cy="6" r="2" fill="currentColor" stroke="none"/><line x1="4" y1="12" x2="20" y2="12" stroke-linecap="round"/><circle cx="15" cy="12" r="2" fill="currentColor" stroke="none"/><line x1="4" y1="18" x2="20" y2="18" stroke-linecap="round"/><circle cx="7" cy="18" r="2" fill="currentColor" stroke="none"/>',
		'about'         => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16" stroke-linecap="round"/><circle cx="12" cy="7.5" r="0.75" fill="currentColor" stroke="none"/>',
		'check'         => '<path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return;
	}

	printf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="%s" aria-hidden="true">%s</svg>',
		esc_attr( $classes ),
		$icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed set of hardcoded, trusted SVG markup, not user input.
	);
}

/**
 * Open the shared dashboard shell: sidebar + content pane + content
 * header. Every admin page calls this right after its capability
 * check, then prints its own body markup, then calls
 * hex_render_admin_shell_end().
 *
 * @param string $active      One of the hex_admin_nav_items() keys.
 * @param string $title       Content-pane page title.
 * @param string $description Optional one-line description under the title.
 * @return void
 */
function hex_render_admin_shell_start( $active, $title, $description = '' ) {
	$logo  = HEX_THEME_DIR . '/assets/images/hexnity-dark.png';
	$theme = wp_get_theme( get_template() );
	$items = hex_admin_nav_items();
	?>
	<div class="wrap hex-admin">
		<div class="hex-shell flex items-stretch overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-xl shadow-black/20">
			<aside class="flex w-60 shrink-0 flex-col justify-between bg-hexnity-900">
				<div>
					<div class="border-b border-white/10 px-5 py-6">
						<?php if ( file_exists( $logo ) ) : ?>
							<img
								src="<?php echo esc_url( HEX_THEME_URI . '/assets/images/hexnity-dark.png' ); ?>"
								alt="<?php echo esc_attr( $theme->get( 'Name' ) ); ?>"
								class="h-11 w-auto"
							>
						<?php else : ?>
							<span class="text-base font-semibold text-white!"><?php echo esc_html( $theme->get( 'Name' ) ); ?></span>
						<?php endif; ?>
					</div>

					<nav class="flex flex-col gap-1 p-3" aria-label="<?php esc_attr_e( 'Theme admin navigation', 'hex' ); ?>">
						<?php foreach ( $items as $key => $item ) : ?>
							<?php $is_active = ( $active === $key ); ?>
							<a
								href="<?php echo esc_url( admin_url( 'admin.php?page=' . $item[1] ) ); ?>"
								class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?php echo $is_active ? 'bg-white/10 text-white!' : 'text-white/6!0! hover:bg-white/5 hover:text-white!'; ?>"
								<?php echo $is_active ? 'aria-current="page"' : ''; ?>
							>
								<?php hex_render_admin_icon( $key, 'h-5 w-5 shrink-0' ); ?>
								<?php echo esc_html( $item[0] ); ?>
							</a>
						<?php endforeach; ?>
					</nav>
				</div>

				<div class="border-t border-white/10 px-5 py-4 text-xs text-white/4!0!">
					<?php
					printf(
						/* translators: %s: theme version. */
						esc_html__( 'Version %s', 'hex' ),
						esc_html( $theme->get( 'Version' ) )
					);
					?>
				</div>
			</aside>

			<div class="min-w-0 flex-1 bg-gray-950">
				<header class="border-b border-gray-800 bg-gray-900 px-8 py-5">
					<h1 class="m-0 text-xl font-semibold text-white!"><?php echo esc_html( $title ); ?></h1>
					<?php if ( '' !== $description ) : ?>
						<p class="mt-1 mb-0 text-sm text-gray-400!"><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>
				</header>
				<div class="p-8">
	<?php
}

/**
 * Close the shell markup opened by hex_render_admin_shell_start().
 *
 * @return void
 */
function hex_render_admin_shell_end() {
	?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render a single at-a-glance stat tile (icon, big value, small label)
 * for use at the top of the Dashboard page.
 *
 * @param string $icon  One of hex_render_admin_icon()'s icon names.
 * @param string $value The headline value, e.g. "1.2.0" or "146".
 * @param string $label The caption underneath, e.g. "Theme Version".
 * @return void
 */
function hex_render_admin_stat_tile( $icon, $value, $label ) {
	?>
	<div class="flex items-center gap-4 rounded-xl border border-gray-800 bg-gray-900 p-5">
		<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-400!">
			<?php hex_render_admin_icon( $icon, 'h-5 w-5' ); ?>
		</span>
		<div class="min-w-0">
			<p class="m-0 truncate text-lg font-semibold text-white!"><?php echo esc_html( $value ); ?></p>
			<p class="m-0 truncate text-xs font-medium uppercase tracking-wide text-gray-500!"><?php echo esc_html( $label ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Render a primary "submit" button styled to match the dashboard's
 * dark theme, replacing WP core's own submit_button() (which prints
 * a light-themed, unlayered-CSS button that would clash here).
 *
 * @param string $label    Button text.
 * @param bool   $disabled Whether to render it disabled.
 * @return void
 */
function hex_render_admin_submit_button( $label, $disabled = false ) {
	printf(
		'<button type="submit" name="submit" id="submit" class="hex-btn hex-btn-primary"%s>%s</button>',
		$disabled ? ' disabled' : '',
		esc_html( $label )
	);
}
