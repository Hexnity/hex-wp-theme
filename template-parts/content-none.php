<?php
/**
 * Template part shown when a query returns no results.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="no-results not-found card animate__animated animate__fadeIn text-center">
	<header class="page-header">
		<h1 class="page-title text-h2"><?php esc_html_e( 'Nothing Found', 'hex' ); ?></h1>
	</header>

	<div class="page-content mt-3 text-gray-600">
		<?php if ( is_search() ) : ?>
			<p><?php esc_html_e( 'Sorry, nothing matched your search terms. Please try again with different keywords.', 'hex' ); ?></p>
			<div class="mt-4 flex justify-center [&_input]:rounded-md [&_input]:border [&_input]:border-gray-300 [&_input]:px-3 [&_input]:py-2 [&_button]:ml-2 [&_button]:rounded-md [&_button]:bg-gray-900 [&_button]:px-4 [&_button]:py-2 [&_button]:text-white [&_button:hover]:bg-gray-800">
				<?php get_search_form(); ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'It looks like nothing was found at this location.', 'hex' ); ?></p>
		<?php endif; ?>
	</div>
</section>
