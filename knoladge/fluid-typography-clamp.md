# Fluid typography via runtime clamp()

## Source

The user supplied a reference implementation from a different site of
theirs (a UIKit/WooCommerce theme, unrelated to this project — see the
pasted `:root` block with `--resko-*` vars, `ppm-child` font paths,
`.uk-h1` etc.) and asked for the *technique* — a CSS-only fluid type
scale via `clamp()`, no media queries — to be ported into this
theme's own admin-driven token architecture, fully renamed to this
project's conventions. Nothing from the source file (class names,
breakpoints, font, paths) was copied verbatim; only the interpolation
formula shape was reused.

## The formula

For a property that should be `$mobile` at viewport width `$mobile_bp`
and `$desktop` at viewport width `$desktop_bp`, linearly interpolating
(and holding flat) outside that range:

```
clamp(
  {mobile},
  calc({mobile} + ({desktop} - {mobile}) * ((100vw - {mobile_bp}) / ({desktop_bp} - {mobile_bp}))),
  {desktop}
)
```

Implemented as `hex_build_fluid_clamp()` in `inc/style-settings.php`.
Two edge cases it handles explicitly (both would otherwise produce
either a pointless `clamp()` or a divide-by-zero at compute time):

- `$mobile === $desktop` → returns the flat value, no `clamp()` at all.
- `$mobile_bp === $desktop_bp` → returns `$desktop` (an admin could
  save equal breakpoints; treating it as "always desktop" is safer
  than emitting a `calc()` that divides by zero).

## Why lengths, not the source's unitless-px numbers

The reference implementation stored breakpoints and sizes as **bare
unitless numbers** (`--resko-h1-desktop: 80;`) and multiplied by `1px`
inside every `calc()`. This project's fields are already CSS
`length` values (`rem`, matching every other size field in the
schema), and `calc()` allows subtracting/interpolating two same-unit
lengths directly — so `hex_build_fluid_clamp()` takes real lengths
(`'1.75rem'`, `'640px'`) and never needs the `* 1px` trick. One
mismatch to watch for: the mobile/desktop **breakpoints** are their
own separate `length` fields (`fluid_mobile_breakpoint`/
`fluid_desktop_breakpoint`, default `640px`/`1600px`, `global` group)
— if an admin sets a *heading* size in `rem` but a *breakpoint* in
`px`, the formula still works fine (the two length types don't need
to match each other, only `100vw` needs to be viewport-unit-compatible
with the breakpoints, which `px`/`rem`/`em` all are).

## Why this needs "modern" CSS calc() division

`(var(--desktop-bp) - var(--mobile-bp))` inside the denominator is a
`<length> / <length>` division happening *inside* `calc()`, at
render/compute time, using values that come from custom properties —
not two literal numbers a preprocessor could divide ahead of time.
This relies on CSS Values & Units Level 4's calc() type-checking (a
length divided by a length of the same type cancels to a `<number>`),
which all evergreen engines (Chromium, Firefox, Safari) have supported
since well before this theme's minimum-supported-browser bar. It's
what makes the whole scale **admin-editable without a rebuild** —
change a Theme Options value and the ratio recomputes in the browser,
no PHP/CSS regeneration of the interpolation math needed, only the
raw operand values change in `theme-options.css`.

## What's derived vs. what's stored

`hex_get_fluid_size_pairs()` is the single source of truth for which
`{key}_size_mobile` / `{key}_size_desktop` schema-key pair produces
which derived `--hex-{key}-size` output variable. The derived key is
**never** itself a schema field — `hex_build_style_tokens_css()`
explicitly skips writing a flat value for any key that appears as a
`hex_get_fluid_size_pairs()` output key (guards against a stale
leftover `--hex-h1-size: 3rem;` surviving from before the pair
existed and shadowing the freshly-computed `clamp()`). The raw
mobile/desktop values are *also* written to the file as their own
`--hex-{key}-size-mobile`/`-desktop` vars — not consumed by
`site-theme.css` currently, but available for a child theme that
wants the raw endpoints directly (e.g. to build its own custom
interpolation) without re-deriving them.

## Adding fluid sizing to a new field

See `features/design-system.md`'s "Adding a new token" section,
"A new fluid (mobile/desktop) text-size field" — three touches
(`hex_get_style_schema()` for the mobile/desktop pair,
`hex_get_fluid_size_pairs()` to register the derivation, then
reference the one derived `--hex-{key}-size` var from
`site-theme.css`, same as any other token).
