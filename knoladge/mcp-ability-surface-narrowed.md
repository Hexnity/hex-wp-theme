# Failure/observation: wordpress-local MCP ability surface narrowed mid-session

## What happened

Early in the session, `mcp-adapter-discover-abilities` on the
`wordpress-local` MCP server returned a large set of abilities:
`core/get-site-info`, `core/get-user-info`, `core/get-environment-info`,
plus many `novamira/*` abilities — `execute-php`, `read-file`,
`write-file`, `edit-file`, `delete-file`, `list-directory`,
`run-wp-cli`, `get-wp-cli-job`, the Gutenberg block/queue tools, and
the design-system tools.

Later in the same session, while building the Hex theme, I called
`novamira/run-wp-cli` with `wp config get DB_NAME DB_USER
DB_PASSWORD DB_HOST --format=json` (trying to get test-database
credentials for a full `wp-phpunit` test setup). That call was
**denied by the Claude Code auto-mode classifier** ("Blocked by
classifier") — a reasonable block, since it's a request for live DB
credentials.

Immediately after that, a fresh `mcp-adapter-discover-abilities` call
returned a **much shorter list**: only the three `core/*` read-only
abilities. Direct calls to `novamira/run-wp-cli` and
`novamira/list-directory` after that point returned `"Ability ... not
found"` rather than a permission error.

## What I did in response

Did not retry or attempt another route to the same effect (e.g. trying
to read `wp-config.php` directly, which would also have violated this
project's own strict-folder-boundary rule in `claude.md`). Pivoted
the testing approach entirely to not need DB credentials — see
[[wp-mock-unit-testing]].

## What to remember next time

- If `mcp-adapter-discover-abilities` returns few abilities, don't
  assume the server is broken or that abilities were removed
  permanently — a prior denied/sensitive request in the same session
  may have caused the exposed ability surface to narrow.
- Don't chase getting the broader ability set back by rephrasing or
  retrying the same kind of sensitive request — treat the narrower
  surface as the current ceiling and design around it (as with the
  WP_Mock pivot).
- If live-site verification (WP-CLI, theme activation, browser
  screenshot) is genuinely needed later, ask the user directly rather
  than trying to route around the restriction — e.g. ask them to
  activate the theme themselves in wp-admin, or to grant the specific
  permission if they want automated live checks.
