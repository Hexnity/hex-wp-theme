<?php
/**
 * Template Name: Full Width
 *
 * Header and footer are shown, but the page title and the
 * default content-width constraint are both suppressed so
 * content can span the full available width.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-content is-full-width mx-auto w-full flex-1 px-6 py-10">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', 'page' );
	endwhile;
	?>
</main>

<?php
get_footer();
