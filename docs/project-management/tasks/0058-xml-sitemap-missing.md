---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: No XML sitemap

## Description
`/sitemap.xml` returns a real `404` (confirmed 2026-08-22) — no
`simple_sitemap` or equivalent module installed. Search engines have to
discover every page (horse listings, facility pages, feed products,
the pricing/comparison pages) purely by crawling the site's own
internal links, with no authoritative list of URLs or freshness
signal.

## Resolution (2026-08-22)
`drupal/simple_sitemap` was already a Composer dependency (unused,
same situation as Metatag in task 0054) — installed rather than
hand-rolled. `enabled_entity_types` set to `node` + `commerce_product`
(dropped the module's own defaults of `taxonomy_term` and
`menu_link_content` — shh has no public taxonomy browsing). Bundle
indexing enabled for `node.bookable_facility`, `node.page`,
`commerce_product.horse`, `commerce_product.feed` — deliberately
*not* `commerce_product.bee_bookable_facility` (the facility booking
flow's internal backing product, not a real browsable page) or the
unused `default` product type. Six custom links added for the pages
`simple_sitemap` can't discover on its own (not entities): `/`,
`/horses`, `/our-horses` (task 0057), `/facilities`, `/pricing`,
`/feed`.

Verified over real HTTP: `/sitemap.xml` returns exactly 14 URLs — the
homepage, all six discovery/listing pages, all four real products, all
three facility pages, and the published privacy policy node. No
admin/cart/checkout leakage, and node 2's corrupted revision
([[0059-node-2-test-page-corrupted-revision]]) is naturally excluded
without any special-casing, since simple_sitemap simply can't load it
either.

## Acceptance criteria
- [x] `simple_sitemap` installed and configured for `node`,
      `commerce_product`, and the custom-controller discovery pages
      (`/horses`, `/our-horses`, `/facilities`, `/pricing`, `/feed`)
- [x] `/sitemap.xml` returns a valid sitemap over real HTTP
- [x] Config exported per [[0020-shh-config-export-strategy]]

## Related
- [[shh-stables-platform]]
- [[0054-meta-description-and-og-tags]] — companion SEO gap
