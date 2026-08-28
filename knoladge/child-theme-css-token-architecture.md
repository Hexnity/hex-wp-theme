# How: design tokens live in a child-theme CSS file, with auto-discovery

## What was asked

*"how these theme options work? it should fetch from separate css in
childtheme. so then when Im doing something in child theme it should
visible in this theme options"* — followed by, once the direction was
confirmed: *"no db option doesnt work. it should be css file and while
developing child theme if any value doesnt belong to these availabel
stuffs, then should create new css class and then it should show in
this options panel. develop full architecture how these should create
and how it automatically detect this admin page and show. then
document it then next time while child theme development it should
follow."*

Before this change, every Theme Options field (~146 design tokens —
typography, spacing, colors, buttons, forms, cards, sections, radius,
tables, alerts, badges, icons) was a row in `wp_options`, saved through
WordPress's Settings API. An active child theme was only a *permission
gate* for editing (`hex_is_child_theme_active()` just calls
`is_child_theme()`) — the values themselves never touched any theme
file. This document describes the replacement: values now live in a
CSS file inside the active child theme, and a child-theme developer's
own hand-added tokens are auto-detected and become editable admin
fields.

## The architecture

### Storage: one file per child theme

`hex_style_tokens_file_path()` (`inc/style-settings.php`) always points
at `{active child theme}/theme-options.css` —
`get_stylesheet_directory() . '/theme-options.css'`. This is the
*active* theme's own directory, which is only meaningful (and only
readable/writable by the admin) while `hex_is_child_theme_active()` is
true — matching the scope editing has always been gated on.

The file holds one `:root { --hex-key: value; ... }` block. It's fully
regenerated on every Theme Options save (not diff-patched) — this is
safe and lossless because every form field is always pre-populated
from the same source (`hex_get_style_value()`) it gets written back
to, so a save can never drop a value nobody touched.

**The file is dual-owned**: the admin UI manages it, but so can a
child-theme developer, by hand, at any time — that duality is the
whole point (see "Adding a new token" below).

### Discovery: a pure regex parser, merged with the static schema

`hex_parse_style_tokens_css( string $css ): array` is a **pure
function** — no I/O — that regexes every `--hex-{key}: {value};`
declaration out of a CSS string. `hex_get_child_theme_tokens()` is the
thin function that actually reads the file (only when a child theme is
active and the file exists) and hands its contents to the parser.

`hex_merge_style_schema_with_tokens( array $schema, array $tokens ):
array` (also pure) is where discovery happens: for every key found in
the file that *isn't* already in the static `hex_get_style_schema()`,
it adds a new entry —

```php
array(
	'group'   => 'custom',
	'type'    => hex_guess_style_type( $value ), // see below
	'default' => $value,
	'label'   => hex_style_humanize_key( $key ), // 'hero_overlay' -> 'Hero Overlay'
)
```

`hex_get_effective_style_schema()` / `hex_get_effective_style_groups()`
are what every admin-facing function actually calls — the static
schema plus whatever's been auto-detected. The "Custom Tokens" tab
only appears when at least one such field exists, so a plain-parent
install with no child-theme extensions never shows an empty tab.

**One key never gets auto-detected, on purpose**: any key that's a
`hex_get_fluid_size_pairs()` *output* (`h1_size`, `h2_size`, ...,
`meta_size` — see `knoladge/fluid-typography-clamp.md`) is skipped
even if the file has it. Those keys are derived-only —
`hex_build_style_tokens_css()` always recomputes them from their
`{key}_size_mobile`/`_desktop` pair and never writes them verbatim —
so surfacing one as an editable "Custom Tokens" field would be a
trap: it would look editable, accept a save, and then silently be
discarded on the very next rebuild. This actually happened: a
`theme-options.css` saved before the 1.5.6 mobile/desktop split
still had a flat `--hex-h1-size: ...;` in it, which the 1.5.6 code
didn't recognize as belonging to the reserved key set, so it got
exposed as a "Custom Tokens" field — the user edited it, saved, and
watched the value disappear every time. Fixed in
`hex_merge_style_schema_with_tokens()` by excluding any key present
in `hex_get_fluid_size_pairs()`'s output set before merging.

**Type guessing (`hex_guess_style_type()`)** tries, in order, the
sanitizers that are safe to guess:

