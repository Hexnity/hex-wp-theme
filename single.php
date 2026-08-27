<?php
/**
 * The template for displaying a single post.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-content mx-auto w-full max-w-6xl flex-1 px-6 py-10">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content' );

		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}

		the_post_navigation(
			array(
				'prev_text' => '<span class="text-xs uppercase tracking-wide text-gray-400">' . esc_html__( 'Previous', 'hex' ) . '</span><br>%title',
				'next_text' => '<span class="text-xs uppercase tracking-wide text-gray-400">' . esc_html__( 'Next', 'hex' ) . '</span><br>%title',
				'class'     => 'mt-8 flex justify-between gap-4 border-t border-gray-100 pt-6 text-sm [&_a]:font-medium [&_a:hover]:text-gray-900',
			)
		);
	endwhile;
	?>
</main>

<?php
get_footer();
