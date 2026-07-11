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
# Task: Facility images — featured on cards, full gallery on the page

## Description
Client request (2026-07-11, follow-on to
[[0039-product-images-featured-and-gallery]]): the riding facilities
(Oval Track, Manège, Lunge Ring — `bookable_facility` nodes) should
have images "in the same manner as horses": staff upload; one
featured image on the `/facilities` overview cards
([[0020-facilities-overview-page]]); all images on the individual
facility page.

Current state:
- Facility nodes have **no media field at all**. Drupal CMS ships a
  `field_featured_image` node storage, but it's cardinality **1**
  and only used by the stock `page` type — it can't model 0039's
  "featured = first of many" pattern, so it's not the vehicle.
- The overview cards (`FacilitiesOverviewController::buildCard()`)
  carry no image; the facility node page renders classic field
  formatters (kept deliberately — [[0030-canvas-content-template-bookable-facility]])
  plus sibling-hook additions (booking CTA, availability calendar
  via the `entity_reference_entity_view` formatter at weight 103).

Approach — reuse 0039's decisions and helpers verbatim:
- New multi-value `field_media` storage on **node**, mirroring the
  variation one (media reference; image/video/remote_video), field
  on `bookable_facility`, `media_library` widget.
- Featured = first image item (delta 0), via
  `shh_common_image_media_props()` on the overview card.
- All images on the node page: a "Photos" section from
  `shh_common_image_gallery()` via `hook_node_view()` in
  `shh_facilities_overview` (the sibling-hook mechanism — the
  booking CTA and calendar must survive untouched). Weight 50:
  after the facts fields (10–25), before links (100) and the
  calendar (103).

## Acceptance criteria
- [x] Staff can upload multiple images on a facility through the
      node form (media_library widget)
- [x] `/facilities` cards show exactly one featured image (delta 0)
- [x] The facility page shows all uploaded images in the shared
      gallery presentation; zero- and single-image facilities
      degrade cleanly (no gallery furniture when empty) — Lunge
      Ring is the deliberate live zero-image case
- [x] The booking CTA and the availability calendar still render on
      facility pages with the gallery present
- [x] 0039's helpers are reused — no new image plumbing
- [x] Verified over real HTTP as anonymous (overview + facility
      pages) and as staff (widget on the node form)
- [x] Config exported (`make shh-export`) in the same change
      (decision [[0020-shh-config-export-strategy]])

## Resolution (2026-07-11)

Implemented exactly per the approach above — no surprises, because
everything hard was already decided and built in 0039; this task is
that model applied to a node bundle (the helpers take any
`FieldableEntityInterface`, so nodes needed zero new plumbing).

**Config** (exported; `config:status` clean, contents grep-verified):
new `field.storage.node.field_media` (multi-value media reference —
deliberately not Drupal CMS's cardinality-1 `field_featured_image`,
which can't model "featured = first of many"),
`field.field.node.bookable_facility.field_media` (same target
bundles as the horse's), `media_library` widget on the node form
display. The node *view* display was deliberately left alone — the
new field lands in its `hidden:` list, and the gallery renders via
the hook instead.

**Code**: `FacilitiesOverviewController::buildCard()` adds the
featured image via `shh_common_image_media_props()`; new
`shh_facilities_overview.module` with `hook_node_view()` appending
the shared "Photos" gallery
(`shh_common_image_gallery(shh_common_image_media_props_all(...))`)
at weight 50 — between the facts fields (10–25) and links/open
hours/calendar (100+). Sibling hook, not a display takeover, per
decision 0030's classic-formatter status quo; the node itself
carries the cacheability, so no extra metadata was needed (unlike
0039's cross-variation feed galleries).

**Sample content**: three GD placeholder images — Oval Track ×2
(featured + gallery), Manège ×1, Lunge Ring deliberately **zero**
so the degrade case stays live on the site, not just verified once.

**Verified over real HTTP** (same bar as 0039):
- Anonymous `/facilities`: Oval Track's card shows only the aerial
  (delta 0 — the surface shot correctly stays off the card), Manège
  shows its interior, Lunge Ring renders cleanly with no image.
- Anonymous `/oval-track`: "Photos" with both images, **plus** the
  "Book now" CTA and the FullCalendar availability embed intact;
  `/manege`: single-image gallery, CTA intact; `/lunge-ring`: no
  Photos furniture at all, CTA and calendar intact.
- Staff (admin over HTTP): the media-library widget renders on
  `/node/3/edit` with both existing media referenced. The real
  multipart-upload path through `/media/add/image` was proven in
  0039 this same day and is unchanged.

Real facility photos from the client are outstanding, same as
0039's product photos.

## Post-delivery fix (2026-07-11): image overflow — `cq-full`

Client review the same day: images "default to too big — a large
picture displays as size 100% and overflows the space available,
particularly the 1st image". Root cause found in the theme, not the
modules: **`hestehoj:image` is full-bleed by design** — its
container always carried the `cq-full` utility
(`width: 100cqw; margin-inline: calc(-50cqw + 50%)` against the
`.layout-content` container-query context), i.e. it deliberately
breaks out of any intermediate wrapper to span the full content
width. Correct for Canvas-style content flows; wrong inside a
composed layout — the horse hero (a `md:grid-cols-2` column, the
"1st image") and every gallery tile escaped their grid cells.
**Latent since 0031**: this render path had never displayed a real
image until 0039/0040 created the site's first media, so it could
never have been seen before.

Fix, per the theme's own CVA conventions: new **`bleed` prop** on
`hestehoj:image` (`full` — the default, so every existing usage is
byte-identical — keeps `cq-full`; `contained` adds no breakout
class, leaving `w-full` inside the parent). The hero and
`shh_common_image_gallery()` tiles pass `bleed: contained`. The
`contained` variant deliberately maps to **no class at all**, which
also means `build/main.min.css` needed no rebuild — important,
because this machine's stale theme toolchain (tailwind 4.1.18 vs
the 4.2.1 the committed build was made with) would have churned the
entire compiled file; a full rebuild here should only happen after
a proper dependency install. (An earlier `npm run format` pass also
reformatted a swath of never-prettier-formatted theme files —
info.yml quote style, DESIGN-SPEC.md, lockfiles/PnP state — all
reverted as unrelated churn.)

Verified over real HTTP after the fix: `/product/1`, `/product/7`,
`/oval-track`, `/manege` all render every image container as
`w-full aspect-…` with **zero** `cq-full` occurrences; catalog
cards (which render `canvas:image` directly, never `hestehoj:image`)
were unaffected throughout, which is why only the product/facility
pages showed the bug.

The related client question — a lightbox for the galleries — is
recorded as [[0041-gallery-lightbox]] (0039's "no lightbox until
the client asks" trigger has now fired).

## Related
- [[shh-stables-platform]]
- [[0039-product-images-featured-and-gallery]]
- [[0020-facilities-overview-page]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0020-shh-config-export-strategy]]
