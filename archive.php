<?php
/**
 * The template for displaying archive pages (category, tag, date, author).
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-content mx-auto w-full max-w-6xl flex-1 px-6 py-10">
	<?php if ( have_posts() ) : ?>
		<header class="page-header animate__animated animate__fadeIn mb-8">
			<?php
			the_archive_title( '<h1 class="page-title text-h1">', '</h1>' );
			the_archive_description( '<div class="archive-description prose mt-2 max-w-none">', '</div>' );
			?>
		</header>

		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content' );
		endwhile;

		the_posts_pagination(
			array(
				'mid_size' => 1,
				'class'    => 'mt-8 flex justify-center gap-2 [&_a]:rounded-md [&_a]:border [&_a]:border-gray-300 [&_a]:px-3 [&_a]:py-1.5 [&_a:hover]:bg-gray-50 [&_.current]:rounded-md [&_.current]:bg-gray-900 [&_.current]:px-3 [&_.current]:py-1.5 [&_.current]:text-white',
			)
		);
	else :
		get_template_part( 'template-parts/content', 'none' );
	endif;
	?>
</main>

<?php
get_footer();
