<?php
/**
 * The header template: opens <html>/<body>, renders the skip link,
 * site branding, and primary navigation.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'bg-white text-gray-900' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site flex min-h-screen flex-col">
	<a class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-[100000] focus:rounded focus:bg-gray-900 focus:px-4 focus:py-3 focus:text-white" href="#primary">
		<?php esc_html_e( 'Skip to content', 'hex' ); ?>
	</a>

	<header id="masthead" class="site-header animate__animated animate__fadeIn border-b border-gray-200">
		<div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
			<div class="site-branding flex items-center gap-3">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<p class="site-title m-0 text-large font-semibold">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-gray-600" rel="home">
							<?php bloginfo( 'name' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<nav id="site-navigation" class="main-navigation flex items-center">
				<button
					class="menu-toggle inline-flex items-center rounded border border-gray-300 px-3 py-1.5 text-sm md:hidden"
					aria-controls="primary-menu"
					aria-expanded="false"
				>
					<?php esc_html_e( 'Menu', 'hex' ); ?>
				</button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_id'        => 'primary-menu',
						'container'      => false,
						'fallback_cb'    => false,
						'menu_class'     => 'hidden md:flex flex-col md:flex-row gap-2 md:gap-6 text-sm w-full md:w-auto mt-3 md:mt-0 [&_a]:block [&_a]:py-1 [&_a:hover]:text-gray-500',
					)
				);
				?>
			</nav>
		</div>
	</header>
