<?php
/**
 * The main template file — fallback for any query WordPress
 * doesn't have a more specific template for.
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
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content' );
		endwhile;

		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => __( '&laquo; Previous', 'hex' ),
				'next_text' => __( 'Next &raquo;', 'hex' ),
				'class'     => 'mt-8 flex justify-center gap-2 [&_a]:rounded-md [&_a]:border [&_a]:border-gray-300 [&_a]:px-3 [&_a]:py-1.5 [&_a:hover]:bg-gray-50 [&_.current]:rounded-md [&_.current]:bg-gray-900 [&_.current]:px-3 [&_.current]:py-1.5 [&_.current]:text-white',
			)
		);
	else :
		get_template_part( 'template-parts/content', 'none' );
	endif;
	?>
</main>

<?php
get_footer();
