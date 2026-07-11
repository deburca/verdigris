---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-11
updated: 2026-07-11
---
# Task: Product images — featured image in lists, full gallery on the item

## Description
Client request (2026-07-11), covering horses AND the new straw/wrap
items from [[0038-straw-and-wrap-sale-items]]: staff upload images
per item; list views show **one featured image** per item; the
individual item page shows **all** uploaded images.

Current state (why this is mostly a display task for horses):

- The `horse` variation already has `field_media` — an
  unlimited-cardinality media reference (image, video and
  remote_video bundles) — so multi-image *upload and storage*
  already exist for horses.
- But display renders only the **first** image everywhere:
  `shh_common_image_media_props()` (`shh_common.module`) returns
  props for the first item only, and both consumers use it — the
  `/horses` catalog card (`HorseCatalogController`, task 0019/0031)
  and the individual product page
  (`shh_horse_product_display.module`, task 0031). A horse with
  five images shows exactly one, everywhere.

So the work splits cleanly:
1. **Horses (independent of 0038, can start first)**: keep the
   card's single image, add an all-images gallery to the product
   page.
2. **Straw/wrap (after 0038's bundles exist)**: give the new
   variation types the same `field_media` field + form widget, and
   the same featured/gallery display treatment.

Decisions to make during implementation (record reasoning):

- **What "featured" means**: recommend media delta 0 — the first
  item in the field, staff reorder in the widget — over a separate
  featured flag/field. Matches what the card already shows today,
  so nothing moves for existing content.
- **Gallery rendering**: hestehoj has an `image` SDC but no
  gallery/carousel component. Decide: a grid/`group` of `image`
  components vs. a new `gallery` SDC in the theme. Either way stay
  inside the single site-wide custom-code SDC-composition approach
  (0031/0030 — no Canvas, render arrays via `#type: component`).
- **Non-image media**: `field_media` also accepts video and
  remote_video; the current helper deliberately skips non-image
  items. Decide whether the gallery does the same (recommended:
  images only, videos out of scope) and record it.
- **Helper placement**: an all-images variant belongs in
  `shh_common` next to `shh_common_image_media_props()` (the 0035
  rule: extend shared helpers, don't re-duplicate per module).

## Acceptance criteria
- [ ] Staff can upload multiple images on a horse, a straw and a
      wrap product through the admin form (media widget verified on
      all three bundles)
- [ ] List views (`/horses` cards and 0038's listing) show exactly
      one featured image per item; the "featured = ?" decision is
      recorded
- [ ] The individual item page shows all uploaded images for all
      three item kinds; gallery-rendering decision recorded; the
      SDC-composition approach stays custom code
- [ ] The all-images helper lives in `shh_common`, reused by every
      consumer — no per-module duplication
- [ ] The product-page additions survive alongside what's already
      injected there: add-to-cart form, 0024's sale-state
      enforcement, the 0001 deposit CTA and 0036/0037 staff buttons
      (0031's view-alter mechanism)
- [ ] Degrades gracefully: a single-image item shows no broken
      gallery furniture, a zero-image item renders cleanly
- [ ] Verified over real HTTP as anonymous (catalog + item pages)
      and as staff (upload path), with responsive image widths
      still served (`src_with_alternate_widths`)
- [ ] Config exported (`make shh-export`) in the same change if
      field/form/view display config changed (decision
      [[0020-shh-config-export-strategy]])

## Related
- [[shh-stables-platform]]
- [[0038-straw-and-wrap-sale-items]]
- [[0031-sdc-based-commerce-product-display]]
- [[0019-horse-catalog-page]]
- [[0035-shh-install-hook-cleanup]]
- [[0020-shh-config-export-strategy]]
