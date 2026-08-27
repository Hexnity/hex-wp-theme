# Feature: Page Templates (Default / Full Width / Canvas)

## What it is

The theme exposes exactly three selectable Page Attributes templates,
per an explicit user requirement — no more, no fewer:

| Template | File | Header/Footer | Title |
|---|---|---|---|
| Default | `template-default.php` | Yes | Yes |
| Full Width | `template-full-width.php` | Yes | No |
| Canvas | `template-canvas.php` | No | No |

`page.php` is the implicit WordPress fallback for pages with no
template explicitly selected, and renders identically to Default.

## How it works

- `hex_get_page_templates()` in `inc/setup.php` is the **single source
  of truth** for the file → label map. Anything that needs to know
  about "the three templates" (tests, docs) reads from here rather
  than hardcoding the list a second time.
- Each template file registers itself with WordPress via a
  `Template Name:` header comment (the standard WP Page Attributes
  mechanism) — `hex_get_page_templates()` doesn't register them
  itself, it documents/validates what the header comments already
  declare.
- `hex_should_show_title()` (in `inc/template-tags.php`) — returns
  `false` only when the current page's template is
  `template-full-width.php` or `template-canvas.php`. Used by
  `template-parts/content-page.php` (shared by `page.php` and
  `template-default.php`) and directly by `template-full-width.php`'s
  content-page include.
- `hex_should_show_chrome()` — returns `false` only for
  `template-canvas.php`. `template-canvas.php` doesn't actually call
  this helper — it simply never calls `get_header()`/`get_footer()`
  and builds its own minimal `<html>` shell instead, since a template
  file either includes the header/footer or it doesn't; the helper
  exists mainly so other code (e.g. a future template-part) can ask
  "should chrome show here" without hardcoding the template filename.
- `template-full-width.php` additionally swaps the `.site-content`
  wrapper class to `.site-content.is-full-width`, which drops the
  `max-width` constraint in `style.css`.

## Files involved

- `inc/setup.php` — `hex_get_page_templates()`.
- `inc/template-tags.php` — `hex_should_show_title()`, `hex_should_show_chrome()`.
- `template-default.php`, `template-full-width.php`, `template-canvas.php`.
- `template-parts/content-page.php` — shared title+content markup for Default/page.php.
- `page.php` — implicit fallback, same rendering as Default.

## Tests

- `tests/TemplateTagsTest.php` — every combination of `is_page()` /
  `get_page_template_slug()` for all three templates, for both
  `hex_should_show_title()` and `hex_should_show_chrome()`.
- `tests/ThemeFilesTest.php::test_every_declared_page_template_file_exists_with_matching_template_name_header`
  — regression test that catches drift between
  `hex_get_page_templates()` and the actual `Template Name:` headers
  in the three files, so the map and the files can never silently
  disagree.

## Known gaps / next steps

- Not yet visually verified in the WordPress admin Page Attributes
  dropdown or on a rendered page (no live-site smoke test performed).
