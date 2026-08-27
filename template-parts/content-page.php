<?php
/**
 * Template part for rendering a single page's title and content.
 *
 * Shared by page.php and template-default.php. Title visibility
 * is controlled by hex_should_show_title() so Full Width and
 * Canvas pages can reuse it without repeating a title check.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry animate__animated animate__fadeIn' ); ?>>
	<?php if ( hex_should_show_title() ) : ?>
		<header class="entry-header mb-6">
			<?php the_title( '<h1 class="entry-title text-h1">', '</h1>' ); ?>
		</header>
	<?php endif; ?>

	<div class="entry-content prose max-w-none">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<div class="page-links mt-6 text-small">' . esc_html__( 'Pages:', 'hex' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>
</article>
