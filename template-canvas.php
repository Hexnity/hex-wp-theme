<?php
/**
 * Template Name: Canvas
 *
 * Fully clean layout: no site header, no site footer, no title —
 * only the page content, wrapped in the minimum markup wp_head()
 * and wp_footer() need to keep plugins and the admin bar working.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'hex-canvas bg-white text-gray-900' ); ?>>
<?php wp_body_open(); ?>

<?php
while ( have_posts() ) :
	the_post();
	?>
	<div id="post-<?php the_ID(); ?>" <?php post_class( 'entry is-canvas prose mx-auto max-w-none px-6 py-10' ); ?>>
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
?>

<?php wp_footer(); ?>
</body>
</html>
