<?php
/**
 * The template for displaying comments and the comment form.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments-area mt-10 border-t border-gray-100 pt-8">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title hex-h3">
			<?php
			printf(
				/* translators: %s: Number of comments. */
				esc_html( _n( '%s Comment', '%s Comments', get_comments_number(), 'hex' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="comment-list mt-6 space-y-6 [&_.comment-author]:font-medium [&_.comment-metadata]:text-sm [&_.comment-metadata]:text-gray-400 [&_.comment-content]:mt-2 [&_.comment-content_p]:mb-2">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="no-comments mt-6 hex-small text-gray-500"><?php esc_html_e( 'Comments are closed.', 'hex' ); ?></p>
	<?php endif; ?>

	<div class="comment-respond-wrap mt-8 [&_input[type=text]]:w-full [&_input[type=email]]:w-full [&_input[type=url]]:w-full [&_input]:rounded-md [&_input]:border [&_input]:border-gray-300 [&_input]:px-3 [&_input]:py-2 [&_textarea]:w-full [&_textarea]:rounded-md [&_textarea]:border [&_textarea]:border-gray-300 [&_textarea]:px-3 [&_textarea]:py-2 [&_.submit]:rounded-md [&_.submit]:bg-gray-900 [&_.submit]:px-4 [&_.submit]:py-2 [&_.submit]:text-white [&_.submit:hover]:bg-gray-800 [&_label]:mb-1 [&_label]:block [&_label]:text-sm [&_.comment-form>p]:mb-4">
		<?php comment_form(); ?>
	</div>
</div>
