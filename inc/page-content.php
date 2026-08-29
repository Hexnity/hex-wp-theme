<?php
/**
 * The "Page Content" JSON framework: every text-bearing value a page
 * template needs is meant to live in one JSON object per page,
 * stored in a dedicated custom table (not post meta, not a file) —
 * `{$wpdb->prefix}hex_page_content`, one row per page (`page_id`
 * UNIQUE), one `content` column holding the JSON. A template reads it
 * with hex_get_page_content(); an admin edits it directly as raw JSON
 * in a meta box on that page's own Edit Page screen — no separate
 * admin page, no per-field form (there's no fixed schema to build one
 * from — each page template defines its own JSON shape by convention,
 * not this framework).
 *
 * Deliberately NOT wired into any template yet — this file is the
 * framework only, per explicit user request ("just develop framework
 * and add tables, then add gideline to child theme, then I can use
 * it. no need to use any template now"). See
 * ../hexnity-wp-child/GUIDELINES.md for the convention a template
 * author should follow when they do wire a template into this.
 *
 * @package Hex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema version for `{$wpdb->prefix}hex_page_content`. Bump this and
 * update hex_create_page_content_table()'s SQL together whenever the
 * table structure changes — hex_maybe_upgrade_page_content_table()
 * re-runs dbDelta() for anyone already on an older version.
 */
define( 'HEX_PAGE_CONTENT_DB_VERSION', '1.0.0' );

/**
 * The custom table's full name, prefixed for the current site (also
 * correct on multisite — uses $wpdb->prefix, not $wpdb->base_prefix).
 *
 * @return string
 */
function hex_page_content_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'hex_page_content';
}

/**
 * Create (or, via dbDelta()'s own diffing, upgrade) the custom table.
 * Safe to call repeatedly — dbDelta() only ever adds/modifies what's
 * actually different from this SQL, never drops data.
 *
 * @return void
 */
function hex_create_page_content_table() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table_name      = hex_page_content_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	// dbDelta() parses this with a strict, regex-based format (exactly
	// two spaces before the PRIMARY KEY's column list, lowercase native
	// MySQL column types) -- do not reformat.
	$sql = "CREATE TABLE {$table_name} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  page_id bigint(20) unsigned NOT NULL,
  content longtext NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY page_id (page_id)
) {$charset_collate};";

	dbDelta( $sql );

	update_option( 'hex_page_content_db_version', HEX_PAGE_CONTENT_DB_VERSION );
}
add_action( 'after_switch_theme', 'hex_create_page_content_table' );

/**
 * Catch anyone already running this theme (or this child theme) from
 * before the table existed, or before a later schema-version bump —
 * after_switch_theme only fires at the moment of activation, so an
 * existing, already-active install would otherwise never create/
 * upgrade the table on its own.
 *
 * @return void
 */
function hex_maybe_upgrade_page_content_table() {
	if ( get_option( 'hex_page_content_db_version' ) !== HEX_PAGE_CONTENT_DB_VERSION ) {
		hex_create_page_content_table();
	}
}
add_action( 'admin_init', 'hex_maybe_upgrade_page_content_table' );

/**
 * Recursively sanitize a decoded JSON value before it's stored —
 * every string leaf goes through wp_kses_post() (the same allowance
 * WordPress gives its own post content: safe HTML like <strong>/<a>/
 * <p> survives, <script>/onclick/etc. don't), arrays/objects recurse,
 * every other scalar (number, bool, null) passes through unchanged.
 * Pure function: no I/O.
 *
 * @param mixed $value A JSON-decoded value (string, number, bool, null, or nested array).
 * @return mixed The same shape, with every string leaf sanitized.
 */
function hex_sanitize_page_content_value( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'hex_sanitize_page_content_value', $value );
	}

	if ( is_string( $value ) ) {
		return wp_kses_post( $value );
	}

	if ( is_scalar( $value ) || null === $value ) {
		return $value;
	}

	// Objects/resources shouldn't occur from json_decode(..., true), but
	// don't store something unpredictable if one somehow does.
	return null;
}

/**
 * Parse and validate a raw JSON submission for a page's content — the
 * meta box save handler's only real logic, factored out as a pure
 * function (no I/O, no superglobal access) so it's directly testable.
 * Deliberately does NOT sanitize (that's hex_sanitize_page_content_value(),
 * applied separately in hex_save_page_content()) — this only answers
 * "is this well-formed enough to attempt saving at all."
 *
 * @param string $raw Raw JSON string, as submitted.
 * @return array|null The decoded content array, or null if the input isn't valid JSON, isn't a JSON object/array, or is implausibly large.
 */
