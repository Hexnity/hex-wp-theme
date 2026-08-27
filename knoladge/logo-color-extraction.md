# How: deriving the brand palette from the actual logo file

## What I did

The user gave a direct URL to their logo
(`https://hexnity.com/images/hexnity-dark.png`) and asked to "use
these colors" for the admin menu. Rather than eyeballing the color
from a rendered preview (unreliable — compression, gamma, monitor
differences), downloaded the actual PNG into the theme
(`assets/images/hexnity-dark.png`, within the folder boundary) and
ran a Python/Pillow pixel-frequency scan over every opaque pixel to
get exact RGB counts, sorted by frequency.

Result: three dominant colors — `#009688` (81k px, the hex mark),
`#ffffff` (79k px, the wordmark text), `#000000` (17k px, the "H"
glyph). `#009688` is exactly Material Design's documented "Teal 500",
so extended it to a full scale using Material's own published Teal
50–900 values rather than inventing one — 500 already matched the
extracted pixel value exactly, so this is a real, traceable palette,
not an approximation.

## What I learned

- The `-dark` in `hexnity-dark.png` means "the variant meant to sit on
  a dark background" (the wordmark is white, invisible against a light
  background) — not "a dark-colored logo". Confirmed this by rendering
  the image directly (via the Read tool's image support) rather than
  just reading pixel data blind; seeing the actual image made the
  design intent obvious (and shaped a real decision: both the front-end
  header/footer and the admin page banner ended up dark, specifically
  *because* that's what this asset needs to render correctly).
- For a wp-admin sidebar menu icon (which WordPress renders very small,
  ~20px), the full wide wordmark logo doesn't work — cropped just the
  square-ish hex mark by scanning for the bounding box of non-white/
  non-transparent pixels in the left half of the image
  (`assets/images/hexnity-mark.png`), rather than fabricating a
  simplified icon from scratch.

## Related

[[brand-styling]] (features/) — where this palette and these assets
are actually used.
