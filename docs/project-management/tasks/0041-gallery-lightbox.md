---
type: task
tags: [cms2/task]
status: backlog
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
- [ ] Clicking/activating a gallery tile opens the image uncropped
      in an overlay; keyboard (Esc, arrows) and focus handling work
      (native `<dialog>` semantics)
- [ ] Prev/next navigation within the item's gallery
- [ ] No external/CDN assets; JS follows the theme's
      `ComponentType`/`ComponentInstance` pattern
- [ ] Without JS the gallery degrades to today's grid (no dead
      controls)
- [ ] Works identically on horse, feed and facility pages (one
      shared implementation)
- [ ] Verified over real HTTP + a real browser check (this is
      interactive UI; markup-level checks aren't enough)

## Related
- [[shh-stables-platform]]
- [[0039-product-images-featured-and-gallery]]
- [[0040-facility-images-featured-and-gallery]]
- [[0009-vendor-fullcalendar-library]]
