<?php
/**
 * Template helper functions used across template files.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output the current page/post's published and, if edited, updated date.
 *
 * @return void
 */
function hex_posted_on() {
	$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string .= '<time class="updated" datetime="%3$s">%4$s</time>';
	}

	$time_string = sprintf(
		$time_string,
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( DATE_W3C ) ),
		esc_html( get_the_modified_date() )
	);

	echo '<span class="posted-on">' . $time_string . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $time_string is built from escaped pieces above.
}

/**
 * Output the current post's author, linked to their archive.
 *
 * @return void
 */
function hex_posted_by() {
	printf(
		'<span class="byline">%1$s <a class="author vcard" href="%2$s">%3$s</a></span>',
		esc_html__( 'by', 'hex' ),
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);
}

/**
 * Output the entry footer: category, tag, and edit links.
 *
 * @return void
 */
function hex_entry_footer() {
	if ( 'post' !== get_post_type() ) {
		return;
	}

	$categories_list = get_the_category_list( esc_html__( ', ', 'hex' ) );
	if ( $categories_list ) {
		printf( '<span class="cat-links">%1$s %2$s</span>', esc_html__( 'Posted in', 'hex' ), $categories_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_category_list() returns safe markup.
	}

	$tags_list = get_the_tag_list( '', esc_html__( ', ', 'hex' ) );
	if ( $tags_list && ! is_wp_error( $tags_list ) ) {
		printf( '<span class="tags-links">%1$s %2$s</span>', esc_html__( 'Tagged', 'hex' ), $tags_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_tag_list() returns safe markup.
	}

	edit_post_link(
		sprintf(
			wp_kses(
				/* translators: %s: Name of current post. */
				__( 'Edit <span class="screen-reader-text">%s</span>', 'hex' ),
				array( 'span' => array( 'class' => array() ) )
			),
			get_the_title()
		),
		'<span class="edit-link">',
		'</span>'
	);
}

/**
 * Determine whether the current page should render its title.
 *
 * The Full Width and Canvas templates suppress the entry title;
 * the Default template (and every other context) shows it.
 *
 * @return bool
 */
function hex_should_show_title() {
	if ( ! is_page() ) {
		return true;
	}

	$template = get_page_template_slug();

	return ! in_array( $template, array( 'template-full-width.php', 'template-canvas.php' ), true );
}

/**
 * Determine whether the current page should render the site header and footer.
 *
 * Only the Canvas template hides both.
 *
 * @return bool
 */
function hex_should_show_chrome() {
	if ( ! is_page() ) {
		return true;
	}

	return 'template-canvas.php' !== get_page_template_slug();
}
