# How: WordPress Coding Standards (WPCS) setup and the one violation found

## What I did

Installed `squizlabs/php_codesniffer` + `wp-coding-standards/wpcs` +
`phpcompatibility/phpcompatibility-wp` +
`dealerdirect/phpcodesniffer-composer-installer` via Composer (the
installer plugin auto-registers the ruleset search paths — no manual
`phpcs --config-set installed_paths` needed). Wrote `phpcs.xml.dist`
using the full `WordPress` ruleset (not just `WordPress-Core`) plus
`PHPCompatibilityWP` pinned to `testVersion 8.0-` to match this
theme's declared `Requires PHP: 8.0`.

One sniff exclusion: `WordPress.Files.FileName`, because this theme
intentionally uses WordPress's own template-name file naming
(`404.php`, `template-default.php`, etc.) rather than the sniff's
preferred `class-*`/underscore convention — that's expected and
correct for a theme, not a real violation.

## What I learned / what failed

Running `vendor/bin/phpcs --standard=phpcs.xml.dist` found exactly one
real error: in `comments.php`, `number_format_i18n( get_comments_number() )`
was passed straight into `printf()` as an argument, unescaped —
`WordPress.Security.EscapeOutput.OutputNotEscaped`. Even though
`number_format_i18n()`'s output is always a numeric string (so this
was never exploitable), WPCS still flags it because it can't prove
that statically — and the project rule here is "escape all output,"
not "escape all output except when you're confident it's safe."
Fixed by wrapping it: `esc_html( number_format_i18n( get_comments_number() ) )`.

## Takeaway for future work in this theme

Run `vendor/bin/phpcs --standard=phpcs.xml.dist` after every change
that touches a `.php` file, before considering the change done — it
catches exactly this class of issue (unescaped output, missing text
domain, etc.) that's easy to miss by eye.
