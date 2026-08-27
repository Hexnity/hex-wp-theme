# Failure/fix: child-theme active-detection missed a manually-activated theme

## What happened

The user reported: *"theme is there, but still I can see Install Child
Theme"* — the child theme (`hexnity-wp-child`, the hand-written starter
created earlier and manually activated by the user rather than
installed through the "Fetch & Install Child Theme" button) was
genuinely the site's active theme, but the Child Theme admin page
still showed the Install section instead of the "Activated" status.

## Why

`hex_is_child_theme_active()` and `hex_get_child_theme_slug()` both
relied entirely on the `hex_child_theme_slug` **option** — which is
only ever written by `hex_install_child_theme_from_repo()`. A child
theme that reached disk and got activated any other way (a manual
copy — as actually happened, a `git clone`, WP-CLI, uploading a zip
via Appearance → Themes, etc.) left that option empty, so the code had
no way to know a child theme was active at all, regardless of reality.

## Fix

Made `hex_get_child_theme_slug()` prefer WordPress's own live state
over our own bookkeeping:

```php
function hex_get_child_theme_slug() {
	if ( is_child_theme() ) {
		return get_stylesheet();
	}
	return trim( (string) get_option( 'hex_child_theme_slug', '' ) );
}
```

The key insight: this code only ever executes because it's `require`d
from *this theme's own* `functions.php` — and WordPress always loads
the parent's `functions.php` even when a child is active. So if
`is_child_theme()` is true at the point this code runs, the active
child's declared `Template:` parent **must** be this theme; there's no
other way this file could be executing with `is_child_theme()` true.
`hex_is_child_theme_active()` simplified to just `is_child_theme()`
directly.

This single change fixed the reported bug (status card / Install
section now react to *any* active child, not just ones we tracked)
and, as a side effect, also fixed `hex_check_for_child_theme_update()`
/ `hex_perform_child_theme_update()` — those call
`hex_get_child_theme_slug()` too, so they'd have had the exact same
blind spot for a manually-activated child theme.

## Takeaway

When a feature tracks "the thing I created/installed" via its own
option/flag, ask whether the *real-world state* it's trying to
represent can also come about through a path that option doesn't
cover. Here, "a child theme is active" is directly knowable from
WordPress itself (`is_child_theme()`) — that's more reliable and
requires no bookkeeping at all, versus reconstructing it from "did our
own installer run." Prefer the platform's own source of truth over a
private flag whenever both exist for the same fact.

## Related

[[child-theme]] (features/) — the feature this affects.