function hex_parse_submitted_page_content( $raw ) {
	$raw = (string) $raw;

	// A defensive cap, not a meaningful content limit -- guards against a
	// pathological paste, not real page content (LONGTEXT itself allows
	// far more than this).
	if ( strlen( $raw ) > 500000 ) {
		return null;
	}

	$decoded = json_decode( $raw, true );

	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
		return null;
	}

	return $decoded;
}

/**
 * Decode a page's stored `content` column value back into an array.
 * Pure function: no I/O.
 *
 * @param string $raw_json The table's raw `content` column value.
 * @return array Decoded content, or an empty array if it isn't valid JSON.
 */
function hex_decode_page_content( $raw_json ) {
	$decoded = json_decode( (string) $raw_json, true );

	return is_array( $decoded ) ? $decoded : array();
}

/**
 * Pretty-print a content array back into the JSON shown in the admin
 * textarea — '{}' for no content yet, rather than json_encode(array())'s
 * '[]', since an empty JSON *object* reads as the more natural "nothing
 * has been added here yet" starting point for a page author to type
 * into. Pure function: no I/O.
 *
 * @param array $content A page's content array, e.g. from hex_get_page_content().
 * @return string Pretty-printed JSON.
 */
function hex_encode_page_content_for_display( array $content ) {
	if ( ! $content ) {
		return '{}';
	}

	$json = wp_json_encode( $content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

	return false !== $json ? $json : '{}';
}

/**
 * The JSON content assigned to a page, decoded — what a page template
 * calls to actually read its content, e.g.
 * `$content = hex_get_page_content(); echo esc_html( $content['hero_heading'] ?? '' );`.
 *
 * @param int|null $page_id A page/post ID, or null to use the current post in the loop (get_the_ID()).
 * @return array Decoded content, keyed however that page's JSON was written; empty array if the page has no content saved yet.
 */
function hex_get_page_content( $page_id = null ) {
	$page_id = null === $page_id ? (int) get_the_ID() : (int) $page_id;

	if ( ! $page_id ) {
		return array();
	}

	global $wpdb;
	$table = hex_page_content_table_name();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- no WP API for a theme-owned custom table; $table is our own prefix + a hardcoded name, never user input (not interpolatable via a %-placeholder — $wpdb->prepare() has no table-name placeholder).
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT content FROM {$table} WHERE page_id = %d", $page_id ) );

	if ( ! $row || ! isset( $row->content ) ) {
		return array();
	}

	return hex_decode_page_content( $row->content );
}

/**
 * The raw (pretty-printed) JSON for a page — what the admin meta box
 * textarea shows.
 *
 * @param int $page_id A page/post ID.
 * @return string Pretty-printed JSON, '{}' if the page has no content saved yet.
 */
function hex_get_page_content_raw( $page_id ) {
	return hex_encode_page_content_for_display( hex_get_page_content( $page_id ) );
}

/**
 * Save a page's content — sanitizes every string leaf
 * (hex_sanitize_page_content_value()), then upserts the row (an
 * explicit SELECT-then-insert/update, not $wpdb->replace(), so an
 * existing row's `created_at` is preserved rather than reset on every
 * save).
 *
 * @param int   $page_id A page/post ID.
 * @param array $content Decoded content to save, e.g. from hex_parse_submitted_page_content().
 * @return bool True on success.
 */
function hex_save_page_content( $page_id, array $content ) {
	$page_id = (int) $page_id;

	if ( ! $page_id ) {
		return false;
	}

	$json = wp_json_encode( hex_sanitize_page_content_value( $content ) );

	if ( false === $json ) {
		return false;
	}

	global $wpdb;
	$table = hex_page_content_table_name();
	$now   = current_time( 'mysql' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- no WP API for a theme-owned custom table; $table is our own prefix + a hardcoded name, never user input.
	$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE page_id = %d", $page_id ) );

	if ( $existing_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- no WP API for a theme-owned custom table.
		$result = $wpdb->update(
			$table,
			array(
				'content'    => $json,
				'updated_at' => $now,
			),
			array( 'page_id' => $page_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- no WP API for a theme-owned custom table.
		$result = $wpdb->insert(
			$table,
			array(
				'page_id'    => $page_id,
				'content'    => $json,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	return false !== $result;
}

/**
 * Permanently remove a page's content row, if any.
 *
 * @param int $page_id A page/post ID.
 * @return bool True on success (including "there was nothing to delete").
 */
function hex_delete_page_content( $page_id ) {
	$page_id = (int) $page_id;

	if ( ! $page_id ) {
		return false;
	}

	global $wpdb;
	$table = hex_page_content_table_name();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- no WP API for a theme-owned custom table.
	return false !== $wpdb->delete( $table, array( 'page_id' => $page_id ), array( '%d' ) );
}

/**
 * Clean up a page's content row when the page itself is permanently
 * deleted (trashing does NOT trigger this — only an actual delete).
 *
 * @param int $post_id The post being deleted.
 * @return void
 */
function hex_delete_page_content_on_post_delete( $post_id ) {
	hex_delete_page_content( $post_id );
}
add_action( 'before_delete_post', 'hex_delete_page_content_on_post_delete' );

/**
 * Register the "Page Content (JSON)" meta box on the Edit Page screen.
 *
 * @return void
 */
function hex_register_page_content_meta_box() {
	add_meta_box(
		'hex_page_content',
		__( 'Page Content (JSON)', 'hex' ),
		'hex_render_page_content_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'hex_register_page_content_meta_box' );

/**
 * Render the meta box: a single raw-JSON textarea, pre-filled with
 * this page's current content (pretty-printed).
 *
 * @param WP_Post $post The page being edited.
 * @return void
 */
function hex_render_page_content_meta_box( $post ) {
	wp_nonce_field( 'hex_save_page_content', 'hex_page_content_nonce' );
	?>
	<p class="description">
		<?php esc_html_e( 'Structured content for this page, as JSON — read by the active page template via hex_get_page_content(). Must be valid JSON to save; an invalid submission is rejected and this page\'s previously saved content is left untouched.', 'hex' ); ?>
	</p>
	<textarea
		id="hex_page_content_json"
		name="hex_page_content_json"
		rows="16"
		style="width:100%;font-family:monospace;"
	><?php echo esc_textarea( hex_get_page_content_raw( $post->ID ) ); ?></textarea>
	<?php
}

/**
 * Save the meta box on page save — nonce/capability/autosave guards,
 * then hex_parse_submitted_page_content() + hex_save_page_content().
 * An invalid submission is rejected with an admin notice
 * (hex_page_content_error_notice()) rather than silently discarded or
 * allowed to corrupt the stored content.
 *
 * @param int $post_id The page being saved.
 * @return void
 */
function hex_save_page_content_meta_box( $post_id ) {
	if ( ! isset( $_POST['hex_page_content_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hex_page_content_nonce'] ) ), 'hex_save_page_content' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['hex_page_content_json'] ) ) {
		return;
	}

	$raw     = wp_unslash( $_POST['hex_page_content_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- structured JSON, not a simple string field: validated by hex_parse_submitted_page_content() (rejects anything malformed) and every string leaf is sanitized via wp_kses_post() in hex_sanitize_page_content_value() before hex_save_page_content() ever stores it -- no sanitize_*() call would be correct to apply to the raw JSON text itself.
	$decoded = hex_parse_submitted_page_content( $raw );

	if ( null === $decoded ) {
		set_transient( 'hex_page_content_error_' . $post_id, __( 'Page Content was not saved — the JSON was invalid or too large.', 'hex' ), 60 );
		return;
	}

	hex_save_page_content( $post_id, $decoded );
}
add_action( 'save_post_page', 'hex_save_page_content_meta_box' );

/**
 * Show (and clear) the "invalid JSON" admin notice set by
 * hex_save_page_content_meta_box() above, on that same page's Edit
 * Page screen only.
 *
 * @return void
 */
function hex_page_content_error_notice() {
	$screen = get_current_screen();

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display check (which page's error to show), not a state change.

	if ( ! $post_id ) {
		return;
	}

	$message = get_transient( 'hex_page_content_error_' . $post_id );

	if ( ! $message ) {
		return;
	}

	delete_transient( 'hex_page_content_error_' . $post_id );

	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}
add_action( 'admin_notices', 'hex_page_content_error_notice' );
