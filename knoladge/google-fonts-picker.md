# How: Admin-configurable Google Fonts picker (no API key)

## What was asked

*"use font selector (searchable) using google fonts. no need to use
api. Just add option to google font urls and then the added font
should list in font selectors"* — plus the user pasted a real Google
Fonts embed snippet as the expected input shape (two `<link
rel="preconnect">` tags + one `<link rel="stylesheet" href="...css2?
family=...">`).

## How it was implemented

- New file `inc/google-fonts.php` (loaded unconditionally from
  `functions.php`, since the front-end enqueue needs to run on every
  request): `hex_sanitize_google_fonts_urls()`,
  `hex_get_google_fonts_urls()`, `hex_get_google_font_families()`,
  `hex_enqueue_google_fonts()`, `hex_google_fonts_resource_hints()`,
  `hex_render_google_fonts_datalist()`.
- **No API call, ever.** The admin pastes the whole embed snippet (or
  just the bare stylesheet URL) into a textarea
  (`hex_google_fonts_urls` option, registered under the existing
  `hex_style_options` settings group so it saves in the same form
  submit as the rest of Theme Options).
  `hex_sanitize_google_fonts_urls()` regexes out only genuine
  `https://fonts.googleapis.com/css2?...` substrings — this is what
  lets the admin paste the *entire* 3-line snippet including the two
  preconnect tags: they're simply not `css2?` URLs, so they're
  silently discarded, and only the real stylesheet URL(s) survive.
  Anything not on that exact host is rejected outright (prevents an
  admin — or a future compromised admin account — from getting an
  arbitrary third-party stylesheet enqueued site-wide).
- `hex_get_google_font_families()` parses the family **names** back
  out of each stored URL's `family=` query parameter(s) — a URL can
  legally contain several (`&family=A&family=B`), and Google's own
  compact syntax appends an axis spec after a colon
  (`Inter:ital,wght@0,400`), which is stripped to leave just `Inter`.
  Deduped by name, in first-seen order.
- **The "searchable selector"** is a native HTML `<datalist>` — zero
  JS, zero extra dependency. Every `'font'`-type Theme Options field
  (`hex_render_style_field()`'s `'font'` case, in
  `inc/admin/settings.php`) is a plain `<input type="text"
  list="hex-google-fonts-list">`; `hex_render_google_fonts_datalist()`
  prints the shared `<datalist id="hex-google-fonts-list">` once per
  page load, populated from whatever families are currently
  configured. Typing filters the browser's native suggestion list
  (that's what makes it "searchable"), while still allowing any free
  text (a hand-typed system font stack still works, unaffected).
- Front-end delivery: `hex_enqueue_google_fonts()` (hooked
  `wp_enqueue_scripts`) enqueues each stored URL directly via
  `wp_enqueue_style()` with `$ver = null` (an external URL isn't ours
  to version). `hex_google_fonts_resource_hints()` (hooked
  `wp_resource_hints`) adds the same two preconnect hints Google's own
  snippet uses — via WP core's own resource-hints filter rather than
  hand-printing `<link>` tags in `wp_head`, and only when at least one
  font URL is configured.
- The "Google Fonts" textarea + a chip list of the families it
  currently resolves to (`hex_render_google_fonts_field()`, in
  `inc/admin/settings.php`) is rendered once, at the top of the
  Typography panel only (`inc/admin/page-theme-options.php` checks
  `'typography' === $group`) — it's a font-system control, not a
  design token itself, so it isn't part of `hex_get_style_schema()`.

## Testing

`tests/GoogleFontsTest.php` (18 tests) covers: sanitizing a bare URL,
extracting just the stylesheet URL out of a full pasted embed
snippet, rejecting a non-Google host, deduping repeated/multiple URLs,
reading/splitting the stored option, parsing one or several families
out of one URL, decoding `+`-separated multi-word names, deduping the
same family across URLs, the front-end enqueue (per-URL, and doing
nothing when unconfigured), the resource-hints filter (adds hints /
leaves other relation types alone / adds nothing when unconfigured),
and the rendered `<datalist>` markup. `wp_parse_url()` needed a
permanent bootstrap passthrough stub (delegating to native
`parse_url()`) alongside the existing `add_action`/`add_filter` ones —
added to `tests/bootstrap.php`.
