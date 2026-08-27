# Failure/fix: WP core's unlayered admin CSS beat Tailwind's `.text-white`

## What happened

After building the admin tab bar (`inc/admin/partials.php`) with
`text-white` / `border-white` utility classes, the user reported the
nav links were still rendering WordPress core's default admin link
blue (`#3858e9`, from wp-admin's own `a { color: #3858e9; }` rule),
pasting the rendered HTML as proof the classes were present.

## Why

Tailwind v4 wraps every utility inside a CSS `@layer` (`theme`,
`base`/preflight, `utilities`). WordPress's own core admin CSS is
**not** written inside any `@layer`. Per the CSS cascade-layers spec,
**any unlayered rule beats every layered rule, regardless of selector
specificity** — so WP core's plain element selector `a { color: ... }`
(specificity 0,0,1) overrode Tailwind's `.text-white` class selector
(specificity 0,1,0) even though a class selector normally always wins
over an element selector. This has nothing to do with load order or
`!important` in the usual sense — it's the layer rule alone.

This will affect *any* Tailwind color utility applied to an `<a>` (or
other element WP core admin CSS already styles) on our admin pages,
not just this one nav bar — noting that in case it comes up again
elsewhere.

## Fix

Tailwind v4's own documented escape hatch for exactly this class of
conflict: the trailing `!` important-modifier (`text-white!`, not
v3's leading `!text-white`), which compiles to
`.text-white\!{color:var(--color-white)!important}`. Applied it to
the four color utilities in the nav (`border-white!`, `text-white!`,
`text-white/70!`, `hover:text-white!`) and verified in the compiled
CSS that `!important` was actually present before calling it fixed.

## Takeaway

When embedding Tailwind utilities into a page that already has
significant *unlayered* legacy CSS (WordPress admin, most CMS admin
themes, many older sites), specificity comparisons alone won't
predict the outcome — check for `@layer` involvement first. The `!`
modifier is the correct, permanent fix here (not a hack to be
"cleaned up later") since the layer mismatch is structural and won't
resolve itself.

## Related

[[brand-styling]] (features/) — the Tailwind admin build this affects.
