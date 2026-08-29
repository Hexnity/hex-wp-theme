# How: Admin-configurable Google Fonts picker (no API key)

## ⚠️ Superseded (2026-08-29) — read this first

**Everything below describes a system that has been removed.** The
free-text "paste a Google Fonts embed link" picker this doc covers
(`body_font_family`/`heading_font_family` fields, the
`hex_google_fonts_urls` option, the repeater UI, the `<datalist>`) was
deleted entirely, replaced by the **Font Library** (four
dropdown-only fields — Heading/Body/Accent/Mono — backed by a
hardcoded common-fonts list; see `features/design-system.md`'s "Font
Library" section and `inc/google-fonts.php`'s current contents).
Explicit user request: *"keep only that option and remove all other
font options."* See the "Removed (2026-08-29)" section at the bottom
of this file for what changed and why. Kept here as historical record
of how the original picker worked and why it was built that way —
none of it is live code anymore.

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

## Repeater UI + live picker update (2026-08-27)

Follow-up ask: the original single textarea only showed the resolved
family chips (and updated the `<datalist>`) after a full save+reload,
which read as broken — pasting a URL and looking at the still-empty
"No Google Fonts added yet" message right below it, with no feedback
that anything had registered. User: *"here u have to add repeater, if
I add font it should visible in font selector as drop down."*

- **The storage/sanitize contract was deliberately left untouched.**
  `hex_sanitize_google_fonts_urls()`, `hex_get_google_fonts_urls()`,
  and the `hex_google_fonts_urls` option are still one newline-joined
  string — same as before, so `tests/GoogleFontsTest.php`'s 18 tests
  needed zero changes. The repeater is a client-side-only convenience
  layered on top: `hex_render_google_fonts_field()` now renders one
  visible text input per stored URL (plus an "+ Add Font" button and a
  per-row remove button) alongside a single hidden `<textarea
  data-hex-google-fonts-hidden>` carrying the *actual*
  `name="hex_google_fonts_urls"` field that gets submitted.
- **`assets/js/admin.js`** (new IIFE) keeps the hidden field's value in
  sync with the visible rows on every keystroke (`input` listener,
  joining non-empty row values with `\n` — exactly the format
  `hex_sanitize_google_fonts_urls()` already parses), and on the same
  tick re-derives the family list by mirroring the PHP regex logic in
  JS (`family=[^&]+` extraction, `+`-to-space decoding, stripping the
  `:axis-spec` suffix) — then rewrites the shared
  `#hex-google-fonts-list` `<datalist>`'s `<option>`s and the chip list
  from scratch. This is why a newly pasted font now shows up in the
  "Body Font Family" / "Heading Font Family" etc. dropdowns
  immediately, without saving — saving is still what makes it durable
  across reloads/other admins, but the picker itself no longer waits
  for it.
- The repeater's remove button (`.hex-btn.hex-btn-secondary` shrunk
  with `px-3!`) needed the same `!important`-over-`bg-black!`-style
  care as the Theme Options tab buttons — see
  `knoladge/admin-bare-button-reset-and-layer-order.md` — since
  `.hex-btn`'s own `px-4` lives in the later-declared `components`
  layer and would otherwise have beaten a plain `px-3` utility
  regardless of specificity.
