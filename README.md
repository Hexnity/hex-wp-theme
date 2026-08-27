# Hexnity WP

A lightweight, standards-compliant classic (non-block) WordPress theme with a GitHub-based self-updater, an optional validated child theme, and a full admin-configurable design-token system.

- **Author:** Nishan Shashintha ([shashinthalk.cc](https://shashinthalk.cc))
- **Theme URI:** [hexnity.com](https://hexnity.com)
- **License:** GPLv2 or later — see [LICENSE](LICENSE)
- **Requires:** WordPress 6.4+, PHP 8.0+

## Features

- **Theme foundation** — `functions.php` + `inc/`, all required templates (`header.php`, `footer.php`, `index.php`, `page.php`, `single.php`, `archive.php`, `search.php`, `404.php`, `comments.php`) and exactly three page templates: Default, Full Width, Canvas.
- **GitHub self-updater** (`inc/updater.php`, vendored `yahnis-elsts/plugin-update-checker`) — repo, branch, and access token are configured from the admin Updates page; nothing is hardcoded.
- **Child theme support** (`inc/child-theme.php`) — fetches a child theme from a GitHub repo, validates its `style.css` `Template:` header actually points at this theme before installing it. The installed child theme gets its own fully independent updater (separate repo/branch/token from the main theme).
- **Admin dashboard** (`inc/admin/`) — Dashboard, Updates, Child Theme, Theme Options, and About pages under one dark, Hexnity-branded app-shell menu.
- **Design-token system** — ~146 admin-editable fields across 12 groups (Typography, Spacing, Colors, Buttons, Forms, Cards, Sections, Global radius, Tables, Alerts, Badges, Icons), applied site-wide via runtime CSS custom properties. Includes an API-free Google Fonts picker.
- **Styling** — Tailwind CSS v4 + Animate.css, compiled separately for front end and admin.

## Requirements

- WordPress 6.4+
- PHP 8.0+
- Node.js + npm (for building CSS)
- Composer (for dev tooling: linting and tests)

## Development setup

```bash
composer install
npm install
npm run build:css
```

## Testing & linting

```bash
vendor/bin/phpunit                              # PHPUnit test suite (WP_Mock-based, no live WP DB)
vendor/bin/phpcs --standard=phpcs.xml.dist      # WordPress Coding Standards
```

## Building CSS

```bash
npm run build:css          # builds both front-end and admin CSS
npm run watch:css          # watches the front-end build during development
```

Compiled CSS is committed to the repo since WordPress cannot run an npm build in production.

## Documentation

This project keeps its own internal documentation, used to onboard AI assistants and contributors working on the theme:

- `CLAUDE.md` — start-here index and project rules
- `project.json` — structural index of files/functions
- `features/*.md` — one file per complete feature
- `knoladge/` — implementation notes and lessons learned
- `action-map.json` — history of actions taken against requirements

## License

GPLv2 or later. See [LICENSE](LICENSE) for the full text.
