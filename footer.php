<?php
/**
 * The footer template: footer widgets, footer navigation, closing markup.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<footer id="colophon" class="site-footer mt-auto border-t border-gray-200 text-gray-600">
		<div class="mx-auto max-w-6xl px-6 py-10">
			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<div class="footer-widgets mb-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 [&_.widget-title]:mb-2 [&_.widget-title]:font-semibold [&_.widget-title]:text-gray-900">
					<?php dynamic_sidebar( 'footer-1' ); ?>
				</div>
			<?php endif; ?>

			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'menu_id'        => 'footer-menu',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'mb-4 flex flex-wrap gap-5 text-sm [&_a:hover]:text-gray-900',
				)
			);
			?>

			<div class="site-info border-t border-gray-100 pt-4 text-small">
				<?php
				$hex_footer_text = get_theme_mod( 'hex_footer_text', '' );
				if ( $hex_footer_text ) {
					echo esc_html( $hex_footer_text );
				} else {
					printf(
						/* translators: %s: Current year. */
						esc_html__( '© %s. All rights reserved.', 'hex' ),
						esc_html( gmdate( 'Y' ) )
					);
				}
				?>
			</div>
		</div>
	</footer>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
