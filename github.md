# GitHub-Based Theme Self-Updater

This theme updates itself from a private GitHub repository instead of
(or in addition to) manual re-upload. This document describes how that
works today, based on a direct read of `inc/updater.php` and
`inc/admin-setup.php`.

## Summary

- **Source repo:** `https://github.com/shashinthalk/chindfundwptheme/`
- **Branch tracked:** `main`
- **Library used:** [`YahnisElsts/plugin-update-checker`](https://github.com/YahnisElsts/plugin-update-checker) v5.7, vendored (not loaded via Composer at runtime)
- **Where it's wired in:** `inc/updater.php`, required from `functions.php`
- **Admin UI:** ChildFund Setup → **Updates** tab (`inc/admin-setup.php`)
- **Current theme version:** read from `style.css`'s `Version:` header and the `CHILDFUND_VERSION` constant in `functions.php` (the two must always match — see CLAUDE.md's version-bump rule)

Once loaded, the library makes WordPress treat GitHub releases on that
repo (or, if there is no release, the latest commit on `main`) as
available theme updates — shown the normal way on **Appearance → Themes**
and the **Updates** screen, exactly like an update from WordPress.org
would look.

## Why the library isn't loaded from Composer's `vendor/`

The library was originally installed with `composer require
yahnis-elsts/plugin-update-checker`, and `composer.json` / `composer.lock`
/ the top-level `vendor/` folder are still kept in the repo as a
provenance record of that. But **the theme does not load the library
from there at runtime.**

Reason: a folder literally named `vendor` (or `node_modules`) is a common
*default* exclude target for Git-based hosting deploy tools, migration
plugins, and zip exporters — independent of this repo's own
`.gitignore`. That means the library could reach GitHub via `git push`
but still silently fail to reach the *live* site depending on how the
host deploys it. This was an actual production bug once (see
`claude.json` milestone `manual-check-and-activation-key`): the theme
worked locally but the "Enable auto-updates" UI never appeared on the
live site because `vendor/` never made it there.

**Fix:** the library is copied to `inc/lib/plugin-update-checker/` — a
plain, theme-owned path with no special meaning to any deploy tool — and
loaded via the library's own **Composer-independent standalone
bootstrap file**, `inc/lib/plugin-update-checker/plugin-update-checker.php`.
That file pulls in everything it needs itself (via `__DIR__`-relative
`require`s and its own internal autoloader), with zero dependency on
`vendor/composer/autoload_*.php`.

`inc/lib/plugin-update-checker/vendor/` (nested, ships with the library
itself) is a different, smaller `vendor/` folder containing Parsedown —
genuinely used by the library's own GitHub changelog-parsing code. To
avoid a naive `.gitignore` rule silently excluding it too, the repo's
`.gitignore` root-anchors the exclude as `/vendor/` (leading slash =
top-level match only), so this nested one stays tracked and ships with
the theme while the top-level Composer `vendor/` does not need to.

## How `inc/updater.php` builds the checker

`childfund_get_update_checker()` (static-cached per request):

1. Requires `inc/lib/plugin-update-checker/plugin-update-checker.php`. If that file doesn't exist on disk (e.g. it wasn't deployed), the function returns `null` and every caller degrades to an "Update checker is not available" message instead of fataling.
2. Calls `PucFactory::buildUpdateChecker()` with three arguments:
   - the GitHub repo URL above,
   - `get_template_directory() . '/style.css'` — **not** `__FILE__`. The library detects "this is a theme" by checking whether the given file *is* a theme's `style.css` or sits in the *same directory* as one (it doesn't support subdirectories). Since `inc/updater.php` lives in a subdirectory, passing `__FILE__` here throws a fatal `RuntimeException` — this was hit and fixed once already; don't revert it.
   - `'chindfundwptheme'` — the slug.
3. `setBranch( 'main' )` — tracks the `main` branch rather than defaulting to `master`.
4. `setAuthentication( childfund_get_activation_key() )` — see below.
5. `getVcsApi()->enableReleaseAssets()` — prefers a GitHub Release's uploaded zip asset over an auto-generated source-code archive, when a release exists.

The checker is instantiated unconditionally on every request (not
behind a conditional/hook), matching the library's documented usage
pattern, so its own internal hooks — the periodic ~12-hour background
check and the Themes-page update notice — stay registered.

## Authentication: the "Activation Key"

The GitHub repo is private, so update checks need a token. In the admin
UI this is deliberately labeled **"Activation Key"**, not "GitHub
token" or "PAT" — the GitHub-specific implementation detail is hidden
from the site owner by design (per an explicit request), even though
under the hood it's exactly a GitHub personal access token.

