# Feature: Site Design System (Theme Options)

## What it is

A YOOtheme-Pro-scale set of admin-configurable design tokens —
**~146 fields across 12 groups**: Typography, Spacing, Colors,
Buttons, Forms, Cards, Sections, Global (radius), Tables, Alerts,
Badges, Icons — editable from wp-admin's **Theme Options** page (a
left-category/right-panel tabbed layout, not a giant flat form), and
applied site-wide via CSS custom properties with **no rebuild
required**. Change the H1 line-height in the admin, save, reload the
front end: it's different everywhere, immediately.

## Status

Implemented and unit-tested (schema, value resolution, sanitization
for all 6 field types, CSS-var output, shadow-preset resolution).
**Not yet viewed in a browser** — no live-site check performed.

## Revision note

This replaced a first version that only had ~27 fields (headings'
size only, one color palette, basic buttons) and a plain WP
form-table UI. The user's feedback was direct: *"it is not completed.
there are massive settings in yootheme pro... even basic stuffs also
missing like, line height, letter spacing... the settings UI is not
good... add more 100+ settings."* This version addresses both: full
line-height/letter-spacing/weight/margin per heading level, a much
larger color/component system, and a tabbed admin UI with real color
swatches.

## ⚠️ Read this before styling any new template, page, or component

**Always prefer a design-system class/component over a raw Tailwind
value or hand-rolled markup** when the thing you're styling is
typography, a card, a button, a form field, an alert, a badge, a
table, or an icon — because only these respond to Theme Options.

### Typography

| Need | Use this |
|---|---|
| A page/post heading | Just use `<h1>`–`<h6>` — size, line-height, letter-spacing, weight, and margin-bottom are ALL already wired to Theme Options via a base `@layer base` rule. No class needed. |
| H-sized text on a non-heading element | `text-h1` … `text-h6` (size only) |
| Intro/lead paragraph | `text-lead` |
| Slightly bigger body copy | `text-large` |
| Meta text, captions, fine print | `text-small` |
| Uppercase eyebrow/label text | `text-meta` (paired with its own line-height/letter-spacing/weight tokens) |
| Base paragraph text | `text-body` (also the `<body>` default) |
| A link | Nothing needed — `<a>` already defaults to the Link Color / Link Hover Color tokens (any more specific existing utility, e.g. a white nav link, still wins via ordinary specificity) |

**Post/page content typed by an editor** (inside `the_content()`,
wrapped in `.prose`) gets the full H1–H6 treatment (size, line-height,
letter-spacing, weight) automatically too — `site-theme.css` overrides
`.prose h1`…`h6` directly, using `@tailwindcss/typography`'s own
documented override pattern.

### Layout & spacing

| Need | Use this | Not this |
|---|---|---|
| Section/element gaps | `p-xs` `p-sm` `p-md` `p-lg` `p-xl` `p-2xl` (and `m-*`, `gap-*`, `space-y-*` — same scale) | `p-4`, `p-8`, arbitrary numbers |
| A full page-section's vertical padding | `py-section-sm` `py-section-md` `py-section-lg` `py-section-xl` | `py-16`, `py-24` |
| A section's background | `bg-site-section-default` `bg-site-section-muted` `bg-site-section-primary` `bg-site-section-secondary` | `bg-white`, `bg-gray-50` |

### Colors

| Need | Use this |
|---|---|
| Brand/primary | `bg-site-primary` / `text-site-primary` / `border-site-primary` |
| Secondary / Tertiary / Accent | `*-site-secondary` / `*-site-tertiary` / `*-site-accent` |
| Semantic state | `*-site-success` / `*-site-warning` / `*-site-danger` / `*-site-info` |
| Muted surface | `bg-site-muted` |
| Strong emphasis text | `text-site-emphasis` |
| Body background/text | `bg-site-body-bg` / `text-site-body-text` (already the `<body>` default — see above) |
| A dark/inverse surface | `bg-site-inverse-bg` / `text-site-inverse-text` |
| A divider/border | `border-site-border` |

**Never use raw `bg-blue-600`, `text-gray-800`, etc. for anything
brand- or content-related** — those never respond to Theme Options.
Raw Tailwind colors are still fine for genuinely one-off,
non-brand accents if a specific page ever needs one outside the
system (rare).

### Components

