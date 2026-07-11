---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-11
updated: 2026-07-11
---
# Task: Lightbox for the product/facility image galleries

## Description
Client question (2026-07-11, reviewing 0039/0040): "Would it be
worthwhile adding a lightbox functionality to display the gallery of
related images?" — this is exactly the trigger 0039's Resolution
reserved ("no lightbox … revisit only if the client asks").

Assessment: **yes, worthwhile, modest scope.** The gallery tiles are
uniform 1:1 crops (deliberately, for a tidy grid), so a tall or wide
photo is never visible uncropped anywhere — for horse sales photos
in particular (conformation shots are portrait/landscape, and buyers
scrutinise them) a click-to-enlarge view genuinely adds value, not
just polish.

Implementation constraints and direction (decide finally in
implementation):
- **No CDN assets** — the platform vendors everything
  ([[0009-vendor-fullcalendar-library]]); a contrib lightbox module
  pulling a JS library would need the same vendoring treatment.
- Recommended: **skip contrib entirely** — a small custom lightbox
  on the native `<dialog>` element, following the theme's own JS
  component pattern (`lib/component.js` `ComponentType`/
  `ComponentInstance`, auto-wired `Drupal.behaviors`). The gallery
  markup already exists (`shh_common_image_gallery()`); the tiles
  become buttons/links opening the full image in a dialog, with
  prev/next inside. Progressive enhancement: without JS the grid
  stays exactly as today.
- Needs a full-size image URL per tile: extend the
  `shh_common` props family with the original file URL (kept
  *outside* the SDC `media` prop, whose shape is schema-validated).
- Keep the 0039 decisions otherwise: images only, featured = delta
  0, one shared presentation for horses, feed and facilities.

## Acceptance criteria
- [x] Clicking/activating a gallery tile opens the image uncropped
      in an overlay; keyboard (Esc, arrows) and focus handling work
      (native `<dialog>` semantics)
- [x] Prev/next navigation within the item's gallery (wrap-around;
      hidden entirely for a one-image gallery)
- [x] No external/CDN assets; JS follows the theme's
      `ComponentType`/`ComponentInstance` pattern
- [x] Without JS the gallery degrades to today's grid — better,
      in fact: tiles are plain links to the original files, so a
      no-JS click still shows the full image (no dead controls)
- [x] Works identically on horse, feed and facility pages (one
      shared implementation)
- [x] Verified over real HTTP + a real browser check — headless
      Chromium; see the honest scope note in the Resolution

## Resolution (2026-07-11)

Built as recommended — native `<dialog>`, no contrib module, no
vendored JS library, nothing external:

- **New `hestehoj:gallery` SDC** owning the whole presentation:
  the tile grid (moved out of `shh_common`'s render array — this
  supersedes 0039's "grid of image components, no new theme SDC"
  decision now that the gallery has behaviour), the `<dialog>`
  markup, `gallery.js` (ES module extending the theme's
  `ComponentInstance`, exactly like `accordion.js`) and
  `gallery.css`. Tiles pass `bleed: contained` and link to the
  original file via the image component's own `url` prop — that
  link *is* the no-JS fallback. JS upgrades the links to open the
  dialog: prev/next with wrap-around (hidden for single-image
  galleries), ArrowLeft/ArrowRight, backdrop-click to close;
  Escape and focus containment are native `showModal()` semantics.
  A `gallery--ready` marker class doubles as the JS-ran signal for
  verification.
- **`gallery.css` is deliberately plain CSS, not Tailwind**:
  `build/main.min.css` cannot be rebuilt on this machine (stale
  toolchain, see 0040), and SDC auto-attaches a component's own
  stylesheet — so the lightbox styling is rebuild-independent.
  Two dead utility classes found while moving the grid were fixed:
  `sm:grid-cols-3` was never in the compiled CSS (the 0039 grid
  had silently been 2-col at all widths — now `md:grid-cols-3`,
  which is compiled) and the Photos sections' `my-8` (→ `mb-8`).
- **`shh_common` reshaped** (task-0039-era internal API, one day
  old): `shh_common_image_gallery()` now takes media *entities*
  (it needs the original file URL, which display props don't
  carry) and renders the SDC; new companion
  `shh_common_image_media_list()` mirrors `…_props_all()`'s
  filtering but returns the entities. Callers updated: horse
  (slice off the hero image), facilities (whole list), feed
  (its cross-variation union, with non-image media filtered
  inside the gallery builder).

**Verified**: over real HTTP, all five image-bearing pages
(`/product/1`, `/product/6`, `/product/7`, `/oval-track`,
`/manege`) render the gallery markup with per-tile
`data-full-src`/`data-alt`, the dialog element, `gallery.js` as a
`type="module"` script and the lightbox CSS present in the served
aggregate; tile anchors point at the original files (the no-JS
path). In a **real browser** (headless Chromium/Brave): the
`gallery--ready` marker appears in the live DOM on product and
facility pages — the ES module loaded, the behavior attached and
`init()` wired both tiles without error — and a full-page
screenshot shows the complete page intact (title, hero, gallery,
add-to-cart, deposit CTA). Honest scope note: headless CLI can't
click, so the click→dialog interaction itself was verified by
construction (native `dialog.showModal()`) rather than driven —
worth one human click-through, which the client is doing anyway.

**Post-review tweak (same day)**: the client's click-through found
the dialog pinned to the top-left instead of centered. Cause:
Tailwind v4's preflight zeroes `margin` on *every* element —
including `dialog`, killing the UA stylesheet's `margin: auto`
that centers a modal dialog inside its `inset: 0` containing
block. Fixed with an explicit `margin: auto` on `.gallery--dialog`
(commented in gallery.css, since it looks redundant without
knowing the preflight behaviour). Verified visually in headless
Chromium via a test page loading the site's real stylesheets with
the dialog force-opened by `showModal()`: image centered both
axes, caption below, controls at the viewport edges.

**Found during verification, tracked separately as
[[0043-bee-price-frequency-form-reset]]**: the client's live test
edit of Lunge Ring (adding a photo via the new widget) silently
flipped `field_price_frequency` back to `hour` — bee's form alter
hardcodes that default over the stored value on every edit,
reintroducing 0020's 0.00-DKK pricing bug on any staff save. Data
re-fixed; patch under 0043.

## Related
- [[shh-stables-platform]]
- [[0039-product-images-featured-and-gallery]]
- [[0040-facility-images-featured-and-gallery]]
- [[0009-vendor-fullcalendar-library]]