- A **read-only token is hardcoded** as `CHILDFUND_GITHUB_TOKEN` at the top of `inc/updater.php`, so the theme authenticates out of the box without any setup step. This was an explicit, informed instruction from the site owner (flagged once: since it ships inside the theme file, it would go out to every WordPress install running this theme — relevant only if the theme is ever handed to another site).
- `childfund_get_activation_key()` returns the `childfund_activation_key` **wp-admin option** if one has been saved, otherwise falls back to the hardcoded constant. This lets a site owner rotate/replace the token from wp-admin without editing code.
- The Activation Key field lives on the ChildFund Setup → **Updates** tab, rendered as a `type="password"` input (value never shown in plain text in the markup beyond the attribute itself) with placeholder text "Leave blank to use the built-in key".

## Manual check / update actions

Themes (unlike plugins) don't get a built-in "check now" UI from the
library — only plugins do. So `inc/updater.php` adds two functions that
the ChildFund Setup admin page calls directly:

### `childfund_check_for_theme_update()`

Calls the checker's real `checkForUpdates()` method, which forces an
immediate remote check and **bypasses the library's normal ~12 hour
throttle**. Returns a one-line log: either `"Update available: version
X"` or `"You are running the latest version (X)"`.

### `childfund_perform_theme_update()`

Same check, and if an update is available, installs it using
**WordPress's own upgrader machinery** — `Theme_Upgrader` +
`Automatic_Upgrader_Skin` — the exact mechanism behind the native
"Update Now" link on the Themes screen. It is not a hand-rolled
download/extract routine. Before invoking the upgrader it calls
`wp_update_themes()` to refresh the `update_themes` transient the
upgrader reads the download URL from. Reports success with the new
version number, or WordPress's own error message on failure.

Both functions are guarded with `function_exists()` checks wherever
they're called from `inc/admin-setup.php`, so a deploy that's missing
`inc/updater.php` for some reason degrades gracefully instead of
fataling the admin page.

## The Updates tab (ChildFund Setup)

`inc/admin-setup.php` → `childfund_render_tab_updates()` renders:

1. **Current version** — echoes the `CHILDFUND_VERSION` constant.
2. **"Check for Updates Now"** button — a form posting `childfund_check_updates`, nonce-protected (`childfund_check_updates`). The handler calls `childfund_check_for_theme_update()`, stores the resulting log lines in a 60-second transient (`childfund_setup_log`), and redirects back to the tab so the result survives the redirect (POST/redirect/GET).
3. **"Check & Update Now"** button — a form posting `childfund_perform_update`, nonce-protected (`childfund_perform_update`), with a JS `confirm()` dialog first since this action can replace live theme files. The handler calls `childfund_perform_theme_update()`, same transient/redirect pattern.
4. **Activation Key** field — a separate form posting `childfund_save_update_settings`, nonce-protected, which just does `update_option( 'childfund_activation_key', ... )` on the sanitized POST value.

All three POST handlers live together in `inc/admin-setup.php`'s
central admin-post dispatch block, each gated by its own
`check_admin_referer()` nonce check before touching anything.

## Practical notes for whoever deploys this theme

- **Deploying via anything other than direct file copy / git pull on the server** (zip export, migration plugin, some Git-based hosts) — double-check that `inc/lib/plugin-update-checker/` actually lands on the live server. This exact class of tool has silently dropped update-checker files once before (see the `vendor/` incident above); `inc/lib/` was chosen specifically to avoid the *known* problem, but any exclude-by-folder-name convention could in principle still bite a different path.
- If **`Appearance → Themes` doesn't show an available update** even though a newer commit/release is genuinely on GitHub: use ChildFund Setup → Updates → "Check for Updates Now" first — it bypasses the ~12 hour throttle and will report a clear reason (checker not available, vs. genuinely up to date, vs. an update was found) rather than silently waiting.
- The hardcoded default `CHILDFUND_GITHUB_TOKEN` is a **live GitHub token**. If this theme is ever handed to a different site/owner, that token should be rotated or the receiving owner should set their own value in the Activation Key field rather than relying on the shipped default indefinitely.
- Style.css's `Version:` header and `functions.php`'s `CHILDFUND_VERSION` constant must always match (CLAUDE.md's strict versioning rule) — the update checker (and WordPress core) determines "is there something newer" by comparing against `style.css`'s `Version:` header specifically, so letting the two drift apart doesn't just break cache-busting, it also breaks update detection accuracy.
