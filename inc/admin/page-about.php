<?php
/**
 * About page: theme metadata pulled straight from style.css's header,
 * so this always stays in sync with whatever the theme is renamed to.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the About page.
 *
 * @return void
 */
function hex_render_about_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$theme = wp_get_theme( get_template() );
	$rows  = array(
		__( 'Version', 'hex' )           => $theme->get( 'Version' ),
		__( 'Author', 'hex' )            => $theme->get( 'Author' ),
		__( 'License', 'hex' )           => $theme->get( 'License' ),
		__( 'PHP Version', 'hex' )       => PHP_VERSION,
		__( 'WordPress Version', 'hex' ) => get_bloginfo( 'version' ),
	);
	?>
	<?php
	hex_render_admin_shell_start(
		'about',
		$theme->get( 'Name' ),
		$theme->get( 'Description' )
	);
	?>

	<div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
			<dl class="divide-y divide-gray-800">
				<?php foreach ( $rows as $label => $value ) : ?>
					<div class="flex items-center gap-4 px-5 py-3 text-sm">
						<dt class="w-40 shrink-0 font-medium text-gray-500!"><?php echo esc_html( $label ); ?></dt>
						<dd class="m-0 text-gray-100!"><?php echo esc_html( $value ); ?></dd>
					</div>
				<?php endforeach; ?>

				<?php if ( $theme->get( 'AuthorURI' ) ) : ?>
					<div class="flex items-center gap-4 px-5 py-3 text-sm">
						<dt class="w-40 shrink-0 font-medium text-gray-500!"><?php esc_html_e( 'Author URI', 'hex' ); ?></dt>
						<dd class="m-0">
							<a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( $theme->get( 'AuthorURI' ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $theme->get( 'AuthorURI' ) ); ?>
							</a>
						</dd>
					</div>
				<?php endif; ?>

				<?php if ( $theme->get( 'ThemeURI' ) ) : ?>
					<div class="flex items-center gap-4 px-5 py-3 text-sm">
						<dt class="w-40 shrink-0 font-medium text-gray-500!"><?php esc_html_e( 'Theme URI', 'hex' ); ?></dt>
						<dd class="m-0">
							<a class="font-medium text-indigo-400! hover:text-indigo-300!" href="<?php echo esc_url( $theme->get( 'ThemeURI' ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $theme->get( 'ThemeURI' ) ); ?>
							</a>
						</dd>
					</div>
				<?php endif; ?>
			</dl>
		</div>
	<?php
	hex_render_admin_shell_end();
}
