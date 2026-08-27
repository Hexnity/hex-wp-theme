# How: Unit-testing theme PHP with WP_Mock (no live WP database)

## Context

Needed real, running PHPUnit test coverage for the Hex theme's PHP
logic. The standard "full fidelity" approach (`wp-phpunit/wp-phpunit`
+ a real WordPress test database via `install-wp-tests.sh`) needs DB
credentials. Attempting to read those via
`novamira/run-wp-cli` (`wp config get DB_NAME DB_USER DB_PASSWORD
DB_HOST`) was **blocked by the Claude Code auto-mode classifier** as a
denied action (see [[mcp-ability-surface-narrowed]] for the follow-on
effect on the MCP ability list). Didn't attempt to work around it —
pivoted the testing strategy instead.

## What I did

Used `10up/wp_mock` (Mockery-based function mocking for WordPress
core functions) with plain PHPUnit, requiring **no WordPress install
and no database at all**. This works well specifically because the
theme's custom logic (decision functions, sanitize callbacks, data
maps) only *calls* a handful of WP core functions rather than needing
full request/response/database behavior.

Key structural decision in `tests/bootstrap.php`: define `add_action`,
`add_filter`, `__`, and `esc_html__` as **plain, permanent PHP
functions** (guarded by `function_exists()`), not as per-test
`WP_Mock::userFunction()` expectations. Reasoning: every `inc/*.php`
file calls `add_action()`/`add_filter()` once at the top level the
moment it's `require`d — and PHP can't redeclare a function, so each
`inc/` file can only be `require`d once across the whole test run.
That means the hook-registration calls fire exactly once, at
bootstrap time, before any individual test's `WP_Mock` expectations
exist — so they can't be verified per-test anyway, and the cleanest
fix is to not route them through `WP_Mock` at all. Individual test
files instead use `WP_Mock::userFunction()` only for the WP functions
whose *behavior* is actually part of what's being tested (`is_page()`,
`get_page_template_slug()`, `wp_strip_all_tags()`,
`sanitize_text_field()`, `apply_filters` via `WP_Mock::onFilter()`).

## What I learned

- `WP_Mock`'s mock registry resets per test (via `Mockery::close()` in
  its `TestCase::tearDown()`), so any `WP_Mock::userFunction()` call
  made at bootstrap time (outside of any single test) would only be
  honored for the *first* test that runs — a real gotcha if not
  anticipated. Plain stub functions for "not under test" WP wrappers
  sidestep this entirely.
- For sanitize-style tests, using `andReturnUsing()` with PHP's own
  `strip_tags()`/`trim()` (not the real `wp_strip_all_tags()`/
  `sanitize_text_field()`, which don't exist in this environment) gave
  realistic-enough behavior to verify the *theme's* composition logic
  (strip tags, then sanitize) without needing WordPress loaded.
- Tests that don't touch any mocked WP function at all (e.g.
  `tests/ThemeFilesTest.php`, pure filesystem/regex checks) can just
  extend plain `\PHPUnit\Framework\TestCase` — no need to extend
  `WP_Mock\Tools\TestCase` everywhere.

## Related

[[testing-and-tooling]] (features/) — the full tooling setup this
belongs to. [[mcp-ability-surface-narrowed]] — why the DB-credential
route wasn't available.