1. Passes `hex_sanitize_style_color()` → `color`.
2. Matches the length pattern (a number + `rem`/`em`/`px`/`%`, or bare
   `0`) → `length`.
3. Matches a bare decimal → `number`.
4. Contains a comma and passes `hex_is_safe_font_value()` → `font`.
5. Otherwise → `custom` (a plain text field, sanitized narrowly —
   see below).

`weight` and `shadow` are **never guessed** — both are closed
enumerations (`100`–`900` step 100; `none|sm|md|lg|xl`) syntactically
indistinguishable from an arbitrary short string, so a wrong guess
could silently coerce a real value into a `<select>` that can't
represent it. A hand-added token that's conceptually a weight or a
shadow will render as a plain `custom` text field — safe, just a
little less polished. That's an accepted trade-off.

### Saving: a dedicated admin-post handler, not the Settings API

`inc/admin/page-theme-options.php`'s form posts to `admin-post.php`
(`action=hex_save_style_options`), not `options.php` — matching the
convention already used for the Updates/Child Theme pages'
actions (`inc/admin/handlers.php`). `hex_handle_save_style_options()`:

1. Checks capability (`edit_theme_options`) and the nonce.
2. **Hard-checks `hex_is_child_theme_active()` server-side** — before
   this change, the *only* thing preventing a save with no active
   child theme was an HTML `disabled` attribute on the fieldset; this
   closes that gap.
3. Sanitizes every submitted value via `hex_sanitize_submitted_style_tokens()`
   (a pure function: given `$submitted`, the effective `$schema`, and
   the `$current` values, it returns the sanitized token set plus a
   list of any field labels that got rejected and fell back to their
   current value).
