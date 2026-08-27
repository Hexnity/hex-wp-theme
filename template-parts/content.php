<?php
/**
 * Template part for displaying a post entry in loop contexts
 * (index, archive, search results).
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry card animate__animated animate__fadeIn mb-6' ); ?>>
	<header class="entry-header mb-3">
		<?php
		if ( is_singular() ) :
			the_title( '<h1 class="entry-title">', '</h1>' );
		else :
			the_title( '<h2 class="entry-title"><a class="hover:text-site-primary" href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;

		if ( 'post' === get_post_type() ) :
			?>
			<div class="entry-meta mt-1 flex flex-wrap gap-2 text-small text-gray-500">
				<?php
				hex_posted_on();
				hex_posted_by();
				?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( has_post_thumbnail() && ! is_singular() ) : ?>
		<div class="post-thumbnail mb-4 overflow-hidden rounded-lg">
			<a href="<?php the_permalink(); ?>">
				<?php the_post_thumbnail( 'medium', array( 'class' => 'w-full object-cover' ) ); ?>
			</a>
		</div>
	<?php endif; ?>

	<div class="entry-content prose max-w-none">
		<?php
		if ( is_singular() ) {
			the_content();
		} else {
			the_excerpt();
		}
		?>
	</div>

	<footer class="entry-footer mt-4 flex flex-wrap gap-3 border-t border-gray-100 pt-3 text-small text-gray-500">
		<?php hex_entry_footer(); ?>
	</footer>
</article>
