<?php
/**
 * Dashboard page: theme status at a glance.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Dashboard page.
 *
 * @return void
 */
function hex_render_dashboard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$theme       = wp_get_theme( get_template() );
	$repo        = hex_get_github_repo();
	$nav_menus   = get_registered_nav_menus();
	$templates   = hex_get_page_templates();
	$is_active   = hex_is_child_theme_active();
	$field_count = count( hex_get_effective_style_schema() );
	?>
	<?php
	hex_render_admin_shell_start(
		'dashboard',
		__( 'Dashboard', 'hex' ),
		__( 'An at-a-glance overview of this theme\'s setup.', 'hex' )
	);
	?>

	<div class="mb-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
		<?php
		hex_render_admin_stat_tile( 'about', $theme->get( 'Version' ), __( 'Theme Version', 'hex' ) );
		hex_render_admin_stat_tile( 'dashboard', count( $templates ), __( 'Page Templates', 'hex' ) );
		hex_render_admin_stat_tile( 'theme-options', $field_count, __( 'Style Settings', 'hex' ) );
		hex_render_admin_stat_tile( 'child-theme', $is_active ? __( 'Active', 'hex' ) : __( 'Not Active', 'hex' ), __( 'Child Theme', 'hex' ) );
		?>
	</div>

	<div class="grid gap-5 sm:grid-cols-2">
			<div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
				<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Updates', 'hex' ); ?></h2>
				<?php if ( '' === $repo ) : ?>
					<p class="text-sm text-gray-400!"><?php esc_html_e( 'No GitHub repository configured yet.', 'hex' ); ?></p>
				<?php else : ?>
					<p class="text-sm text-gray-400!">
						<?php
						printf(
							/* translators: 1: Repository, 2: Branch. */
							esc_html__( 'Tracking %1$s (%2$s branch).', 'hex' ),
							'<code class="rounded bg-gray-800 px-1.5 py-0.5 text-gray-200!">' . esc_html( $repo ) . '</code>',
							esc_html( hex_get_github_branch() )
						);
						?>
					</p>
				<?php endif; ?>
				<a class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'admin.php?page=hex-theme-updates' ) ); ?>">
					<?php esc_html_e( 'Manage Updates', 'hex' ); ?> &rarr;
				</a>
			</div>

			<div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
				<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Page Templates', 'hex' ); ?></h2>
				<ul class="space-y-1 text-sm text-gray-400!">
					<?php foreach ( $templates as $label ) : ?>
						<li class="flex items-center gap-2">
							<span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
							<?php echo esc_html( $label ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
				<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Navigation Menus', 'hex' ); ?></h2>
				<ul class="space-y-1 text-sm text-gray-400!">
					<?php foreach ( $nav_menus as $location => $description ) : ?>
						<li class="flex items-center justify-between">
							<span><?php echo esc_html( $description ); ?></span>
							<?php if ( has_nav_menu( $location ) ) : ?>
								<span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-400!"><?php esc_html_e( 'assigned', 'hex' ); ?></span>
							<?php else : ?>
								<span class="rounded-full bg-gray-800 px-2 py-0.5 text-xs font-medium text-gray-500!"><?php esc_html_e( 'not assigned', 'hex' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<a class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">
					<?php esc_html_e( 'Manage Menus', 'hex' ); ?> &rarr;
				</a>
			</div>

			<div class="rounded-xl border border-gray-800 bg-gray-900 p-5">
				<h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-400!"><?php esc_html_e( 'Quick Links', 'hex' ); ?></h2>
				<ul class="space-y-1 text-sm">
					<li><a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Customize', 'hex' ); ?></a></li>
					<li><a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Widgets', 'hex' ); ?></a></li>
					<li><a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'admin.php?page=hex-theme-about' ) ); ?>"><?php esc_html_e( 'About This Theme', 'hex' ); ?></a></li>
					<li><a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'admin.php?page=hex-theme-child-theme' ) ); ?>"><?php esc_html_e( 'Child Theme', 'hex' ); ?></a></li>
					<li><a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( admin_url( 'admin.php?page=hex-theme-theme-options' ) ); ?>"><?php esc_html_e( 'Theme Options', 'hex' ); ?></a></li>
				</ul>
		</div>
	</div>
	<?php
	hex_render_admin_shell_end();
}
