# Feature: Testing & Coding-Standards Tooling

## What it is

Dev-only tooling (no runtime dependencies) so every future change to
this theme can be linted and tested the same way, per the user's
explicit "always create test cases" / "industry standard code"
requirement.

## Status

Implemented and verified working as of 2026-08-27:
- `vendor/bin/phpunit` → 27 tests, 34 assertions, all passing.
- `vendor/bin/phpcs --standard=phpcs.xml.dist` → 0 errors (1 was found and fixed — see below).
- `php -l` across every theme `.php` file (excluding `vendor/`, `tests/`) → no syntax errors.

## How it works

- **`composer.json`** — dev requires only: `squizlabs/php_codesniffer`,
  `wp-coding-standards/wpcs`, `phpcompatibility/phpcompatibility-wp`,
  `dealerdirect/phpcodesniffer-composer-installer` (auto-registers the
  WPCS ruleset paths), `phpunit/phpunit` (^9.6), `10up/wp_mock` (^1.0).
  Composer scripts: `composer lint`, `composer lint:fix`, `composer test`.
- **`phpcs.xml.dist`** — the `WordPress` ruleset (full: Core + Docs +
  Extra) plus `PHPCompatibilityWP` (testVersion 8.0-), text-domain
  checks scoped to `hex`. `WordPress.Files.FileName` is excluded since
  this theme intentionally uses WordPress-template-name filenames
  (`404.php`, `template-*.php`) rather than the sniff's preferred
  naming.
- **`phpunit.xml.dist`** — bootstraps `tests/bootstrap.php`; suite is
  every `tests/*Test.php`.
- **`tests/bootstrap.php`** — see
  [[wp-mock-unit-testing]] in `knoladge/` for the full reasoning. In
  short: uses `WP_Mock` instead of a full WordPress test install (no
  database required), requires every `inc/*.php` file exactly once,
  and defines a few WordPress core functions (`add_action`,
  `add_filter`, `__`, `esc_html__`) as plain permanent stubs rather
  than per-test `WP_Mock` expectations, because their behavior is
  never itself under test.

## Files involved

- `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`
- `tests/bootstrap.php`
- `tests/SetupTest.php`, `tests/TemplateTagsTest.php`,
  `tests/CustomizerTest.php`, `tests/SecurityTest.php`,
  `tests/ThemeFilesTest.php`

## Known gaps / next steps

- No live-WordPress integration tests (would need `wp-phpunit` + a
  real test database; DB-credential access was blocked this session —
  see [[mcp-ability-surface-narrowed]] in `knoladge/`). Current tests
  cover the theme's own decision logic, sanitization, and file/version
  consistency, not full WordPress request/response behavior.
- Template-tag output functions that print heavy WP-generated markup
  (`hex_posted_on()`, `hex_posted_by()`, `hex_entry_footer()`) are
  deliberately not unit-tested — they're thin wrappers around several
  WP core functions each, and mocking all of them would test the
  mocks more than the theme's own logic. They should instead be
  covered by a manual/browser smoke test once the theme is activated.
- No CI workflow file yet (e.g. GitHub Actions) to run `composer test`
  and `composer lint` automatically — not requested, but worth
  offering once the theme has a git remote.