- **This UI has no no-JS fallback for editing.** The visible per-row
  inputs are deliberately unnamed (only the hidden `<textarea>` carries
  `name="hex_google_fonts_urls"`), so without JS running, typing into a
  row never reaches the submitted field — the admin would see their
  existing saved URLs rendered, but any add/remove/edit would be
  silently lost on submit. Accepted as consistent with the rest of
  this dashboard, which already assumes JS for anything interactive
  (tab switching breaks completely without it; the color-swatch/hex
  text sync just doesn't sync).

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

## Removed (2026-08-29)

Sequence that led here: the Font Library (dropdown-based, four
Heading/Body/Accent/Mono slots) was added alongside this picker,
without replacing it, per the user's own choice at the time. That
created two parallel, differently-shaped font systems on the same
Typography panel — the free-text fields above plus the new dropdowns —
and led to real confusion tracing through several follow-up messages
(a report that "the variables aren't in the CSS file," which turned
out to be a `.font-mono`/Tailwind-utility class collision investigated
and fixed separately, plus general uncertainty about which field
actually controlled what). The user's resolution: *"keep only that
option and remove all other font options"* — meaning keep the Font
Library, remove everything documented above.

Removed entirely: `hex_sanitize_google_fonts_urls()`,
`hex_get_google_fonts_urls()`, `hex_get_google_font_families()`,
`hex_enqueue_google_fonts()`, `hex_render_google_fonts_datalist()`
(all from `inc/google-fonts.php`), `hex_render_google_fonts_field()`
(`inc/admin/settings.php`), the `hex_google_fonts_urls` option and its
save handling (`inc/admin/handlers.php`), the two render calls in
`inc/admin/page-theme-options.php`, the `body_font_family`/
`heading_font_family` schema fields (`inc/style-settings.php`), the
`list="hex-google-fonts-list"` attribute on the still-present generic
`'font'`-type input (kept only for auto-detected "Custom Tokens" that
happen to guess as a font list — see `hex_guess_style_type()` — not
for these two removed fields specifically), and the entire repeater
IIFE in `assets/js/admin.js` (135 lines).

`hex_google_fonts_resource_hints()` was simplified to check only
`hex_get_font_library_selection()` (it briefly checked both systems'
"has fonts" state during the short window both existed — see
`action-map.json` for that intermediate step).

**`site-theme.css`'s bare `body`/`h1`–`h6` rules were repointed**, not
just left to lose their font control: `font-family:
var(--hex-body-font-family, ...)` / `var(--hex-heading-font-family,
...)` became `var(--hex-font-body, ...)` / `var(--hex-font-heading,
...)` — the Font Library's Heading/Body slots now drive bare
headings/body text site-wide, exactly like the removed fields used
to, so no site-wide styling capability was lost, only the
duplicate/confusing UI for it. A site with an existing saved
`--hex-body-font-family`/`--hex-heading-font-family` value in its
`theme-options.css` keeps that value sitting in the file (auto-picked
up under "Custom Tokens" now that it's no longer a static schema
field, per `hex_merge_style_schema_with_tokens()`) but it no longer
does anything — the Font Library's own `font_body`/`font_heading`
values are what render.

`tests/GoogleFontsTest.php` was rewritten from 18 tests (all for the
removed functions) down to 13 (all for the Font Library, which had
already been covered since it was added a few actions earlier — see
`action-map.json`). Two Theme Options render calls
(`hex_render_google_fonts_field()`/`hex_render_google_fonts_datalist()`)
were removed from `inc/admin/page-theme-options.php`; no test covered
those specifically so nothing needed updating there.

## A hand-edited value with a longer fallback chain didn't show as selected (2026-08-29)

User hand-added real values straight into `theme-options.css`:

```css
--hex-font-heading: 'Instrument Serif', Georgia, 'Times New Roman', serif;
--hex-font-body: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
--hex-font-accent: 'Instrument Serif', Georgia, 'Times New Roman', serif;
--hex-font-mono: 'IBM Plex Mono', ui-monospace, monospace;
```

— then reported *"I cannt see them on admin panel."* Root cause: the
Font Library's admin `<select>` (`inc/admin/settings.php`) marked an
`<option>` selected only via an **exact** string match against
`hex_get_common_google_fonts()`'s own canonical stack for that font —
`hex_get_common_google_fonts()`'s entries use short, opinionated
fallback chains (`"'Inter', sans-serif"`, `"'Instrument Serif',
serif"`, `"'IBM Plex Mono', monospace"`), none of which match a
hand-typed longer chain byte-for-byte, so the `<select>` silently fell
back to showing "— Use Default —" as selected even though a real
value was set and actively rendering on the front end (the CSS
variable and the `.hex-font-{slot}` class were never affected by
this — it's purely a display/matching bug in the admin dropdown).

**Fix**: added `hex_font_stack_primary_name()` (extracts the leading
font-family name out of any stack string, quoted or bare) and
`hex_get_google_font_by_name()` (looks up a Font Library entry by that
name, case-insensitive, in `inc/google-fonts.php`). The render logic
now goes through a new pure function,
`hex_resolve_google_font_field_selection( $value )`: an exact stack
match still wins outright; failing that, a name-only match is used to
decide which `<option>` to mark selected. All 4 of the user's real
values now resolve correctly (verified via a throwaway script loading
`inc/google-fonts.php` directly and calling the new function on each
exact saved value — all four matched their intended font).

**One consequence worth knowing**: since the `<option value="">`
attributes are always the canonical short stacks (not whatever was
hand-typed), the *next* time this Theme Options page is saved — even
saving an unrelated field, since the form submits every field on
every save — the `<select>`'s own submitted value overwrites the
hand-typed longer fallback chain with the canonical shorter one. This
is expected, not a bug: the Font Library's whole design is "admin
picks from a known-good list," not "admin owns an arbitrary fallback
chain" — a hand-edit is honored for display purposes but normalizes
back to the canonical stack on the next real save.

Added 4 new tests to `tests/GoogleFontsTest.php` for
`hex_font_stack_primary_name()`/`hex_get_google_font_by_name()`, plus
4 more for `hex_resolve_google_font_field_selection()` (empty value
unchanged, exact match unchanged, longer-fallback-chain normalized,
genuinely-unknown value returned unchanged so no option gets falsely
marked selected) — 21 tests total in that file now. `vendor/bin/phpunit`
153/153, `vendor/bin/phpcs` 0 errors. Bumped version 1.7.1 → 1.7.2.
