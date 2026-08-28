# Feature: Child Theme Install-from-GitHub + Independent Updates

## What it is

An admin-managed way to fetch, validate, and install a child theme
**from a GitHub repository** (not auto-generated — see "Design
correction" below), plus a fully independent GitHub self-updater for
whatever child theme ends up installed: separate repository, branch,
and access token from the main theme's own updater
([[github-updater]]), checked and installed separately.

Once a child theme is active, it also becomes the storage location for
the Theme Options design-token system — see
[[child-theme-css-token-architecture]] (knoladge/) and
[[design-system]] — which is why `hex_is_child_theme_active()` (this
feature's own function) is the single gate Theme Options editing
checks.

## Status

Implemented, unit-tested (the fetch/validate logic — see Tests
below), PHPCS clean. **Never run end-to-end**: no child theme has
actually been installed on disk this session (would write outside
this theme's folder, at `get_theme_root() . '/' . $slug`), and no
child-theme GitHub repo has been configured to test against a real
repository.

## Design correction (mid-task)

The first version of this feature **generated** a child theme from a
typed name (wrote a fresh `style.css` + `functions.php` from scratch).
The user corrected this: *"child theme is not create automatically...
it should get from github url like updates, it should ask valid
childtheme url and it should validate if that is actual child of this
parent, then it should download."* The feature was rebuilt around
fetch → validate → install instead:

1. **Fetch**: `hex_fetch_remote_child_style_css( $repo, $branch )` —
   `wp_remote_get()`s `https://raw.githubusercontent.com/{repo}/{branch}/style.css`
   (with the saved Activation Key as a Bearer-style `Authorization: token`
   header, for private repos), then reads its `Theme Name:` and
   `Template:` headers with regex.
2. **Validate**: `hex_validate_child_theme_repo()` — rejects with a
   clear `WP_Error` unless the fetched `Template:` value matches this
   theme's own slug (`get_template()`), case-insensitively. A
   repository that isn't declared as a child theme of *this* theme at
   all is refused before anything is downloaded.
3. **Install**: `hex_install_child_theme_from_repo()` — only if
   validation passed, builds the branch's GitHub zip URL
   (`https://github.com/{repo}/archive/refs/heads/{branch}.zip`) and
   installs it via `Theme_Upgrader->install()` — WordPress's own
   mechanism (the same one behind "Install Now" from a zip URL), not a
   hand-rolled download/unzip routine.

The `hex_child_github_repo` / `hex_child_github_branch` /
`hex_child_activation_key` settings (unchanged fields) now serve
**both** roles — the source to install from, and the source to check
updates against afterward — since it's the same repository either way.

## Design decision: management lives entirely in the parent

Installing, checking, and updating the child theme are all
orchestrated from *this* theme's admin (`inc/child-theme.php` + the
Child Theme page), not from inside the installed child theme itself.
This works correctly whether the parent or the child is the currently
*active* theme — WordPress always loads the parent theme's
`functions.php` first, even when a child theme is active, so this
theme's admin machinery keeps running either way.

## How it works

- **GitHub zip → local slug mismatch**: GitHub's zip archives extract
  to a `{repo}-{branch}/` folder, not our expected theme slug.
  `hex_install_child_theme_from_repo()` hooks `upgrader_source_selection`
  (WordPress's own documented extension point for exactly this
  situation) to rename the extracted folder before `Theme_Upgrader`
  finalizes the install.
- **Slug**: derived from the fetched `Theme Name:` header via
  `sanitize_title()` (falling back to the repo name if that header is
  missing). `Theme_Upgrader::install()`'s own "does this destination
  already exist" check is what prevents silently overwriting an
  unrelated existing theme — no separate collision-avoidance logic is
  needed on our side for install (unlike a from-scratch generator,
  which would have needed it).
- **The child's own updater**: `hex_get_child_update_checker()` builds
  a *separate* `PucFactory` checker instance from the parent's own
  (`inc/updater.php`) — pointed at
  `wp_get_theme( $slug )->get_stylesheet_directory() . '/style.css'`
  (works whether or not the child is the active theme) and its own
  `hex_child_github_repo` / `hex_child_github_branch` /
  `hex_child_activation_key` options. Reuses the parent's already-vendored
  `inc/lib/plugin-update-checker/` library file (a normal, expected
  child-theme convention) rather than duplicating a ~700KB library per
  child theme.
- **Full main/child isolation** (a standing contract now documented in
  `claude.md`): separate options, separate settings groups, separate
  `PucFactory` instances with separate `static` caches, separate
  transient logs (`hex_updates_log` vs. `hex_child_theme_log`), and
  `Theme_Upgrader` always called with the correct explicit slug.
  Checking/updating one theme never touches the other.
- **Active-state awareness**: `hex_is_child_theme_active()` is
  literally `is_child_theme()` (WordPress core). This is deliberate,
  not lazy — an earlier version compared `get_stylesheet()` to the
  `hex_child_theme_slug` *option*, which only gets written by
  `hex_install_child_theme_from_repo()`. A user activated a child
  theme that reached disk by any other route (a manual copy, `git
  clone`, etc. — as actually happened) and the page kept showing
  "Install Child Theme" anyway, since that option was never set. Fixed
  by making `hex_get_child_theme_slug()` itself prefer live reality:
  when `is_child_theme()` is true, this code is only executing at all
  because the active child's declared parent IS this theme (WordPress
  always loads the parent's `functions.php` too, however the child got
  there) — so `get_stylesheet()` is correct regardless of how the
  child was installed. Only when no child is currently active does it
  fall back to the stored option (whatever this theme's *own* installer
  last wrote), for the "installed but not currently active" status.
  This one change fixes the admin page's status detection *and* makes
  `hex_check_for_child_theme_update()` / `hex_perform_child_theme_update()`
  correctly target whatever child theme is actually active, not only
  ones installed through this feature.
  The admin page uses this to adapt: once a child theme is *active*,
  the "Install Child Theme" section is hidden entirely (installing
  over an active theme is disruptive) and replaced with a note
  pointing at "Check & Update Now" instead; the status card gets an
  "Activated" badge. If installed (per the tracked option) but a
  different theme is active, the card shows an "Installed, not
  active" badge and an "Activate in Appearance" link. If nothing is
  active or tracked, only the Install section shows.
- **Admin page** (`hex-theme-child-theme`, 4th tab): "Child Theme
  GitHub Repository" settings form (Settings API group
  `hex_child_updates`), a "Current Child Theme" status card (state
  described above), a "Fetch & Install Child Theme" button (hidden
  when active — no name/slug input either way, everything comes from
  the repository itself), and "Check for Updates Now" / "Check &
  Update Now" buttons scoped to the child only.

