# Feature: Page Content JSON Framework

## What it is

A framework for keeping a page template's text content out of
hardcoded PHP/HTML and in one editable JSON object per page instead —
so a page's copy can be changed from wp-admin without touching a
template file. Explicit user request: *"u have to develop full json
framework. all html pages should follow this and when converting html
to wp page templates, it should follow this framework. it should feed
all texts into json and then should use that for page templates. then
json should be visble in created page. then I can change the content
easy."*

## Status

Implemented and unit-tested (framework only — see Known gaps below).
**Not yet used by any page template in the active child theme** — this
round was scoped to the framework and its documentation, explicitly
not a template conversion (*"just develop framework and add tables,
then add gideline to child theme, then I can use it. no need to use
any template now"*).

## How it works

- **Storage**: a dedicated custom database table,
  `{$wpdb->prefix}hex_page_content` — one row per page (`page_id`
  UNIQUE), one `content` column holding the JSON, `created_at`/
  `updated_at`. Explicit user choice over the more WordPress-idiomatic
  post-meta approach ("theme should careta and use different db
  table"). Created via `hex_create_page_content_table()`
  (`dbDelta()`), hooked to `after_switch_theme`; an already-active
  install is caught by `hex_maybe_upgrade_page_content_table()`
  (`admin_init`, version-gated via the `hex_page_content_db_version`
  option) so the table exists even without a fresh theme activation.
- **Reading**: a page template calls `hex_get_page_content( $page_id = null )`
  (defaults to the current post in the loop) — returns the decoded
  JSON as an array, or `array()` if nothing's saved yet. No schema, no
  registration: each template defines its own JSON key convention on
  its own.
- **Writing**: a "Page Content (JSON)" meta box on every page's own
  Edit Page screen — a raw JSON `<textarea>`, pretty-printed, `'{}'`
  for an unsaved page. Chosen over an auto-generated per-field form
  because there's no schema to generate a form *from* (each template
  decides its own shape) — explicit user answer: *"it should be
  visible in assigned page."*
- **Validation & sanitizing**: an invalid JSON submission is rejected
  outright (admin notice, previous content left untouched — never
  silently corrupted). A valid submission has every string leaf run
  through `wp_kses_post()` (same allowance WordPress gives its own
  post content) before being stored.
- **Cleanup**: a page's content row is deleted when the page itself is
  permanently deleted (`before_delete_post`, not on trash).

## Where each piece lives

`inc/page-content.php` — the entire framework (table
creation/upgrade, `hex_get_page_content()`/`hex_save_page_content()`/
`hex_delete_page_content()`, the pure sanitize/parse/encode helpers,
the meta box, the error notice). Loaded unconditionally from
`functions.php` (front-end templates need read access on every
request, not just in `is_admin()`).

## Convention for template authors

Documented in `../hexnity-wp-child/GUIDELINES.md` §8 (that's where
templates actually get built, not this parent theme) — pick a flat,
descriptive JSON key per text field, call `hex_get_page_content()`
near the top of the template, read each key with `?? 'fallback'`,
always escape on output (`esc_html()`/`esc_url()`/`wp_kses_post()` as
appropriate — the framework's own sanitizing on save is not a
substitute for a template's own output escaping).

## Tests

`tests/PageContentTest.php` (33 tests) — every pure function
(`hex_sanitize_page_content_value()`'s recursive sanitizing,
`hex_parse_submitted_page_content()`'s valid/malformed/bare-scalar/
oversized-input handling, `hex_decode_page_content()`,
`hex_encode_page_content_for_display()`'s empty-object fallback), the
DB-touching functions via a `\Mockery::mock('wpdb')` swapped into
`global $wpdb`, and the meta-box save/notice handlers via `$_POST`/
`$_GET` manipulation. `hex_create_page_content_table()`/
`hex_maybe_upgrade_page_content_table()` are NOT unit tested —
`dbDelta()` needs a real WP install and database connection, neither
of which exist in this WP_Mock-based suite; see
`knoladge/page-content-json-framework.md` for the full reasoning and
a regression this testing work caught and fixed in `tests/bootstrap.php`
along the way.

## Known gaps / next steps

- No page template reads from this framework yet — a real conversion
  (e.g. `template-meridian-home.php`) is separate, future work.
- No admin listing/browser for which pages have content saved — the
  only entry point is a given page's own Edit Page screen.
- Raw JSON only, no generated per-field form — would need each
  template to declare its own field schema somewhere the meta box
  could read, which doesn't exist yet.

See `knoladge/page-content-json-framework.md` for the full
architecture, the three `AskUserQuestion` rounds that shaped these
decisions, and implementation detail.