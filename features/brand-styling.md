# Feature: Tailwind CSS, Animate.css, and Hexnity Brand Styling

## What it is

Replaces almost all hand-written CSS with a Tailwind CSS v4 build
(utility classes directly in markup) plus vendored Animate.css for a
handful of restrained entrance animations, on **both** the front end
and admin. The Hexnity brand (logo + color palette extracted from its
pixels) is **admin-only, by explicit user correction mid-task** — the
front end uses Tailwind's default neutral gray scale and has no logo
image, no brand color, and no `theme.css` import at all. See
"What changed mid-task" below.

## Status

Implemented. Front-end and admin builds both compile cleanly, PHPCS
and PHPUnit still pass. **Not yet viewed in a browser** — no live-site
smoke test performed this session (theme still not activated).

## The brand assets

- `assets/images/hexnity-dark.png` — the logo the user provided
  (`https://hexnity.com/images/hexnity-dark.png`), downloaded as-is.
  Its wordmark text is **white** — this variant is designed to sit on
  a dark background (hence the filename), not a light one.
- A cropped square version of the mark (`hexnity-mark.png`) was tried
  for the wp-admin sidebar menu icon, then **removed at the user's
  request** — the sidebar icon is a plain `dashicons-art` dashicon now.
  The full logo (`hexnity-dark.png`) is only used, larger (`h-16`), in
  the admin page banner.
- **Palette**: the mark's exact pixel color is `#009688` — extracted
  with a Python/Pillow pixel-frequency scan of the source PNG, not
  guessed. That value is exactly Material Design's "Teal 500", so the
  full `hexnity-{50..900}` scale in `assets/css/src/theme.css` uses
  the standard, well-documented Material Teal palette (500 matching
  the logo exactly) rather than an invented scale.

## Where the brand shows up (admin only)

- `inc/admin/partials.php`'s `hex_render_admin_shell_start()` renders a
  dark (`bg-hexnity-900`) sidebar with the logo, a five-item icon nav
  (Dashboard / Updates / Child Theme / Theme Options / About), and a
  version footer — a deliberate pairing, since dark is exactly the
  background this logo variant needs to be legible. See
  [[theme-admin-dashboard]] for the full app-shell layout this
  produces. The wp-admin sidebar *menu* icon (WordPress's own left
  nav, not this shell) is a plain `dashicons-art` dashicon, not a logo
  image (tried, then removed at the user's request).
- **The `hexnity-*` palette is no longer used everywhere in admin** —
  explicit user correction: *"u dont need to strict with logo
  colors."* It's now confined to the sidebar only; every other
  interactive element (links, active tab/nav states, buttons, focus
  rings, stat-tile icon chips) uses Tailwind's built-in `indigo` scale
  instead, and the content pane is dark gray (`gray-900`/`gray-950`),
  not brand-tinted. See `knoladge/admin-dark-theme-components.md`.
- **The front end uses none of this.** Header/footer are a plain
  neutral light theme (`border-gray-200`, `text-gray-*`), no logo
  image anywhere, no `hexnity-*` classes, and `assets/css/src/front.css`
  doesn't even import `theme.css` — so the `hexnity-*` utilities and
  the brand-tinted `.prose-hexnity` don't exist in the compiled
  front-end CSS at all, not just "unused in markup".

## What changed mid-task

The logo/palette were originally applied to *both* the front end and
admin (dark header+footer with the logo, `prose-hexnity`, hexnity-tinted
buttons/pagination everywhere). The user corrected this partway
through: *"just use logo and colors only for admin pages. dont use it
for theme."* Reverted every front-end template to a neutral gray
palette and removed the logo/`theme.css` import from the front-end
build entirely, while leaving the admin implementation untouched.
Tailwind CSS and Animate.css themselves stayed on the front end (that
part of the request wasn't retracted) — only the Hexnity-specific
branding was pulled back to admin-only.

## Tailwind CSS setup (v4)

- **Two separate builds**, both from `assets/css/src/*.css`:
  - `front.css` → `assets/css/tailwind.css` — full Tailwind incl.
    Preflight (base reset) + `@tailwindcss/typography` (for
    `.entry-content`/`prose`). This build fully controls the front end.
  - `admin.css` → `assets/css/tailwind-admin.css` — **theme + utilities
    only, no Preflight**. Preflight resets element-level styles
    (`h1`-`h6`, `button`, etc.) globally; on an admin screen we share
    with the rest of wp-admin's own chrome (toolbar, sidebar, other
    plugins), that would visually break things outside our own pages.
    Utility classes are opt-in per element, so skipping Preflight here
    is the documented, correct fix — not a workaround.
  - `theme.css` defines the `hexnity` color scale as Tailwind v4
    `@theme` tokens. Only `admin.css` imports it — `front.css`
    deliberately does not (see "What changed mid-task" above).
- **Build**: `npm install` then `npm run build:css` (or
  `build:css:front` / `build:css:admin` separately). Compiled output
  files are committed/shipped (like `inc/lib/`) since WordPress can't
  run an npm build in production — `node_modules/` is gitignored, the
  compiled CSS is not.
- **No `tailwind.config.js`** — v4 uses CSS-first configuration
  (`@theme` blocks) and automatic content detection (it scans the
  project respecting `.gitignore`), so explicit `@source` directives
  were added mainly for clarity/robustness rather than necessity.

## Animate.css

- Vendored at `assets/vendor/animate.min.css` (copied from the npm
  package, same pattern as `inc/lib/plugin-update-checker/` — a
  real dependency, tracked version, but loaded from a theme-owned
  path rather than `node_modules/`).
- Enqueued front-end only, alongside the Tailwind build.
- Used sparingly: `animate__fadeIn` on the header, entry cards, and
  page-not-found/no-results states — not on every element. A
  `prefers-reduced-motion` override in `front.css` collapses all
  Animate.css animations to effectively instant for users who've
  asked for reduced motion.

## Files involved

`assets/css/src/{theme,front,admin}.css`, `assets/css/tailwind.css`,
`assets/css/tailwind-admin.css` (compiled), `assets/vendor/animate.min.css`,
`assets/images/hexnity-dark.png`, `package.json`, `inc/enqueue.php`,
`inc/admin/menu.php`, `inc/admin/partials.php`, every front-end
template (rewritten to Tailwind utility classes in place of the old
hand-written `style.css` rules), every `inc/admin/page-*.php`.

## Known gaps / next steps

- No live-browser check yet — verify header/footer contrast, mobile
  nav toggle, and the admin banner once the theme is activated.
- The `.prose-hexnity` custom-property override described in
  `knoladge/tailwind-v4-admin-integration.md` (the fix for
  `@tailwindcss/typography`'s `prose-{color}` modifier not supporting
  arbitrary brand hues) was removed entirely once the front end
  stopped using the Hexnity palette — the knoladge entry is kept as a
  record of what was tried and learned, not as a description of
  current code.
