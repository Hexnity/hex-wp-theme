# Feature: Site Design System (Theme Options)

## What it is

A YOOtheme-Pro-scale set of admin-configurable design tokens —
**187 fields across 15 groups**: Typography, Spacing, Colors,
Buttons, Forms, Cards, Sections, Global (radius), Tables, Alerts,
Badges, Navigation, Accordion, Tabs, Icons — editable from wp-admin's
**Theme Options** page (a left-category/right-panel tabbed layout,
not a giant flat form), and applied site-wide via CSS custom
properties with **no rebuild required**. Change the H1 line-height in
the admin, save, reload the front end: it's different everywhere,
immediately.

## Status

Implemented and unit-tested (schema, value resolution, sanitization
for all 7 field types, CSS-var output, shadow-preset resolution).
Live-verified on a real site across many rounds (see
`knoladge/fluid-typography-clamp.md` for the full history of the
typography system specifically).

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
| A page/post heading | Just use `<h1>`–`<h6>` — size, line-height, letter-spacing, weight, and margin-bottom are ALL already wired to Theme Options via a base `@layer base` rule. No class needed. Size here is FLAT (the Desktop Size field's value), not fluid — see "Two typography paths" below. |
| H-sized text on a non-heading element | `text-h1` … `text-h6` (size only, same flat Theme-Options value as bare `h1`–`h6`) |
| Intro/lead paragraph | `text-lead` |
| Slightly bigger body copy | `text-large` |
| Meta text, captions, fine print | `text-small` |
| Uppercase eyebrow/label text | `text-meta` (paired with its own line-height/letter-spacing/weight tokens) |
| Base paragraph text | `text-body` (also the `<body>` default) |
| A link | Nothing needed — `<a>` already defaults to the Link Color / Link Hover Color tokens (any more specific existing utility, e.g. a white nav link, still wins via ordinary specificity) |
| **A fluid (viewport-responsive) text size** | `hex-h1` … `hex-h6`, `hex-body`, `hex-lead`, `hex-large`, `hex-small`, `hex-meta` — see "Two typography paths" below. This is the ONLY fluid path; `text-h1`/bare `h1`/etc. are flat. |

**Post/page content typed by an editor** (inside `the_content()`,
wrapped in `.prose`) gets the full H1–H6 treatment (size, line-height,
letter-spacing, weight) automatically too — `site-theme.css` overrides
`.prose h1`…`h6` directly, using `@tailwindcss/typography`'s own
documented override pattern.

### Two typography paths — flat vs. fluid (read this carefully)

This system went through many iterations (see
`knoladge/fluid-typography-clamp.md` for the full blow-by-blow) and
landed on two deliberately different, coexisting paths. Picking the
wrong one for a given need is the single easiest mistake to make here.

**Path 1 — flat, Theme-Options-driven (`--text-h1` etc., bare `h1`–`h6`, `.text-h1`, `.prose h1`)**

Every text-size field in the Typography group is still **two** admin
inputs (Mobile Size + Desktop Size, both `px`), but only the **Desktop
Size** value is ever used by this path — `--text-h1` resolves to
`var(--hex-h1-size, {px default})`, and `--hex-h1-size` is written by
`hex_build_style_tokens_css()` as a flat copy of `--hex-h1-size-desktop`
(never a `clamp()`, on purpose — removed per explicit user request; see
the knoladge doc). Change "H1 Desktop Size" in the admin, save: every
bare `<h1>`, `.text-h1`, and `.prose h1` updates immediately, and it is
the same size at every viewport width. The Mobile Size field still
exists (and is still admin-editable) but nothing in this path reads it.

**Path 2 — fluid, ALSO Theme-Options-driven, but one shared function (`.hex-h1`…`.hex-meta`)**

Apply one of these classes to ANY element (not just a real `<h1>`–`<h6>`)
for a size that smoothly interpolates between viewport widths — this
IS fluid, unlike path 1. Two hard rules govern this layer, both from
explicit instruction:

1. **Exactly one shared `clamp()` calculation** — never one formula
   per class. Each class only sets its own `--mobile-font-size`/
   `--desktop-font-size` (`var(--hex-{key}-size-mobile/-desktop, {px
   default})` — yes, both endpoints, unlike path 1); one shared
   selector list (all eleven classes) applies the single `font-size:
   clamp(...) !important` rule beneath them.
2. **The breakpoints never touch Theme Options.** `--hex-static-breakpoint-s`/`-m`/`-l`/`-xl`
   (640px/960px/1200px/1600px — Small "Phone Landscape / Large
   Phones", Medium "Tablet Landscape / Small Laptops", Large "Standard
   Desktops", X-Large "Large Screens / High-Res Monitors") are
   hardcoded plain `:root` values in `site-theme.css` itself — not a
   schema field, never written to `theme-options.css`, not
   admin-editable. This is a *different* breakpoint set from Theme
   Options' own `--hex-fluid-breakpoint-s/-m/-l/-xl` (Global tab),
   which exists but isn't consumed by anything (a historical leftover
   from an earlier iteration where path 1 was also fluid — see the
   knoladge doc); only S and XL feed path 2's interpolation, M/L are
   declared for parity, unused.

So: **both paths read the same admin Mobile/Desktop Size fields**, but
path 1 uses only Desktop and stays flat, while path 2 uses both and is
fluid via one shared function. Use path 2 whenever the size should
actually respond to viewport width; use path 1 (the default, already
wired to every bare heading) otherwise.

**`.prose` (post/page body copy) is on path 2, not path 1** — real
post and page content (`the_content()`/`the_excerpt()`, wrapped in
`.prose`) is fluid, unlike bare headings. This is a deliberate
exception: `@tailwindcss/typography`'s own `.prose` rule sets a flat
`font-size: 1rem` directly on the `.prose` container (in Tailwind's
utilities layer), which every plain descendant — `<p>`, `<li>`, etc.
— inherits, completely bypassing Theme Options and `<body>`'s own
`--text-body` no matter what either says. `site-theme.css` gives
`.prose` the exact same `--mobile-font-size`/`--desktop-font-size`
treatment as `.hex-body` (reading `--hex-body-size-mobile`/`-desktop`)
and adds `.prose` to path 2's one shared `clamp()` selector list —
still exactly one function, no duplicate formula. Only `font-size` is
touched; every other `.prose`/`--tw-prose-*` default (colors,
line-height, spacing, list markers, etc.) is untouched, still plain
Tailwind Typography. `.prose h1`–`h6` (below) were already on path 1
before this fix and remain so — only the base body-copy font-size was
the gap.

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
| Any button / CTA | `.btn` (default/neutral), `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-text`, `.btn-outline`; size via `.btn-xs` / `.btn-sm` / (default, no class) / `.btn-lg` / `.btn-xl` | Hand-rolled `class="rounded-md bg-blue-600 px-4 py-2 ..."` |
| A boxed content block | `.card` (background, border, radius, padding, shadow — all tokenized; `:hover` uses the separate hover-shadow token) | `rounded-xl border bg-white p-6 shadow-sm` composed by hand |
| A text input / textarea | `.form-control`, paired with `.form-label` on its `<label>`; size via `.form-control-sm` / (default) / `.form-control-lg` | Ad-hoc `[&_input]:border ...` arbitrary variants |
| A status message | `.alert` + one of `.alert-primary` / `-success` / `-warning` / `-danger` | Hand-rolled colored `<div>` |
| A small status pill | `.badge` + one of `.badge-default` / `-primary` / `-success` / `-warning` / `-danger` | Hand-rolled `rounded-full` span |
| A data table | `.table-styled` (borders, header background, zebra striping) | A bare `<table>` |
| An icon | `.icon` (+ `.icon-sm` / `.icon-lg`) | Hardcoded `w-6 h-6 text-gray-600` |
| A nav menu (primary/footer) | `.nav-menu` on the `wp_nav_menu()` `menu_class` (layout/gap); every link auto-gets `.nav-link` via `hex_nav_menu_link_attributes()` (`inc/setup.php`, hooked to `nav_menu_link_attributes` — `wp_nav_menu()` has no `link_class` arg, so this is the only way to reach the `<a>` tags). Active state matches WP's own `.current-menu-item`/`.current-menu-ancestor` classes on the `<li>`, no extra markup needed. | Hand-rolled `[&_a]:...` arbitrary variants in `menu_class` |
| An accordion (FAQ, expandable sections) | `.accordion` wrapping one or more `<details class="accordion-item">` (native, no JS — `<summary>` is the trigger, wrap the body in `.accordion-content`) | A JS-toggled div, or a plugin |
| Tabs | `.tabs` wrapping hidden radio `.tabs-input`s + `.tabs-label`s (in order, first checked) followed by `.tabs-panel` divs in the same order — CSS-only via `:checked` sibling selectors, no JS, supports up to 8 tabs per `.tabs`. See the comment above `.tabs-input` in `site-theme.css` for the exact markup shape (order matters). | A JS tab-switcher library |

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
  the static base schema (built with loops for repetitive families —
  heading levels, button/alert/badge variants — rather than ~146
  hand-written array literals); `hex_get_effective_style_schema()`
  merges it with any auto-detected custom tokens (see "Where values
  are actually stored" below) and is what the admin UI/save handler
  actually use. Drives the generic field renderer
  (`inc/admin/settings.php`, 7 types now:
  length/number/color/weight/shadow/font/**custom**), and
  `hex_enqueue_child_theme_tokens()` (hooked `wp_enqueue_scripts`,
  enqueues the active child theme's `theme-options.css` file directly
  — nothing prints when no child theme is active or nothing's been
  saved, and every token's CSS-level fallback default handles that
  case).
- **Admin UI**: `inc/admin/page-theme-options.php` — a left-category
  tab nav + right detail panel (client-side JS in `assets/js/admin.js`
  drives tab switching and syncs each native `<input type="color">`
  swatch with its paired hex text field). One panel per schema group;
  within a panel, `hex_render_style_group_fields()`
  (`inc/admin/settings.php`) further splits a loop-built field family
  into its own collapsible `<details>`/`<summary>` accordion section
  when the schema tags 2+ distinct `subgroup` labels for that group —
  Typography gets one section per heading level (H1–H6) and per
  body-level text style (Body/Lead/Large/Small/Meta) plus a "Fonts &
  Links" section, so they no longer all run together in one long list
  (explicit user ask: *"now h1 and h2 styles all together. I need to
  separate them"*); Buttons/Alerts/Badges get one section per variant;
  Sections gets Backgrounds/Padding. Only the first section in a panel
  starts open. A group with 0–1 subgroups (colors, spacing, forms,
  cards, global, tables, nav, icons, auto-detected custom tokens) still
  renders as the original flat two-column grid — accordion chrome is
  opt-in per field family, not forced everywhere. See
  `tests/AdminSettingsRenderTest.php`.

## Field types & their inputs

| Type | Example fields | Admin input | Sanitize rule |
|---|---|---|---|
| `length` | sizes, radius, padding, letter-spacing, margins | text | CSS length (`rem`/`em`/`px`/`%`), optional leading `-`, or bare `0` |
| `number` | line-heights | text | Bare unitless decimal (e.g. `1.5`) |
| `color` | every color field | paired color-swatch + text | `sanitize_hex_color()` |
| `weight` | font weights | `<select>` 300–900 | Must be one of 100–900, step 100 |
| `shadow` | card shadows | `<select>` None/Small/Medium/Large/XL | Must be a `hex_get_shadow_presets()` keyword — the real box-shadow CSS behind each keyword is fixed, not admin-editable (shadow syntax can't safely round-trip through a plain text field) |
| `font` | font-family fields | text with a searchable `<datalist>` (see below) | Letters, digits, spaces, commas, hyphens, single quotes only |
| `custom` | any auto-detected token not in the static schema | text | `[a-zA-Z0-9 .,+\-/#%()]` only, no `url(` substring, non-empty, ≤200 chars — see `knoladge/child-theme-css-token-architecture.md` |

Every sanitize callback returns the sanitized value or `null` on
failure (pure functions — no side effects); the save handler
(`hex_handle_save_style_options()`, `inc/admin/handlers.php`) is what
decides to keep the previous value and report which fields were
rejected.

## Where values are actually stored

Not the database. Every value lives in a single CSS file inside the
**active child theme** — `{child theme}/theme-options.css`, a
`:root { --hex-key: value; ... }` block, fully regenerated on every
save. This is deliberate: a child-theme developer can hand-edit that
file too (or just add a brand-new `--hex-*` custom property to it),
and anything not already in `hex_get_style_schema()` is auto-detected
on the next Theme Options page load and appears as an editable field
under a **"Custom Tokens"** tab (type guessed from the value's shape).
See `knoladge/child-theme-css-token-architecture.md` for the full
architecture — this is the piece to read before touching
`inc/style-settings.php` or building a child theme that wants a new
token.

### Google Fonts picker

Every `font`-type field's text input carries `list="hex-google-fonts-list"`,
pointing at a `<datalist>` (`hex_render_google_fonts_datalist()`,
`inc/google-fonts.php`) populated from whatever Google Fonts the admin
has added — a repeater at the top of the Typography panel
(`hex_render_google_fonts_field()`, `inc/admin/settings.php`; row
add/remove and live sync handled by `assets/js/admin.js`), one row per
embed link/URL, where the admin pastes a Google Fonts embed link (the
whole snippet from fonts.google.com works in a single row too, not
just the bare URL). The rows are a client-side convenience only: on
every change they're joined back into the one hidden
`hex_google_fonts_urls` field the storage/sanitize layer has always
used (newline-joined string — see `knoladge/google-fonts-picker.md`),
and the same JS also mirrors the resolved family names live into the
shared `<datalist>` and the chip list, so a newly pasted font shows up
in the font-family dropdowns immediately, with no save/reload needed
(saving is still what persists it). No Google Fonts API key or request
is used: family names are parsed directly out of each URL's own
`family=` query parameter(s) (in PHP for storage/front-end enqueue, and
mirrored in JS for the live preview), and the same URL(s) are enqueued
as-is on the front end. See `knoladge/google-fonts-picker.md` and
`knoladge/admin-bare-button-reset-and-layer-order.md` (the repeater's
remove button needed the same `!important`-vs-layer-order care as the
Theme Options tabs) for implementation detail, and
`inc/google-fonts.php`'s functions (`hex_get_google_fonts_urls()`,
`hex_get_google_font_families()`, `hex_enqueue_google_fonts()`,
`hex_google_fonts_resource_hints()`) — all unchanged by the repeater UI.

## Adding a new token (for future AI work)

Two ways, depending on whether this is a token every site using this
theme should have (a parent-theme change) or a one-off a specific
child theme wants (no parent-theme change needed at all):

**A token every site should have** — add it to the static schema:

1. Add an entry (or a loop-built family) to `hex_get_style_schema()`
   in `inc/style-settings.php` — key, group, type, default, label.
   That's the only place that needs a change for it to appear on the
   Theme Options page and be saved/sanitized/output correctly. If
   you're adding a loop-built family with more than one member (a new
   heading level, a new button/alert/badge variant), also give every
   member the same `subgroup` label so the admin UI renders them as
   their own accordion section instead of dumping every member into
   the tab's flat list.
2. Reference it in `assets/css/src/site-theme.css`'s `@theme` block as
   `--{tailwind-namespace}-{name}: var(--hex-{key}, {same default});`
   for a general-purpose utility, or reference
   `var(--hex-{key}, {default})` directly inside a `@layer components`
   rule if it's component-specific (like button padding).
3. `npm run build:css` and use the new class.
4. Add it to the tables above so the next AI session knows it exists.

**A new mobile/desktop text-size field (Path 1, flat)** — same as
above, but add a `{key}_size_mobile` and `{key}_size_desktop` pair
instead of a single `{key}_size` field, and register the pair in
`hex_get_fluid_size_pairs()` (`inc/style-settings.php`) as
`'{key}_size' => array( '{key}_size_mobile', '{key}_size_desktop' )`.
`hex_build_style_tokens_css()` then derives `--hex-{key}-size`
automatically as a **flat copy of the desktop value** (not a
`clamp()` — that was removed per explicit instruction) — reference
that one derived variable from `site-theme.css`, not the mobile/
desktop vars directly. The Mobile Size field still exists and is
still admin-editable, but nothing in this path reads it — see "Two
typography paths" above for why, and use Path 2 below if you actually
want the mobile value to matter.

**Adding the same field to Path 2 (fluid, one shared class)** — if
the new size should also be selectable as a fluid `.hex-*`-style
class: add a new `.hex-{name}` class to `site-theme.css`'s `@layer
components` block that sets `--mobile-font-size: var(--hex-{key}-size-mobile,
{default})` / `--desktop-font-size: var(--hex-{key}-size-desktop,
{default})`, and add `.hex-{name}` to the ONE shared selector list
right below it (the single `clamp()` rule) — never write a second
`clamp()`/duplicate the formula. Do not add new breakpoints for this;
every `.hex-*` class shares the same `--hex-static-breakpoint-s`/`-xl`.
See `knoladge/fluid-typography-clamp.md` for the full history of why
this shape (one function, static breakpoints, Theme-Options-sourced
endpoints) is mandatory, not optional.

**Give mobile/desktop fields genuinely different `default` values,
not the same value twice** — a hypothetical future fluid formula (or
a child theme reintroducing one) would collapse to a flat value when
mobile equals desktop, so an equal-defaults pair would silently ship
non-fluid until an admin manually differentiates it. `hex_build_fluid_clamp()`
itself still exists (pure, unit-tested) even though nothing in this
codebase calls it currently.

**A one-off token for a specific child theme** — no parent-theme
change at all; see `knoladge/child-theme-css-token-architecture.md`'s
"Adding a new token" workflow: add the `--hex-*` custom property to
that child theme's `theme-options.css` (by hand, or by saving any
value once from the admin to create the file), reference it in that
child theme's own CSS, and it appears under "Custom Tokens"
automatically.

## Why it's gated on an active child theme

Editing Theme Options is disabled (`<fieldset disabled>`, and now also
hard-enforced server-side in `hex_handle_save_style_options()`) unless
`hex_is_child_theme_active()` is true — an explicit user requirement:
*"if any child theme activated, they should [be able to edit]. if not
it should be disabled and asking for a child theme."* This is no
longer just a UX nicety: values now literally live inside the active
child theme's own `theme-options.css` file, so there's nowhere to save
them without one. The stored **values** always still apply on the
front end regardless — only the editing UI (and the save path) is
gated.

## Files involved

`inc/style-settings.php` (schema, discovery/merge, sanitize, the
CSS-file builder, the front-end enqueue), `assets/css/src/site-theme.css`,
`assets/css/src/front.css` (imports it), `inc/admin/settings.php`
(`hex_render_style_field()`, `hex_render_style_group_fields()`,
`hex_render_google_fonts_field()`), `inc/admin/handlers.php`
(`hex_handle_save_style_options()` — the admin-post save handler),
`inc/admin/page-theme-options.php` (tabbed layout inside the shared
dashboard shell — see [[theme-admin-dashboard]]), `inc/google-fonts.php`
(the Google Fonts picker — see `knoladge/google-fonts-picker.md`),
`assets/js/admin.js` (tab switching + color-swatch sync),
`inc/admin/menu.php` (5th submenu), `inc/admin/partials.php` (5th
sidebar nav item), `inc/setup.php` (`hex_nav_menu_link_attributes()` —
adds the `.nav-link` class to every primary/footer menu link, since
`wp_nav_menu()` has no built-in way to class its `<a>` tags). See
`knoladge/child-theme-css-token-architecture.md` for the storage
architecture specifically.

Front-end templates retrofitted to use these tokens: `archive.php`,
`404.php`, `search.php`, `comments.php`, `template-parts/content-none.php`
(now uses `.card`), `template-parts/content.php` (now uses `.card`),
`template-parts/content-page.php`, `footer.php`, `header.php` — and
explicit `font-bold`/`font-semibold`/`tracking-tight` utility classes
were **removed** from headings across these files, since they'd
otherwise override (via higher specificity) the new base `h1`–`h6`
rules that make weight/letter-spacing admin-controlled.

## Tests

`tests/StyleSettingsTest.php` — schema size (≥100 fields)
and group coverage, naming transforms, the token-file parser, the
custom-token type-guessing and schema-merge logic, value resolution
(the "no active child theme" fallback path — see
`knoladge/child-theme-css-token-architecture.md` for why the
active-child-theme-with-a-real-file path isn't unit tested here), the
CSS-file builder (incl. shadow-keyword resolution, the unsafe-value
skip, and the `custom` type), all 7 sanitize callbacks' accept/reject
behavior (pure — return `null` on failure), the submitted-tokens
sanitizer, and the derived text-size output (`hex_get_fluid_size_pairs()`
referencing only real schema keys, and the CSS builder emitting the
derived `--hex-{key}-size` as a flat copy of the desktop value — no
`clamp()` — for every pair; `hex_build_fluid_clamp()`'s own
interpolation formula / flat-value collapse / equal-breakpoint
fallback / unsafe-input rejection is still separately tested even
though nothing currently calls it). `tests/GoogleFontsTest.php` (18 tests) — URL sanitizing
(bare URL, whole pasted embed snippet, wrong-host rejection,
deduping), family-name parsing (single/multiple/dedup/`+`-decoding),
the front-end enqueue, the resource-hints filter, and the rendered
`<datalist>`.

## Known gaps / next steps

- Not retrofitted everywhere: fine-grained layout spacing (`gap-2`,
  `mt-1`), admin-page chrome, and search/comment-form submit
  buttons still use raw Tailwind rather than `.form-control`/`.btn` —
  WP's `comment_form()` accepts a `class_submit` arg and could switch
  easily; `get_search_form()` is more involved. Not done this round.
  (Nav *is* now retrofitted — `.nav-menu`/`.nav-link`, see the
  Components table above.)
- `.alert`, `.badge`, `.table-styled`, `.icon` have no current
  on-site usage (the theme doesn't render any alerts/badges/tables/
  icons yet) — they exist ready for future page-building, per the
  original ask to cover "almost all" of YOOtheme Pro's settings even
  where this specific theme doesn't have a matching component yet.
- **Accordion and Tabs** (`.accordion`/`.accordion-item` via native
  `<details>`/`<summary>`; `.tabs`/`.tabs-input`/`.tabs-label`/
  `.tabs-panel` via the CSS-only radio-input technique) — explicit
  user choice: CSS-only, zero JS, matching this theme's currently
  JS-free front end. Same "no current on-site usage yet" caveat as
  alerts/badges/tables above — styled and Theme-Options-driven, ready
  for the next page that needs one.
- No live-browser verification that changing a Theme Options value
  actually re-renders correctly (verified via compiled-CSS inspection
  + unit tests only).
- The `theme-options.css` file write path
  (`hex_handle_save_style_options()`'s `WP_Filesystem` call) has never
  run against a real WordPress install or an actual child theme
  directory — no child theme has been installed yet (see
  `CLAUDE.md`'s "Known project state"). Same standing caveat as the
  GitHub updater/child-theme-install flows.
- Color fields use `<input type="color">` + a paired text field
  (synced via JS) rather than a fancier picker with a palette/recent-colors
  UI — deliberately simple, still meaningfully more "professional"
  than a bare text input.
