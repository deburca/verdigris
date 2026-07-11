---
type: task
tags: [cms2/task]
status: done
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
- [x] Staff can upload multiple images on a horse, a straw and a
      wrap product through the admin form (media widget verified on
      all three bundles) — required switching the widget to
      `media_library` first; the horse form's old autocomplete had
      **no upload path at all** (see Resolution)
- [x] List views (`/horses` cards and 0038's listing) show exactly
      one featured image per item; the "featured = ?" decision is
      recorded — **featured = first image item in field delta
      order**, staff reorder in the widget
- [x] The individual item page shows all uploaded images for all
      three item kinds; gallery-rendering decision recorded; the
      SDC-composition approach stays custom code — grid of 1:1
      `hestehoj:image` tiles, no new theme component
- [x] The all-images helper lives in `shh_common`, reused by every
      consumer — no per-module duplication
- [x] The product-page additions survive alongside what's already
      injected there: add-to-cart form, 0024's sale-state
      enforcement, the 0001 deposit CTA and 0036/0037 staff buttons
      (0031's view-alter mechanism)
- [x] Degrades gracefully: a single-image item shows no broken
      gallery furniture, a zero-image item renders cleanly
- [x] Verified over real HTTP as anonymous (catalog + item pages)
      and as staff (upload path), with responsive image widths
      still served (`src_with_alternate_widths`)
- [x] Config exported (`make shh-export`) in the same change
      (decision [[0020-shh-config-export-strategy]])

## Resolution (2026-07-11)

**Decisions:**

- **Featured = the first *image* item in field delta order.** No
  separate flag field: staff pick the featured image by dragging it
  first in the media widget, and it's what the cards already showed.
  One refinement over the old behaviour: a leading *video* no longer
  hides a later image — the helper now finds the first image, not
  the first item.
- **Gallery = a responsive grid of uniform 1:1 `hestehoj:image`
  tiles.** No new theme SDC (the existing `image` component
  composes fine), no lightbox and no full-size links — the tiles
  are the "see all images" surface; revisit only if the client asks.
  Stays entirely inside the site-wide custom-code SDC-composition
  approach (0031/0030).
- **Non-image media are skipped** by the whole helper family
  (videos in `field_media` simply don't appear in cards or
  galleries), unchanged from the 0031 helper's stance.
- **Feed images live on the year variations (per the 0038 model),
  but the product-page gallery is the union across published
  variations**, deduped by media entity, variation-then-delta
  order — photos belong to the product, not the harvest year. The
  feed *card* walks the variations in order and takes the first
  image found (photos usually sit on the current year, not
  necessarily the default variation).

**What was built:**

- **`shh_common`** helper family (the single-image
  `shh_common_image_media_props()` is now the featured accessor,
  delegating to the new pieces): `shh_common_image_media_props_all()`
  (all image props in delta order),
  `shh_common_image_media_props_from_media()` (single media → props,
  public for cross-entity aggregators), and
  `shh_common_image_gallery()` (props list → the shared grid render
  array; returns `[]` for an empty list so callers skip gallery
  furniture entirely).
- **Horse pages** (`shh_horse_product_display`): hero keeps the
  featured image; a "More photos" section (weight −4, between the
  narrative sections and the add-to-cart form) shows every image
  *after* the first — a single-image horse gets no gallery
  furniture at all.
- **Feed pages** (new `shh_feed_catalog.module`):
  `hook_commerce_product_view()` — the CTA modules' sibling-hook
  mechanism, *not* 0031's view_alter takeover, since feed pages
  keep their classic formatter display — appends a "Photos" section
  at weight 7 (between body and add-to-cart) with the
  union-across-variations gallery; per-variation cacheability
  attached.
- **Upload path fixed as part of this task**: the horse variation
  form's `field_media` widget was `entity_reference_autocomplete` —
  staff literally could not upload an image through it, only
  reference media created elsewhere. Both horse and feed variation
  form displays now use the **`media_library` widget** (the
  `media_library` + `media_library_bulk_upload` modules were
  already enabled platform-wide, just never wired to this field).
- Config: `field.field.commerce_product_variation.feed.field_media`
  (same storage/target bundles as the horse's) + both form
  displays; exported, `config:status` clean, export contents
  grep-verified (both widgets `media_library_widget`).

**Sample content** (content, not config; the site had **zero media
entities** before this — every image code path since 0031 had never
run against real content): eight GD-generated labelled placeholder
JPEGs under `public://product-images/`, chosen to exercise every
path — Freja ×3 (featured + 2-tile gallery), Þór ×1 (single-image
degrade; he is `sold` from earlier tasks' testing, which doubles as
the sold-page check), straw ×2 on its one variation, wrap ×1 on
*each* year variation (the union proof). Real photos from the
client replace these whenever they arrive.

**Verified over real HTTP:**
1. Anonymous `/horses`: Freja's card shows exactly her featured
   portrait (Þór is correctly absent — `sold`); `/feed`: straw and
   wrap cards each show one featured image with alt text. All srcs
   carry canvas's `alternateWidths` parametrized-width style URLs
   (this was the computed property's first-ever exercise on this
   site — it works).
2. Anonymous `/product/1` (Freja): hero portrait, "More photos"
   with the other two, add-to-cart form with quantity **and** the
   0001 "Pay a deposit to reserve instead" CTA all present
   together.
3. Anonymous `/product/3` (Þór, sold, one image): image renders,
   **no** gallery furniture, Sold badge and 0024's "no longer
   available" notice intact — no add-to-cart.
4. Anonymous `/product/6` (straw): "Photos" with both images +
   add-to-cart; `/product/7` (wrap): "Photos" with **both years'
   images** (union verified) + the year selector.
5. Staff upload path as admin: the media-library widget ("Add
   media") renders on both the horse and feed variation edit forms
   over HTTP, and a **real multipart upload** went through
   `/media/add/image` end to end via the no-JS two-step (file POST
   → fid 11 returned with the required-alt rebuild → alt + save →
   "has been created"). The media-library *selection* modal is
   AJAX-only and can't be driven scriptlessly — recorded honestly:
   upload and reference were each proven over HTTP, the modal click
   path itself is core UI. Test media deleted afterwards.
6. Zero-image degrade: every page above rendered cleanly *before*
   the sample images existed (this session's 0038 verification runs
   are exactly that state).

## Resolution addendum — what 0039 deliberately did not do
No per-image captions, no ordering UI beyond the widget's own
drag-order, no image styles/cropping decisions beyond what canvas's
parametrized widths already provide, and no video display. All fine
to revisit when real client photos arrive.

## Related
- [[shh-stables-platform]]
- [[0038-straw-and-wrap-sale-items]]
- [[0031-sdc-based-commerce-product-display]]
- [[0019-horse-catalog-page]]
- [[0035-shh-install-hook-cleanup]]
- [[0020-shh-config-export-strategy]]
