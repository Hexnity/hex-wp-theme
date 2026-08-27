# How: running Tailwind v4 inside wp-admin without breaking it, and what I got wrong first

## Context

The user asked for Tailwind CSS (+ Animate.css) as the styling
framework for both the front end and the admin dashboard, with
brand colors from the Hexnity logo. See [[brand-styling]] for the
full feature.

## What I did

1. **Two separate Tailwind builds, not one.** The admin pages render
   inside wp-admin, sharing the page with WordPress's own toolbar,
   sidebar, and other plugins' UI. Tailwind's Preflight (base reset)
   applies element-level resets globally (`h1`-`h6` margins, `button`
   appearance, etc.) — if the admin build included it, it would have
   visually broken everything else on the screen, not just our pages.
   Fixed by importing only `tailwindcss/theme` + `tailwindcss/utilities`
   for the admin build (`assets/css/src/admin.css`), skipping
   `tailwindcss/preflight` entirely. Utility classes only affect
   elements that use them, so this is a real, documented fix — not a
   workaround.
2. **Verified it, rather than assuming.** After building, grepped the
   compiled `tailwind-admin.css` for Preflight's signature rule
   (`*,::before,::after{box-sizing:border-box...}`) and got zero
   matches, confirming it was actually excluded — and separately
   confirmed the `hexnity-*` color utilities *were* present, so the
   admin build wasn't just empty/broken.
3. **v4 needs no `tailwind.config.js`.** Configuration is CSS-first now
   (an `@theme { --color-hexnity-500: #009688; ... }` block), and
   content scanning is automatic (respects `.gitignore`). Didn't add a
   config file at all — just `@source` directives in each entry file
   for clarity.

## What I got wrong initially (and fixed)

Assumed `@tailwindcss/typography`'s `prose-{color}` modifier (e.g.
`prose-slate`) worked like other Tailwind color modifiers — that I
could write `prose-hexnity` and get an arbitrary brand-tinted prose
theme (links, headings, bold, etc. all in teal). Wrote the templates
that way, rebuilt, and grepped the output for `prose-hexnity` —
**nothing was generated**. Checked further: `prose-{color}` only ever
selects which of Tailwind's five *built-in gray scales* (slate, gray,
zinc, neutral, stone) prose uses for body text — it was never designed
to accept arbitrary custom colors for accents at all, in v3 or v4.

Fixed by using the plugin's actual documented customization path:
manually overriding its CSS custom properties (`--tw-prose-links`,
`--tw-prose-headings`, etc.) under a `.prose-hexnity` class I define
myself in `front.css`, pointing them at the `--color-hexnity-*` tokens.
The markup (`class="prose prose-hexnity"`) didn't need to change —
only the assumption about *how* `prose-hexnity` would come to exist.

## Takeaway for future work in this theme

Before assuming a plugin/framework feature does what its naming
pattern suggests, build once and grep the actual output — cheap way
to catch a wrong assumption before it propagates through a dozen
template files (as it briefly did here).
