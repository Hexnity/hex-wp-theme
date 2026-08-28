<?php
/**
 * The template for displaying 404 (Not Found) errors.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-content mx-auto w-full max-w-6xl flex-1 px-6 py-10">
	<section class="error-404 not-found card animate__animated animate__fadeIn text-center">
		<header class="page-header">
			<h1 class="page-title hex-h1"><?php esc_html_e( 'Page Not Found', 'hex' ); ?></h1>
		</header>

		<div class="page-content mt-3 text-gray-600">
			<p><?php esc_html_e( 'The page you are looking for could not be found. It may have been moved or deleted.', 'hex' ); ?></p>
			<div class="mt-4 flex justify-center [&_input]:rounded-md [&_input]:border [&_input]:border-gray-300 [&_input]:px-3 [&_input]:py-2 [&_button]:ml-2 [&_button]:rounded-md [&_button]:bg-gray-900 [&_button]:px-4 [&_button]:py-2 [&_button]:text-white [&_button:hover]:bg-gray-800">
				<?php get_search_form(); ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
