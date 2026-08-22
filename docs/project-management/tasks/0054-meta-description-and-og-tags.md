---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: No meta description or Open Graph tags anywhere on the site

## Description
Confirmed by inspecting the `<head>` of the homepage and several
content pages (2026-08-22): zero `<meta name="description">` tags and
zero `og:*`/`twitter:*` tags anywhere. Only the generic Drupal
`<title>` (e.g. "Home | Stutteri Hestehøj", "Lunge Ring | Stutteri
Hestehøj") is present.

This matters concretely for this site: the footer already links a
Facebook and an Instagram page ([[0032-adopt-footer-navbar-sdc-components]]),
so social sharing is an expected traffic path, and a shared horse
listing or facility page currently renders with no title override, no
description, and no preview image on any platform. Google search
results also fall back to an arbitrary page snippet instead of a
written description.

## Resolution (2026-08-22)
**Hand-rolled, not Metatag.** `drupal/metatag` is already vendored and
enabled on vdg, but only via `field_seo_title`/`field_seo_description`/
`field_seo_image` fields none of shh's bundles have, and its token
resolution across the `commerce_product` → `commerce_product_variation`
boundary (where shh's actual sellable data lives) is known-awkward.
Every page needing a description here already had a small PHP builder
computing the exact summary text (`HorseCardBuilder`,
`FacilityCardBuilder`, `FeedCardBuilder`) — attaching it directly was
simpler and more consistent with this project's "compute from live
data" pattern than fighting a token chain for seven pages.

New shared helpers in `shh_common`: `shh_common_attach_meta_tags()`
(writes `meta description` + `og:title`/`og:description`/`og:type`/
`og:image`/`og:image:alt` into an `#attached`-shaped array) and
`shh_common_absolute_image_url()` (an absolute URL for a media
entity's original file — no cropped social-share derivative, since
that would need the `focal_point` module vdg uses for its own styles,
a dependency shh has no other reason to add).

Wired in:
- **Facility pages** — `shh_facilities_overview_node_view_alter()`,
  composed from the same fields `FacilityCardBuilder`'s card summary
  uses (kind, indoor/outdoor, capacity, per-slot price).
- **Horse product pages** — `shh_horse_product_display`'s existing
  view-alter, reusing the hero's already-computed price/gaits/breed.
- **Feed product pages** — `shh_feed_catalog`'s existing view hook,
  reusing the body teaser + cheapest price (same shape as
  `FeedCardBuilder`).
- **`/horses`, `/facilities`, `/pricing`, `/feed`** — one static,
  hand-written description each (listing pages, not single entities —
  a static line is standard practice here, not copy that can drift).
- **Homepage** — `hook_page_attachments()`, the only hook that reaches
  the front page regardless of it being a Canvas page. Hit a real bug
  wiring this up: `hook_page_attachments()` only permits `#attached`
  and `#cache` directly on `$attachments` — `html_head` must nest under
  `$attachments['#attached']`, not sit at the top level like a normal
  render array's `#attached` key. Caught via `watchdog:show`
  (`LogicException: Only #attached and #cache may be set in
  hook_page_attachments()`), not silently.

**Bonus fix caught along the way**: composing the feed description
surfaced that `FeedCardBuilder::buildCard()`'s existing body teaser
(used on `/feed`'s cards and the homepage's feed teaser already) never
collapsed whitespace after `strip_tags()` — a body field authored with
line breaks rendered them as literal newlines/runs of spaces inside
the card text. Fixed there too (`preg_replace('/\s+/', ' ', ...)`),
live and pre-existing, not something this task introduced.

Verified over real HTTP: a real `<meta name="description">` on all
seven required pages, `og:image`/`og:image:alt` present on Oval Track
and Freja's product page, and correctly *absent* (not broken) on Lunge
Ring, which currently has no photo ([[0053-lunge-ring-wrong-hero-photo]]).
No config change — this was code only, `config:status` clean.

## Acceptance criteria
- [x] A meta-tag solution in place — hand-rolled, not Metatag (see
      Resolution for why)
- [x] Homepage, horse product pages, facility pages, and `/feed`,
      `/horses`, `/facilities`, `/pricing` all get a real
      `<meta name="description">`
- [x] `og:title`, `og:description`, `og:image` on product/facility
      pages (image omitted gracefully where no photo exists yet)
- [x] Verified over real HTTP: view-source on at least one page of each
      content type

## Related
- [[shh-stables-platform]]
- [[0032-adopt-footer-navbar-sdc-components]] — the social links this depends on
- [[0039-product-images-featured-and-gallery]], [[0040-facility-images-featured-and-gallery]] — the featured-image source for `og:image`
