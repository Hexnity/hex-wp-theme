# How: The "Page Content" JSON framework

## What was asked

*"now I need new requirement, u have to develop full json framework.
all html pages should follow this and when converting html to wp page
templates, it should follow this framework. it should feed all texts
into json and then should use that for page templates. then json
should be visble in created page. then I can change the content easy."*

Clarified via three `AskUserQuestion` rounds before writing any code
(architectural decisions this big are expensive to redo — see the
Font Library/`site-theme.css` reversals earlier this session for why
that caution exists):

- **Storage**: *"theme should careta [create] and use different db
  table for that. then can do edit."* — a dedicated custom database
  table, not post meta, not a file.
- **Visibility/editing**: *"it should be visible in assigned page."*
  — on the specific page's own Edit Page screen in wp-admin, not a
  separate admin page.
- **Scope for this round**: *"just develop framework and add tables,
  then add gideline to child theme, then I can use it. no need to use
  any template now, but everything should be functional and bug free.
  test always."* — framework + table + child-theme documentation
  only; no pilot template conversion in this pass.

## The architecture

### Storage: one custom table, one row per page

`{$wpdb->prefix}hex_page_content` — `id` (PK), `page_id` (UNIQUE),
`content` (LONGTEXT, the JSON), `created_at`, `updated_at`. One row
per page; `page_id` is the FK-in-spirit back to `wp_posts.ID` (no
formal foreign key — WordPress core tables don't use them either).

Created via `hex_create_page_content_table()` (`inc/page-content.php`),
hooked to `after_switch_theme` (fires whenever this theme, or any
child theme of it, is activated — both the parent's and the active
child's `functions.php` are always loaded, so this hook registration
in the parent fires regardless of which one is "the active theme").
`hex_maybe_upgrade_page_content_table()` (hooked to `admin_init`,
comparing a stored `hex_page_content_db_version` option against
`HEX_PAGE_CONTENT_DB_VERSION`) catches an already-active install that
was never freshly "switched to" since the table was introduced — the
standard WordPress custom-table versioning pattern. `dbDelta()` is
idempotent and only ever adds/modifies what's actually different from
the SQL given to it — safe to call on every admin page load without
performance concern (guarded by the version check, so it's not
literally running on every load, just checked).

**Why a custom table over post meta**: explicit user choice. Post
meta would have been the more "WordPress-idiomatic" default (no new
table, works with existing meta APIs, revisions, etc.) — but the user
specifically asked for "different db table," so that's what this is.
One practical consequence: post meta gets cleaned up automatically by
WordPress core when a post is deleted; a custom table does not, so
`hex_delete_page_content_on_post_delete()` (hooked to
`before_delete_post`, not `wp_trash_post` — trashing a page does NOT
delete its content row, only a real delete does) exists specifically
to replicate that behavior.

### Reading: one function a template calls

```php
$content = hex_get_page_content(); // defaults to get_the_ID()
echo esc_html( $content['hero_heading'] ?? 'Default' );
```

No schema, no registration step for a template's keys — the framework
doesn't know or care what keys a given template uses; that convention
lives entirely in each template's own PHP (and is documented for
future template authors in `../hexnity-wp-child/GUIDELINES.md` §8,
since that's where templates actually get built). This was a
deliberate scope decision: building a per-template JSON *schema*
(so e.g. an auto-generated form could exist) was explicitly ruled out
for this round ("no need to use any template now") — there's nothing
to generate a schema from yet.

### Writing: a meta box, raw JSON, on the page's own Edit Page screen

`hex_register_page_content_meta_box()` adds a "Page Content (JSON)"
box (`page` post type, `normal` context, `high` priority) showing a
single `<textarea>` — the page's current content, pretty-printed
(`hex_get_page_content_raw()` → `hex_encode_page_content_for_display()`,
`JSON_PRETTY_PRINT`, `'{}'` for an unsaved page rather than the
less-intuitive `'[]'` an empty-array encode would produce).

Chose **raw JSON over an auto-generated per-field form** deliberately:
a generated form needs a schema (field names, types, labels) to
generate *from*, and there is no schema — each template defines its
own JSON shape by convention, not registration. A raw textarea is the
only UI that works with zero prior knowledge of what a given page's
content is supposed to contain, and it's what "no need to use any
template now" implied was acceptable for this round. If templates
later want a friendlier per-field UI, that would need each template
to declare its own field list somewhere the meta box could read —
a real, separate follow-up, not attempted here.

`hex_save_page_content_meta_box()` (hooked to `save_post_page`, so it
only ever runs for the `page` post type): nonce
(`hex_save_page_content`) → autosave guard → `edit_page` capability
check → `hex_parse_submitted_page_content()` (pure: JSON-decodes and
validates — rejects malformed JSON, a bare JSON scalar like `"foo"`
or `42` rather than an object/array, or anything over 500KB as a
defensive cap against a pathological paste) → on success,
`hex_save_page_content()`; on failure, a `set_transient()` error
message shown once via `hex_page_content_error_notice()`
(`admin_notices`, scoped to that specific page's screen only) and the
page's *previously saved* content is left completely untouched — an
invalid submission never corrupts or clears existing data.

### Sanitizing: every string leaf, recursively

`hex_sanitize_page_content_value()` walks the decoded JSON (arrays
recurse via `array_map`, so it handles arbitrarily nested
objects/arrays the same way) and runs `wp_kses_post()` on every string
leaf — the same allowance WordPress gives its own post content (safe
HTML like `<strong>`/`<a>`/`<p>` survives; `<script>`/`onclick`/etc.
don't). Non-string scalars (numbers, booleans) and `null` pass through
unchanged. This runs in `hex_save_page_content()`, not in the parse
step — `hex_parse_submitted_page_content()` only validates *shape*
(is this well-formed JSON), sanitizing is a separate concern applied
right before the sanitized-and-now-trusted value gets written to the
table.

### Upsert logic: preserves `created_at`

`hex_save_page_content()` does an explicit `SELECT id ... THEN
INSERT/UPDATE`, not `$wpdb->replace()` — `REPLACE INTO` would delete
and re-insert on every save (changing the row's own `id` and,
critically, requiring `created_at` to be re-supplied every time or
lost), whereas the explicit check preserves the original `created_at`
across every subsequent update and only ever sets it once.

## Testing

`tests/PageContentTest.php` (33 tests) — every pure function fully
covered (`hex_sanitize_page_content_value()`'s recursion and
scalar/null passthrough, `hex_parse_submitted_page_content()`'s
valid/malformed/bare-scalar/oversized cases, `hex_decode_page_content()`,
`hex_encode_page_content_for_display()`'s empty-object fallback), the
DB-touching functions (`hex_get_page_content()`,
`hex_save_page_content()`'s insert-vs-update branching and
sanitize-before-store behavior, `hex_delete_page_content()`) via a
`\Mockery::mock('wpdb')` swapped into the `global $wpdb` for the
duration of each test (confirmed working — Mockery can mock a class
name that isn't even loaded, since it only needs `shouldReceive()`
calls to match, not a real class definition), and the meta-box/notice
functions (`hex_save_page_content_meta_box()`'s nonce/capability/
invalid-JSON/valid-JSON paths via `$_POST` manipulation,
`hex_page_content_error_notice()`'s screen-scoping and
render-then-clear behavior).

**`hex_create_page_content_table()`/`hex_maybe_upgrade_page_content_table()`
are NOT unit tested** — `dbDelta()` requires a real
`wp-admin/includes/upgrade.php` (via `require_once`) and performs a
real schema operation against a real database connection, neither of
which exist in this WP_Mock-based suite. Same documented constraint as
other genuinely I/O-bound functions elsewhere in this theme (see
`knoladge/child-theme-css-token-architecture.md`) — this is a real,
acknowledged gap, not an oversight, and it's the reason table creation
was manually reasoned through (dbDelta's strict SQL formatting rules:
exactly two spaces before `PRIMARY KEY`'s column list, lowercase
native MySQL types) rather than verified by a test run.

**A real regression was caught and fixed while writing these tests**:
adding `sanitize_text_field()`/`wp_strip_all_tags()` as permanent
passthrough stubs in `tests/bootstrap.php` (following the existing
`wp_parse_url()` pattern) broke two *existing* tests
(`CustomizerTest`, `UpdaterTest`) that assert on exactly how many
times `WP_Mock::userFunction('sanitize_text_field'/...)` is called —
a permanent real function definition bypasses WP_Mock's own
interception for that function name entirely, so the mock's call
count silently stayed at zero. Reverted; those two functions are
mocked per-test instead (a `setUp()` passthrough in
`PageContentTest.php`), same as every other test file already does.
`wp_json_encode()`/`wp_unslash()` were added as permanent stubs
successfully — nothing else in the suite asserts call counts against
those two names.

## Files involved

`inc/page-content.php` (the whole framework — table creation/upgrade,
all CRUD, sanitizing, the meta box, the error notice), `functions.php`
(unconditional `require`, since front-end templates need
`hex_get_page_content()` on every request, not just in `is_admin()`),
`tests/PageContentTest.php`, `tests/bootstrap.php` (two new permanent
stubs, `wp_json_encode()`/`wp_unslash()`; two functions deliberately
left un-stubbed, see Testing above), `project.json`.
`../hexnity-wp-child/GUIDELINES.md` §8 documents the convention a
template author should follow when they actually wire a template into
this — that conversion work itself has not happened yet for any
template in this child theme.

## Known gaps / next steps

- No template in this child theme reads from this framework yet
  (explicit scope decision this round — "no need to use any template
  now"). Converting `template-meridian-home.php` (or a future
  template) is real, separate work.
- No admin listing/index of which pages have content saved, or a way
  to browse/search the custom table from wp-admin — not asked for,
  not built. The only entry point is a given page's own Edit Page
  screen.
- No per-field generated form (raw JSON only) — deliberate, see "How
  it was implemented" above; would need each template to declare a
  field schema somewhere, which doesn't exist yet.
- `hex_save_page_content()`/`hex_delete_page_content()` are public
  functions with no caller yet outside the meta box save handler —
  intended for a future programmatic use (e.g. an import/seed script
  that populates a batch of pages' content from a design file), not
  wired to anything else currently.
