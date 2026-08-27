# How: Admin dark theme + button/input components

## What was asked

User feedback after the first app-shell redesign (dark sidebar, light
content pane): *"I would say mostly I like dark theme. u dont need to
strict with logo colors. also buttons and input fileds should be more
good and cute."* Three distinct asks: extend dark styling beyond just
the sidebar, stop treating the exact hexnity-teal palette as
mandatory everywhere, and give buttons/inputs real visual polish.

## What was implemented

- Extended dark theme to the whole shell: the content pane
  (`inc/admin/partials.php`) went from `bg-gray-50`/white cards to
  `bg-gray-950`/`bg-gray-900` cards with `border-gray-800`, and every
  page's card/text colors were updated to match (`text-gray-100` /
  `text-gray-400` in place of `text-gray-900` / `text-gray-600`).
  Kept the sidebar's `bg-hexnity-900` as-is — it was already dark and
  is the one place brand identity still matters.
- Introduced Tailwind's built-in **indigo** scale as the interactive
  accent color (links, active states, primary buttons, panel-header
  eyebrow text, stat-tile icon chips) instead of `hexnity-*` — the
  brand teal no longer needs to appear on every element, per the
  user's "don't need to be strict with logo colors."
- Added a small admin-only component layer to `assets/css/src/admin.css`
  (`@layer components`): `.hex-btn` + `.hex-btn-primary`/`-secondary`/
  `-danger` (rounded-lg, transition, shadow, focus ring), `.hex-field`
  (dark input/select styling), `.hex-color-swatch`, `.hex-label`. These
  are deliberately **not** part of the front-end design-token system
  (`inc/style-settings.php`) — that system exists so a site owner can
  restyle their own front end without a rebuild; the admin dashboard is
  our own fixed chrome, so plain Tailwind components are simpler and
  correct here.
- Replaced every hand-rolled admin button (`rounded-md bg-hexnity-600
  px-4 py-2 ...`) with `.hex-btn .hex-btn-primary`/`-secondary`, and
  every `submit_button()` call with a new
  `hex_render_admin_submit_button()` helper in `inc/admin/partials.php`
  — WP core's `submit_button()` prints a light-themed button styled by
  WP's own unlayered admin CSS, which would visually clash inside a
  dark card.
- Replaced `class="regular-text"` on the repo/branch/token settings
  fields (`inc/admin/settings.php`) with `class="hex-field max-w-md"`,
  and the Theme Options field renderer's `$field_class` with
  `'hex-field'`.
- Restyled WP-core-generated markup we don't control the classes on
  (`.form-table`, `.notice`, `.description`, printed by
  `do_settings_sections()`/`settings_errors()`) via CSS selectors
  scoped under `.hex-admin` in `admin.css`, since we can't add
  Tailwind classes directly to markup WP itself prints. Turned
  `.form-table`'s `<tr>`/`<th>`/`<td>` into a stacked block layout
  (label above field) instead of trying to preserve WP's two-column
  table layout in a dark theme.
- Updated `assets/js/admin.js`'s tab-switching `activate()` function to
  toggle indigo classes (`bg-indigo-600`/`text-white`) instead of the
  old hexnity-tinted ones, matching the new active-tab styling.

## The recurring gotcha, applied again

Every one of the new component classes that sets background, border,
or text color on a native form element (`.hex-field`, `.hex-btn-*`)
uses Tailwind's trailing `!` important-modifier on that specific
utility inside its `@apply` rule. Reason: WordPress core's own admin
CSS is **unlayered**, and Tailwind puts every utility inside a CSS
`@layer` — per the cascade spec, unlayered rules beat layered ones
regardless of specificity, so WP's own `input[type=text]{background:
#fff}` etc. would otherwise silently win over our (layered) component
class. This is the same fix documented in
`knoladge/tailwind-cascade-layers-vs-wp-admin.md`, just applied to a
new set of elements (form fields, not just `<a>` text color).

## Verified

- Both CSS bundles (`npm run build:css`) compile with no errors; grep
  confirms `indigo`, `hex-btn`, `hex-field`, `hex-color-swatch`, and
  `hex-label` all appear in the compiled `tailwind-admin.css`, and the
  front-end bundle still contains zero `hexnity` occurrences (brand
  stays admin-only, unaffected by this round).
- `vendor/bin/phpunit` (85/85) and `vendor/bin/phpcs
  --standard=phpcs.xml.dist` (0 errors/warnings) both pass unchanged —
  this was a presentation-only pass, no logic changed.
- Not yet checked in an actual browser (theme still not activated on
  the live site).

## Follow-up bug: the gotcha wasn't applied *everywhere*

Immediately after this round shipped, the user reported: *"double
check text colors, u changed the bg color black and kept all text
colors also dark. now cannt see them."* Root cause: the `!`
important-modifier fix above was only applied to the new isolated
components (`.hex-field`, `.hex-btn-*`, `.notice`, `.form-table`,
sidebar nav links) — every plain heading/paragraph/badge/link
scattered through the five page bodies (`inc/admin/partials.php`'s
stat tiles and header, and all of `page-dashboard.php`,
`page-updates.php`, `page-child-theme.php`, `page-about.php`,
`page-theme-options.php`) still used *un-`!`'d* Tailwind text-color
utilities (`text-white`, `text-gray-400`, `text-indigo-400`, etc.).
WP-admin core CSS apparently has its own unlayered rule(s) affecting
generic text color that beat those layered utilities the same way —
so on the new dark backgrounds, most body text was landing on WP's
own (light-theme-oriented) default color instead of ours, which read
as "invisible."

**Fix**: a one-off Python script (regex: match any
`(variant:)*text-(white|black|gray-NNN|indigo-NNN|emerald-NNN|amber-NNN
|red-NNN|hexnity-NNN)(/opacity)?` not already followed by `!`, and
append `!`) was run across all six admin PHP files, converting every
remaining bare text-color utility to its `!`-suffixed form in one
pass — 67 replacements total. `assets/js/admin.js`'s JS-toggled tab
classes (`text-white`, `text-gray-400`) got the same treatment by
hand, since those are set via `classList.toggle()` at runtime rather
than living in scanned PHP markup — Tailwind v4's automatic content
detection still picks them up from the `.js` file itself, so the
compiled CSS gained the matching `!important` rules.

**Lesson for next time**: when working inside `.hex-admin`, treat
`!` on text-color utilities as the *default*, not an exception applied
only where a bug was previously spotted — WP-admin core CSS reaches
further into generic elements than just `<a>`.

## Also in this round: bigger sidebar logo

User: *"make the logo little bigger."* Bumped the sidebar logo from
`h-7` to `h-11` and its container's padding from `py-5` to `py-6` in
`hex_render_admin_shell_start()` (`inc/admin/partials.php`) so it
doesn't feel cramped against the larger mark.