4. Also sanitizes and `update_option()`s the Google Fonts field in the
   same request — its repeater lives in this same `<form>`, so it has
   to be handled here even though it's unrelated to design tokens and
   stays DB-backed (it's a list of URLs, not a CSS value).
5. Builds the full file text via `hex_build_style_tokens_css()` (also
   pure — re-validates every value by type, resolves shadow keywords
   to real CSS, silently drops anything that fails so one bad value
   can't corrupt the rest of the file) and writes it with
   `WP_Filesystem` (bootstrapped via `wp-admin/includes/file.php`).
6. Reports success (or which fields were rejected) via the same
   `set_transient()` + redirect pattern every other admin action in
   this dashboard uses — the `settings_errors()`/Settings-API-driven
   error display is gone from this page entirely.

**The sanitize functions themselves are pure now.** Each
`hex_sanitize_style_*( $value )` returns the sanitized string, or
`null` on failure — no more `add_settings_error()` call, no more
`hex_style_revert_from_filter()`'s `current_filter()`-sniffing trick
(that trick only worked because these functions used to be wired as
literal WP `sanitize_option_{name}` filter callbacks; they aren't
anymore). The caller (`hex_sanitize_submitted_style_tokens()`) decides
what "revert to the current value" means — a cleaner separation, and
it's what makes these functions trivially unit-testable.

### The new `custom` type's sanitizer

`hex_sanitize_style_custom()` is deliberately narrower than "any CSS
value": `/^[a-zA-Z0-9 .,+\-\/#%()]+$/`, no `url(` substring (case
insensitive), non-empty, capped at 200 characters. Specifically:

- **No `:` at all** — kills `javascript:`/`data:` pseudo-protocols and
  any `url(http://...)` construction outright, without needing a
  protocol blacklist.
- **No quotes** — a value that needs quotes is a font-family list;
  those should use the `font` type instead of growing this charset to
  also reason about quote-balancing.
- **`url(` is rejected even though parens are allowed** — parens are
  needed for legitimate values like `rgba(0,0,0,.5)` or `calc(...)`; a
  hand-typed token that genuinely needs `url()` (e.g. a background
  image) is a known, accepted limitation of the generic type.

### Front end: enqueue the file directly, no PHP output

`hex_enqueue_child_theme_tokens()` (`wp_enqueue_scripts`) just
`wp_enqueue_style()`s the child theme's `theme-options.css` file
directly (versioned by `filemtime()`) — when a child theme is active
and the file exists. Nothing is printed inline anymore (the old
`hex_render_style_css_vars()` and its `<style id="hex-style-vars">`
block are gone).

This is *safer* than the old inline-`<style>` approach, not riskier:
the file is served as a real stylesheet resource via `<link>`, parsed
strictly as CSS by the browser — it's never inlined into the HTML
document text, so there's no "escaping out of an inline tag" surface
at all. The values were already sanitized once when the admin saved
them (or, for hand-edited files, the developer already has filesystem
access — the same trust boundary as editing any other theme file).

When no child theme is active, or the file doesn't exist yet, nothing
is enqueued — every `var(--hex-key, default)` usage in
`assets/css/src/site-theme.css` already carries its own independent
CSS-level fallback, so the front end renders correctly regardless.

## Adding a new token — the workflow this whole thing exists for

You're building a child theme and want a new design token that isn't
in the ~146-field schema (say, a hero section's overlay opacity):

1. **Either** open the active child theme's `theme-options.css` and
   hand-add a line inside the `:root { ... }` block:
   ```css
   --hex-hero-overlay-opacity: 0.6;
   ```
   **or** just don't — reload Theme Options, add any value to any
   existing field, and save once; the file gets created for you (even
   if empty of custom tokens the first time).
2. Reference it in your own CSS the same way every existing token is
   referenced: `background-color: rgba(0, 0, 0, var(--hex-hero-overlay-opacity, 0.6));`
   — always give it a literal fallback, matching every other usage in
   `site-theme.css`.
3. Reload the Theme Options page. Your new token is now under a
   **"Custom Tokens"** tab, editable like any other field, its type
   guessed automatically (see above).
4. From then on, it round-trips normally: editing it in the admin and
   saving rewrites the file with your new value.

No parent-theme PHP file needs to change for this. If a guessed type
is wrong for your use case (e.g. you wanted a `<select>` because the
value is really a fixed enum), that's not supported yet — the field
will always render as whatever `hex_guess_style_type()` decides.

## Testing

`tests/StyleSettingsTest.php` — the pure functions
(`hex_parse_style_tokens_css()`, `hex_guess_style_type()`,
`hex_merge_style_schema_with_tokens()`, `hex_build_style_tokens_css()`,
every `hex_sanitize_style_*()`, `hex_sanitize_submitted_style_tokens()`)
are fully unit tested with literal array/string inputs — no mocking
needed for most of them. `hex_get_child_theme_tokens()`,
`hex_get_effective_style_schema()`, `hex_get_effective_style_values()`,
and `hex_get_style_value()` are tested only for the "no child theme
active" path (mocking WP core's `is_child_theme()` to `false`, which
short-circuits before any file access) — this is a genuine constraint
of this test suite, not an oversight: `tests/bootstrap.php` `require`s
every `inc/*.php` file once, globally, with no PHP namespaces, so
WP_Mock can only mock names that are never actually declared in this
codebase (WP core functions) — it cannot mock a project-defined
function like `hex_get_child_theme_tokens()` itself, or native
filesystem functions (`file_exists()`/`file_get_contents()`). The new
admin-post handler (`hex_handle_save_style_options()`) and the
enqueue function are filesystem-touching orchestration and are
**deliberately not unit-tested**, the same tier as
`hex_install_child_theme_from_repo()`/`hex_perform_child_theme_update()`
in `inc/child-theme.php` (see `knoladge/wp-mock-unit-testing.md`).

Note: `hex_get_child_theme_tokens()` and `hex_get_effective_style_schema()`
are **not** static-cached, on purpose, even though they're each called
many times per admin page render (~150+). A per-request static cache
was considered and rejected: this test suite runs every test in one
PHP process, so a function-static variable would leak a cached value
across unrelated tests with different mocked scenarios. Since this is
admin-only UI code (not a hot front-end path), re-parsing a small file
per call is cheap enough that correctness/testability won out over the
marginal performance gain.

## Related

[[child-theme]] (features/) — the parent feature this hooks into
(validated fetch-and-install of a child theme from GitHub).
[[design-system]] (features/) — the front-end class reference; its
Theme Options section now points here for storage detail.
[[tailwind-cascade-layers-vs-wp-admin]] — an unrelated but similarly
"looks simple, isn't" CSS gotcha in the same admin dashboard.
