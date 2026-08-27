# Feature: GitHub-Based Theme Self-Updater

## What it is

Lets the theme check a GitHub repository for a newer version and
install it, the same way a WordPress.org theme update works — but for
a theme that isn't on WordPress.org. Architecture follows `github.md`
(a reference doc for a related project), adapted so nothing is
hardcoded: the repository, branch, and access token are all
admin-configured on the Updates page (`Theme name → Updates` in
wp-admin), per an explicit user decision not to hardcode a token or a
repo in the theme's source.

## Status

Implemented and unit-tested (the theme's own logic — see Tests
below). **Not yet exercised against a real GitHub repository** — no
repo has been configured, so the actual update flow (checking a real
API, downloading a real release, running the real upgrader) has not
been run end-to-end. Do that before relying on this in production.

## How it works

- **Library**: `yahnis-elsts/plugin-update-checker` v5.7, installed via
  Composer (`composer.json` "require", not "require-dev" — it's a
  runtime dependency) then **copied** into
  `inc/lib/plugin-update-checker/` and loaded from there via its own
  Composer-independent bootstrap file
  (`inc/lib/plugin-update-checker/plugin-update-checker.php`) — never
  via the top-level `vendor/` autoloader. This mirrors `github.md`'s
  exact reasoning: a folder literally named `vendor/` is a common
  default exclude target for deploy tooling, so the library that
  actually ships at runtime lives at a theme-owned path instead.
  `composer.json`/`composer.lock`/the top-level `vendor/` stay as a
  provenance record of how the library was originally obtained.
- **Facade class used**: `YahnisElsts\PluginUpdateChecker\v5\PucFactory`
  — the library's own documented "latest loaded minor version wins"
  facade (not the version-pinned `v5p7\PucFactory` directly), so this
  doesn't need to change if the vendored copy is ever upgraded to a
  newer 5.x release.
- **`hex_get_update_checker( $repo_override = null )`** (`inc/updater.php`)
  builds (and request-caches) the checker: repo + branch + activation
  key all come from `hex_get_github_repo()` / `hex_get_github_branch()`
  / `hex_get_activation_key()`, which read the `hex_github_repo`,
  `hex_github_branch`, `hex_activation_key` options. Returns `null`
  gracefully (never fatals) when no repo is configured or the vendored
  library is missing.
- Hooked to `init` (`add_action( 'init', 'hex_get_update_checker' )`)
  rather than called at bare file-scope, so it still runs on every
  request (registering the library's own ~12h background check and
  Themes-page notice) without doing consequential work at file-include
  time — a small, deliberate deviation from `github.md`'s literal
  "unconditional file-scope call" description, made for testability
  and because deferring to a hook is the more idiomatic WordPress
  pattern anyway.
- **`hex_check_for_theme_update()`** — forces an immediate check
  (bypassing the library's throttle), returns a one-line human-readable
  result.
- **`hex_perform_theme_update()`** — checks, and if an update exists,
  installs it via WordPress's own `Theme_Upgrader` +
  `Automatic_Upgrader_Skin` (the same mechanism as the native
  "Update Now" link) — not a hand-rolled download/extract routine.

## Admin UI

- **Settings** (`inc/admin/settings.php`): WordPress Settings API
  group `hex_updates` — Repository (validated as `owner/repo`),
  Branch (defaults to `main` at read time if left blank), Activation
  Key (password field, admin-set only — no default token ships in the
  theme).
- **Page** (`inc/admin/page-updates.php`, under `Theme name → Updates`):
  the settings form, plus two `admin-post.php`-backed action buttons —
  "Check for Updates Now" and "Check & Update Now" (nonce-protected,
  the latter with a JS confirm dialog since it can replace live theme
  files — `assets/js/admin.js`). Results show as an admin notice via a
  60-second transient (`hex_updates_log`).
- **Handlers** (`inc/admin/handlers.php`): `admin_post_hex_check_updates`
  / `admin_post_hex_perform_update`, both capability- and
  nonce-checked.

## Files involved

`inc/updater.php`, `inc/admin/settings.php`, `inc/admin/page-updates.php`,
`inc/admin/handlers.php`, `inc/lib/plugin-update-checker/` (vendored
library), `composer.json` (production require).

## Tests

`tests/UpdaterTest.php` — `hex_get_github_branch()`'s default-to-`main`
behavior, `hex_get_update_checker()`/`hex_check_for_theme_update()`/
`hex_perform_theme_update()`'s graceful "not configured" path, and both
sanitize callbacks (`hex_sanitize_github_repo`, `hex_sanitize_github_branch`).
Deliberately does **not** mock the third-party library's internals to
test a successful build/check/update — see
`knoladge/wp-mock-unit-testing.md` for why.

## Known gaps / next steps

- No end-to-end test against a real GitHub repo yet (needs a repo to
  point at — ask the user for one when ready to verify this live).
- No UI affordance yet to test the Activation Key / repo combination
  without leaving the Updates page (e.g. an inline "Test Connection"
  AJAX action) — not requested, worth offering later.