## Files involved

`inc/child-theme.php` (core logic, loaded unconditionally so the
child's checker also runs in the background — same pattern as
`inc/updater.php`), `inc/admin/page-child-theme.php`,
`inc/admin/settings.php` (child settings group), `inc/admin/handlers.php`
(`hex_handle_install_child_theme` / check / perform-update),
`inc/admin/menu.php` (4th submenu — note: the top-level sidebar label
is the fixed string "Hexnity WP", not this or the theme's real name),
`inc/admin/partials.php` (4th tab).

## Tests

`tests/ChildThemeTest.php` — `hex_fetch_remote_child_style_css()`
(header parsing, non-200 response, missing `Template:` header,
`wp_remote_get()` itself failing), `hex_validate_child_theme_repo()`
(accepts a matching `Template:`, rejects a mismatched one),
`hex_install_child_theme_from_repo()`'s validation-only early-return
paths (invalid `owner/repo` format, template mismatch),
`hex_get_child_github_branch()`'s default, `hex_is_child_theme_active()`
and `hex_get_child_theme_slug()` (no child active, any-child active
regardless of the tracked option, live-active-preferred-over-stored-option),
the "no child theme yet" graceful path for both check/perform
functions, and `hex_sanitize_child_github_repo()`. Deliberately does
**not** test the
actual `Theme_Upgrader->install()` / `WP_Filesystem` path, or a real
`PucFactory` build — same rationale as the main updater's tests (see
`knoladge/wp-mock-unit-testing.md`).

## Known gaps / next steps

- Never actually installed a child theme via the feature's own
  install path this session (a hand-written starter child theme was
  separately created at `../hexnity-wp-child/` for the user to push to
  GitHub — see `action-map.json` — but that didn't go through
  `hex_install_child_theme_from_repo()`). Ask the user before running
  the real install live, since it's an action outside this theme's own
  folder boundary — or have them click "Fetch & Install Child Theme"
  themselves in wp-admin.
- The active/inactive/none-installed UI states (`hex_is_child_theme_active()`)
  are unit-tested but never viewed in a real browser — verify the
  three states actually render as intended once the theme is active on
  a live site.
- No child-theme GitHub repo configured, so `hex_fetch_remote_child_style_css()`
  / `hex_install_child_theme_from_repo()` have never hit a real
  GitHub API or real repository.
- Only one child theme is tracked at a time (`hex_child_theme_slug` /
  `hex_child_theme_name` options hold a single value) — installing a
  second child theme overwrites which one the Child Theme page treats
  as "current", though the previous directory itself is left
  untouched on disk. Not built as a multi-child-theme manager; revisit
  if the user needs more than one.
- The GitHub raw-content URL scheme (`raw.githubusercontent.com/{repo}/{branch}/style.css`)
  assumes `style.css` lives at the repository root — a child theme
  with a non-standard layout (style.css in a subdirectory) wouldn't
  validate correctly. Not handled; revisit if needed.
