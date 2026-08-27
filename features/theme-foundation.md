# Feature: Theme Foundation

## What it is

The base theme bootstrap that every page of the site depends on: theme
supports, navigation menus, asset loading, the footer widget area, the
Customizer footer-text setting, and baseline security hardening.

## Status

Implemented, unit-tested, linted clean. Not yet activated/viewed on a
live site.

## Files involved

- `functions.php` — defines `HEX_VERSION`, `HEX_THEME_DIR`, `HEX_THEME_URI`; requires every file in `inc/`.
- `inc/setup.php` — `hex_setup()` (theme supports: title-tag, post-thumbnails, custom-logo, html5, align-wide, responsive-embeds, custom-background; registers the `primary` and `footer` nav menus), `hex_content_width()` (sets `$content_width`, filterable via `hex_content_width`).
- `inc/enqueue.php` — `hex_enqueue_assets()` enqueues `style.css` (header only), vendored `assets/vendor/animate.min.css`, compiled `assets/css/tailwind.css`, and `assets/js/navigation.js` (all versioned by `HEX_VERSION`); conditionally enqueues `comment-reply` on commentable singular views. See [[brand-styling]] for the Tailwind/Animate.css build itself.
- `inc/widgets.php` — registers the `footer-1` sidebar, rendered in `footer.php`.
- `inc/customizer.php` — adds a "Layout Options" Customizer section with a single `hex_footer_text` text setting, sanitized by `hex_sanitize_footer_text()` (strips all HTML, then `sanitize_text_field()`). Used in `footer.php` in place of the default copyright line when set.
- `inc/security.php` — `hex_remove_version_generator()` blanks the WP version generator meta tag; `hex_remove_pingback_header()` strips the `X-Pingback` response header.
- `header.php` / `footer.php` — the shared chrome every template except Canvas includes via `get_header()` / `get_footer()`.
- `assets/js/navigation.js` — accessible mobile menu toggle for `#site-navigation .menu-toggle`.

## Tests

- `tests/SetupTest.php` — `hex_content_width()` applies the `hex_content_width` filter correctly.
- `tests/CustomizerTest.php` — `hex_sanitize_footer_text()` strips HTML and coerces non-string input.
- `tests/SecurityTest.php` — generator tag blanked; `X-Pingback` removed without touching other headers.

## Known gaps / next steps

- No live-site smoke test yet (theme not activated in wp-admin).
- No block-editor (Gutenberg) specific styling/support has been added beyond `align-wide`.
