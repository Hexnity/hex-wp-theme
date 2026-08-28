---
name: admin-bare-button-reset-and-layer-order
description: Bare <button> elements keep native browser chrome in admin.css (no Preflight); appearance-none does NOT clear the UA's own explicit background-color; and Tailwind's own custom @layer order can silently beat a page's hover/active utility.
---

# Failure/fix: bare `<button>` chrome, `appearance-none` myth, and layer order

## What happened

User reported the Theme Options page's category tab buttons
(`.hex-tab-btn`, `inc/admin/page-theme-options.php`) rendered with a
white background and a visible border on a dark dashboard, wanting
"dark bg, without any borders, color should be white." First fix
attempt shipped as 1.5.2; user immediately reported it was still
white ("fuck why cannt u still add dark color to that buttons. still
they are white"). Root issue was the same both times — no Preflight
in this build — but the first fix's reasoning about what
`appearance-none` does was wrong, and that's the part worth
remembering.

## Why (three things stacked up)

1. **No Preflight in this build.** `assets/css/src/admin.css` only
   imports `tailwindcss/theme` and `tailwindcss/utilities` — no base
   reset — specifically so it doesn't disturb the rest of wp-admin's
   own chrome (documented in that file's header). Preflight is what
   normally strips a `<button>`'s native OS widget appearance. Without
   it, any bare `<button>` here keeps the browser's default rendering
   until something explicitly resets it.
2. **`appearance-none` does not clear the UA stylesheet's explicit
   `background-color`/`border` — this was the 1.5.2 mistake.** The
   assumption was that removing `appearance` makes background fall
   back to the CSS-initial `transparent`. It doesn't: the browser's
   UA stylesheet sets an *explicit* `background-color: ButtonFace`
   (or similar) and border on `<button>`, as a normal declaration
   independent of the `appearance` property. `appearance: none` only
   stops the browser from painting its own native widget graphic on
   top; it does not reset the box's own background/border values back
   to any default. Leaving `background-color` unset in the component
   (1.5.2's approach) meant that UA-set background just kept showing —
   exactly the "still white" the user saw. You must explicitly set
   `background-color` yourself; there's no free reset from
   `appearance-none` alone.
3. **A custom `@layer components` block, declared after
   `@layer utilities` in this file, outranks it for equal-importance
   rules.** Cascade layers are ordered by first appearance in the
   stylesheet; `admin.css` imports `utilities` near the top, then
   opens its own `@layer components { ... }` block further down — so
   `components` is the later-declared layer and normally wins over
   `utilities`, *regardless of selector specificity*. Once
   `.hex-tab-btn` had its own `background-color` (needed per point 2),
   it would permanently have beaten the page's `hover:bg-gray-800` and
   the JS-toggled active `bg-indigo-600` — both plain utilities in the
   earlier `utilities` layer — making hover/active backgrounds
   unreachable.

## Fix (as shipped, 1.5.3)

```css
.hex-tab-btn {
	@apply cursor-pointer appearance-none border-none! bg-black! text-white!;
}
```

```php
<!-- inc/admin/page-theme-options.php -->
class="hex-tab-btn ... hover:bg-gray-800! lg:w-full"
```

```js
// assets/js/admin.js
btn.classList.toggle( 'bg-indigo-600!', isActive );
```

Marking all three competing background declarations `!important` moves
the tie-break from "layer order" (which favored `components`, wrong
direction) to "importance tier", where **the earlier-declared layer
wins instead** — `utilities` (declared before `components` in this
file) beats `components` for `!important` rules. So `hover:bg-gray-800!`
and `bg-indigo-600!` (both `utilities`) now correctly override
`.hex-tab-btn`'s `bg-black!` (`components`) on hover/active, while the
component's black background still wins at rest against the browser's
own UA default (any author rule, important or not, always beats a UA
rule). Verified all three in the compiled `tailwind-admin.css` before
calling it done — this class of bug is invisible from reading the PHP
alone, it only shows up in the actual generated CSS.

## Takeaway

Don't assume `appearance: none` gives you a clean slate on
background/border for a bare `<button>` — it removes the native widget
*paint*, not the UA stylesheet's explicit property values. Set
`background-color` (and border) explicitly, always. And once a
component class sets a background that a sibling utility on the same
element also sets (hover/active states), check which `@layer` block
was declared first in the file — the later one wins ties at equal
importance, so either omit the property from the component, or (as
here, since omitting it turned out to be wrong) mark every competing
declaration `!important` and let the importance tier's *reversed*
layer order sort it out correctly. Always confirm in the compiled CSS
output, not just the source.

## Related

[[tailwind-cascade-layers-vs-wp-admin]] — the sibling gotcha (WP core's
*unlayered* CSS beating any of our layers) that this one is easy to
confuse with; that one is about our layers vs. WP's, this one is about
our own layers vs. each other, plus the `appearance-none` misconception.
[[admin-dark-theme-components]] (features/) — where `.hex-tab-btn` and
its sibling component classes live.
