# Feature: Theme Management Admin Dashboard

## What it is

A dedicated wp-admin section for managing this theme: one top-level
menu (fixed label "Hexnity WP" — see [[brand-styling]]) with five
submenu pages, presented as a genuine **app-shell dashboard** rather
than a stock WordPress settings screen: a persistent dark branded
sidebar (logo, icon nav, version footer) on the left, and an equally
dark content pane (cards, panels, header bar) on the right — the whole
shell is dark, not just the sidebar.

## Status

Implemented, linted clean (0 PHPCS errors/warnings), both CSS bundles
compile. Not yet viewed in a live browser (see Known gaps).

## Revision notes

1. Replaced a first version where every page opened with a plain dark
   banner (logo + a horizontal tab bar) above its own `<h1>`. The
   user's feedback: *"the full admin menu should be polished and more
   modern. now it feel just basic. I need very dashbaord look."* That
   round restructured all five pages around one shared two-column
   shell, and added a stat-tile row (icon + big value + label) to the
   top of the Dashboard page.
2. The shell that resulted still had a light-gray content pane next to
   the dark sidebar. Next feedback: *"I would say mostly I like dark
   theme. u dont need to strict with logo colors. also buttons and
   input fileds should be more good and cute."* This round made the
   entire content pane dark (not just the sidebar), swapped the
   `hexnity-*` teal for Tailwind's `indigo` scale as the general
   interactive accent (the brand teal now only appears on the sidebar
   — it doesn't need to be on every element), and added a real
   button/input component system — see
   `knoladge/admin-dark-theme-components.md` for the full detail.
3. That round's `!` important-modifier fix wasn't applied broadly
   enough — most page-body text (not just the new form components)
   was still losing to WP-admin's own unlayered text-color rules on
   the new dark backgrounds. User: *"double check text colors, u
   changed the bg color black and kept all text colors also dark. now
   cannt see them."* Fixed by converting every remaining bare
   text-color utility across all five pages to its `!`-suffixed form.
   Also bumped the sidebar logo size (`h-7` → `h-11`) per *"make the
   logo little bigger."* Both documented in the same knoladge file.
4. Added an admin-configurable, API-free Google Fonts picker (paste a
   Google Fonts embed link, its family name(s) show up in every
   font-family field's searchable picker) — see
   `knoladge/google-fonts-picker.md` and [[design-system]].

## Pages

1. **Dashboard** (`hex-theme`, default page) — a 4-up stat-tile row
   (Theme Version, Page Templates, Style Settings count, Child Theme
   active/not-active), then the existing detail cards: Updates status,
   the three page templates, registered nav menu locations, quick
   links.
2. **Updates** (`hex-theme-updates`) — see [[github-updater]].
3. **Child Theme** (`hex-theme-child-theme`) — see [[child-theme]].
4. **Theme Options** (`hex-theme-theme-options`) — see
   [[design-system]]; the left-category/right-panel tab layout now
   lives inside the shared shell, with card-style panel headers and a
   sticky "Save Style Settings" footer bar.
5. **About** (`hex-theme-about`) — theme metadata via `wp_get_theme()`.

## How it works

- `inc/admin/partials.php` is the shared chrome, replacing the old
  single-banner approach with three building blocks:
  - `hex_admin_nav_items()` — the five nav entries (key, label, menu
    slug), in sidebar order, used by both the sidebar and (formerly)
    the tab bar.
  - `hex_render_admin_icon( $name, $classes )` — a fixed set of inline
    SVG icons built from plain shapes (`<rect>`/`<circle>`/`<line>`),
    not copied bezier path data, so they're guaranteed to render
    correctly with zero external icon-font/CDN dependency.
  - `hex_render_admin_shell_start( $active, $title, $description )` /
    `hex_render_admin_shell_end()` — every page's render function
    calls `_start()` right after its capability check, prints its own
    body markup, then calls `_end()`. `_start()` renders: the sidebar
    (logo, five icon nav links with an active/inactive state, a
    version footer) and opens the content pane, printing its header
    bar (`$title`/`$description`) itself so every page gets consistent
    typography there without repeating markup.
  - `hex_render_admin_stat_tile( $icon, $value, $label )` — the small
    icon+value+label card used in the Dashboard's stat row; a generic
    component, not Dashboard-specific, in case another page wants one
    later.
- `inc/admin/menu.php` — `hex_register_admin_menu()` (hooked to
  `admin_menu`) registers the top-level page and five submenus and
  records their hook suffixes via `hex_admin_screen_hooks()`, so
  `hex_enqueue_admin_assets()` only loads
  `assets/css/tailwind-admin.css` / `assets/js/admin.js` on these
  specific screens.
- Every page render function still checks its own capability
  (`manage_options` / `edit_themes` / `edit_theme_options`) before
  rendering anything, even though menu registration already restricts
  visibility — defense in depth for direct URL access.
- The sidebar's active-state text color and the inactive/hover text
  colors use Tailwind v4's trailing `!` important-modifier
  (`text-white!`, `hover:text-white!`) — see
  [[tailwind-cascade-layers-vs-wp-admin]]: WP core's own unlayered
  `a { color: ... }` rule otherwise beats a plain (layered) Tailwind
  utility regardless of specificity. Background-color utilities don't
  need `!` since that specific WP rule only sets `color`, not
  `background-color`.
- Buttons and form fields across all five pages use the admin-only
  component classes defined in `assets/css/src/admin.css`
  (`.hex-btn`/`.hex-btn-primary`/`-secondary`/`-danger`, `.hex-field`,
  `.hex-color-swatch`, `.hex-label`) instead of hand-rolled Tailwind
  utility strings or WP core's own `submit_button()`/`.form-table` —
  see `knoladge/admin-dark-theme-components.md`. The accent color for
  these (and for links/active states generally) is Tailwind's built-in
  `indigo` scale, not the `hexnity-*` brand palette — the brand teal is
  now deliberately confined to the sidebar only.
- All admin-only files (`inc/admin/*`) are only `require`d when
  `is_admin()` is true (see `functions.php`), so none of this loads on
  front-end requests.

## Files involved

`inc/admin/menu.php`, `inc/admin/partials.php`,
`inc/admin/page-dashboard.php`, `inc/admin/page-updates.php`,
`inc/admin/page-about.php`, `inc/admin/page-child-theme.php`,
`inc/admin/page-theme-options.php`, `inc/admin/settings.php`,
`inc/admin/handlers.php`, `assets/css/tailwind-admin.css`,
`assets/js/admin.js`, `assets/images/hexnity-dark.png`.

## Known gaps / next steps

- No screenshot/visual check performed yet (no live-site smoke test —
  see `knoladge/mcp-ability-surface-narrowed.md`).
- No dedicated tests for the page-render functions themselves (they're
  presentation-only — echo escaped HTML built from already-tested data
  functions); covered indirectly by the underlying data/logic tests.
- The five inline nav icons are simple geometric stand-ins (grid,
  clock, overlapping squares, sliders, info-circle), not a full custom
  icon set — deliberately, to avoid any risk of malformed/inaccurate
  hand-typed bezier path data breaking the sidebar.