| Need | Use this | Not this |
|---|---|---|
| Any button / CTA | `.btn` (default/neutral), `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-text`, `.btn-outline`; add `.btn-sm` / `.btn-lg` for size | Hand-rolled `class="rounded-md bg-blue-600 px-4 py-2 ..."` |
| A boxed content block | `.card` (background, border, radius, padding, shadow — all tokenized; `:hover` uses the separate hover-shadow token) | `rounded-xl border bg-white p-6 shadow-sm` composed by hand |
| A text input / textarea | `.form-control`, paired with `.form-label` on its `<label>` | Ad-hoc `[&_input]:border ...` arbitrary variants |
| A status message | `.alert` + one of `.alert-primary` / `-success` / `-warning` / `-danger` | Hand-rolled colored `<div>` |
| A small status pill | `.badge` + one of `.badge-default` / `-primary` / `-success` / `-warning` / `-danger` | Hand-rolled `rounded-full` span |
| A data table | `.table-styled` (borders, header background, zebra striping) | A bare `<table>` |
| An icon | `.icon` (+ `.icon-sm` / `.icon-lg`) | Hardcoded `w-6 h-6 text-gray-600` |

**It's still fine to use raw Tailwind** for things that are genuinely
layout/structure, not visual design — `flex`, `grid`, `w-full`,
`overflow-hidden`, responsive prefixes (`md:flex-row`), etc.

### Global radius — a deliberate exception

`rounded-sm` / `rounded-md` / `rounded-lg` are **not** left alone —
the "Global" settings group's 3 radius fields intentionally override
Tailwind's own built-in radius scale, site-wide, the way YOOtheme's
own global border-radius setting works. If you use `rounded-lg`
anywhere (as the post-thumbnail wrapper already does), it responds to
Theme Options too, whether you intended that or not. Button/card/form
radii (`rounded-button`, `rounded-card`, `.form-control`'s radius) are
separate, distinctly-named tokens and don't share this override.

## Where each token actually lives

- **Tailwind side**: `assets/css/src/site-theme.css` — a `@theme`
  block defining every sizeable/colorable/radius-able token as
  `var(--hex-{key}, {default})`; a `@layer base` block applying the
  heading/link/body defaults directly to bare HTML elements; a
  `@layer components` block for `.btn`, `.card`, `.form-control`,
  `.table-styled`, `.alert`, `.badge`, `.icon`, and the `.prose`
  heading overrides.
- **PHP side**: `inc/style-settings.php` — `hex_get_style_schema()` is
  the single source of truth (built with loops for repetitive
  families — heading levels, button/alert/badge variants — rather
  than ~146 hand-written array literals). Drives Settings API
  registration (`inc/admin/settings.php`), the generic field renderer
  (6 types: length/number/color/weight/shadow/font), and
  `hex_render_style_css_vars()` (hooked to `wp_head`, prints every
  current value as a `--hex-{key}` custom property on `:root` on
  **every front-end request**, regardless of child-theme-active
  state).
- **Admin UI**: `inc/admin/page-theme-options.php` — a left-category
  tab nav + right detail panel (client-side JS in `assets/js/admin.js`
  drives tab switching and syncs each native `<input type="color">`
  swatch with its paired hex text field). One panel per schema group.

## Field types & their inputs

| Type | Example fields | Admin input | Sanitize rule |
|---|---|---|---|
| `length` | sizes, radius, padding, letter-spacing, margins | text | CSS length (`rem`/`em`/`px`/`%`), optional leading `-`, or bare `0` |
| `number` | line-heights | text | Bare unitless decimal (e.g. `1.5`) |
| `color` | every color field | paired color-swatch + text | `sanitize_hex_color()` |
| `weight` | font weights | `<select>` 300–900 | Must be one of 100–900, step 100 |
| `shadow` | card shadows | `<select>` None/Small/Medium/Large/XL | Must be a `hex_get_shadow_presets()` keyword — the real box-shadow CSS behind each keyword is fixed, not admin-editable (shadow syntax can't safely round-trip through a plain text field) |
| `font` | font-family fields | text with a searchable `<datalist>` (see below) | Letters, digits, spaces, commas, hyphens, single quotes only |

Every sanitize callback **rejects and keeps the previous value**
(never stores empty/invalid) and records a `settings_error` explaining
why.

### Google Fonts picker

Every `font`-type field's text input carries `list="hex-google-fonts-list"`,
pointing at a `<datalist>` (`hex_render_google_fonts_datalist()`,
`inc/google-fonts.php`) populated from whatever Google Fonts the admin
has added — a plain textarea at the top of the Typography panel
(`hex_render_google_fonts_field()`, `inc/admin/settings.php`) where the
admin pastes a Google Fonts embed link (the whole snippet from
fonts.google.com works, not just the bare URL). No Google Fonts API
key or request is used: family names are parsed directly out of the
stored URL's own `family=` query parameter(s), and the same URL(s) are
enqueued as-is on the front end. See
`knoladge/google-fonts-picker.md` for the full implementation and
`inc/google-fonts.php`'s functions (`hex_get_google_fonts_urls()`,
`hex_get_google_font_families()`, `hex_enqueue_google_fonts()`,
`hex_google_fonts_resource_hints()`).

## Adding a new token (for future AI work)

1. Add an entry (or a loop-built family) to `hex_get_style_schema()`
   in `inc/style-settings.php` — key, group, type, default, label.
   That's the only place that needs a change for it to appear on the
   Theme Options page and be saved/sanitized/output correctly.
2. Reference it in `assets/css/src/site-theme.css`'s `@theme` block as
   `--{tailwind-namespace}-{name}: var(--hex-{key}, {same default});`
   for a general-purpose utility, or reference
   `var(--hex-{key}, {default})` directly inside a `@layer components`
   rule if it's component-specific (like button padding).
3. `npm run build:css` and use the new class.
4. Add it to the tables above so the next AI session knows it exists.

## Why it's gated on an active child theme

Editing Theme Options is disabled (`<fieldset disabled>`) unless
`hex_is_child_theme_active()` is true — an explicit user requirement:
*"if any child theme activated, they should [be able to edit]. if not
it should be disabled and asking for a child theme."* Rationale:
style customization should live somewhere a parent theme update can't
wipe out. The stored **values** always still apply on the front end
regardless of which theme is active — only the editing UI is gated.

## Files involved

`inc/style-settings.php`, `assets/css/src/site-theme.css`,
`assets/css/src/front.css` (imports it), `inc/admin/settings.php`
(`hex_register_style_settings()`, `hex_render_style_field()`,
`hex_render_style_group_fields()`, `hex_render_google_fonts_field()`),
`inc/admin/page-theme-options.php` (tabbed layout inside the shared
dashboard shell — see [[theme-admin-dashboard]]), `inc/google-fonts.php`
(the Google Fonts picker — see `knoladge/google-fonts-picker.md`),
`assets/js/admin.js` (tab switching + color-swatch sync),
`inc/admin/menu.php` (5th submenu), `inc/admin/partials.php` (5th
sidebar nav item).

Front-end templates retrofitted to use these tokens: `archive.php`,
`404.php`, `search.php`, `comments.php`, `template-parts/content-none.php`
(now uses `.card`), `template-parts/content.php` (now uses `.card`),
`template-parts/content-page.php`, `footer.php`, `header.php` — and
explicit `font-bold`/`font-semibold`/`tracking-tight` utility classes
were **removed** from headings across these files, since they'd
otherwise override (via higher specificity) the new base `h1`–`h6`
rules that make weight/letter-spacing admin-controlled.

## Tests

`tests/StyleSettingsTest.php` (36 tests) — schema size (≥100 fields)
and group coverage, naming transforms, value resolution, the CSS-var
output builder (incl. shadow-keyword resolution and the unsafe-value
skip), and all 6 sanitize callbacks' accept/reject-and-revert
behavior. `tests/GoogleFontsTest.php` (18 tests) — URL sanitizing
(bare URL, whole pasted embed snippet, wrong-host rejection,
deduping), family-name parsing (single/multiple/dedup/`+`-decoding),
the front-end enqueue, the resource-hints filter, and the rendered
`<datalist>`.

## Known gaps / next steps

- Not retrofitted everywhere: fine-grained layout spacing (`gap-2`,
  `mt-1`), nav/admin-page chrome, and search/comment-form submit
  buttons still use raw Tailwind rather than `.form-control`/`.btn` —
  WP's `comment_form()` accepts a `class_submit` arg and could switch
  easily; `get_search_form()` is more involved. Not done this round.
- `.alert`, `.badge`, `.table-styled`, `.icon` have no current
  on-site usage (the theme doesn't render any alerts/badges/tables/
  icons yet) — they exist ready for future page-building, per the
  original ask to cover "almost all" of YOOtheme Pro's settings even
  where this specific theme doesn't have a matching component yet.
- No live-browser verification that changing a Theme Options value
  actually re-renders correctly (verified via compiled-CSS inspection
  + unit tests only).
- Color fields use `<input type="color">` + a paired text field
  (synced via JS) rather than a fancier picker with a palette/recent-colors
  UI — deliberately simple, still meaningfully more "professional"
  than a bare text input.
