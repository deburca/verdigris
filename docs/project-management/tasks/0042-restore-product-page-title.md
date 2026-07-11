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
# Task: Restore the product page title (missing since 0031)

## Description
Client review (2026-07-11, on `/product/1` after the 0039/0040
image work): the page has no title at all — "What I am missing is a
title of the page above the price along the lines of: Freja, or of:
Freja — Icelandic Horse mare (five-gaited)".

Root cause — a genuine
[[0031-sdc-based-commerce-product-display]] regression, live since
2026-07-08: commerce products have **no page-title source of their
own**. Core's `EntityViewController::buildTitle()` builds the page
`#title` by rendering the *entity view display's own `title`
element* (the node-style route-title mechanism doesn't apply;
`enable_page_title_template` is unset for commerce_product, so the
fallback at EntityViewController.php:105 is the only path). 0031's
view-alter set `$build['title']['#access'] = FALSE`, believing the
element was a duplicate of a page-title H1 — it was actually the
*source* of it, so the H1 **and the head `<title>`** (which showed
only the site name) both went blank. Feed product pages (0038) were
never affected — their display module doesn't touch the title.

## Acceptance criteria
- [x] `/product/{id}` shows the product label as the page H1 above
      the price hero, for horse AND feed products
- [x] The head `<title>` carries the product label again
- [x] No duplicate title in the page body (core marks the rendered
      element printed — verified, zero styled-div copies)
- [x] The H1 renders at heading size, not body-text size (see the
      template fix below)
- [x] Everything else on the page survives: hero, gallery,
      add-to-cart, deposit CTA
- [x] Verified over real HTTP on horse and feed product pages

## Resolution (2026-07-11)

Two-part fix:

1. **`shh_horse_product_display`**: removed the
   `$build['title']['#access'] = FALSE` block (and corrected the
   docblock that asserted the wrong rationale). Core then renders
   the title element as the page `#title` and marks it printed, so
   it does not double-render in the content.
2. **`hestehoj` theme**: new
   `templates/content/field--commerce-product--title.html.twig` —
   the commerce_product equivalent of core's
   `field--node--title.html.twig`, keyed on the same
   `#is_page_title` flag. Without it, the theme's generic
   `field.html.twig` override wraps the value in a
   `text-base`-styled block div *inside* the `<h1>`, which both
   nests block markup in the heading and visually shrinks the title
   back to paragraph size. The override renders a bare inline
   `<span>` when serving as the page title and falls through to the
   generic template otherwise.

Chose the full product label (option 2 of the client's two
suggestions — "Freja — Icelandic Horse mare (five-gaited)"): it's
the entity label, it matches the catalog cards and the head
`<title>`, and needs no new "short name" content field. Rename the
product if a shorter title is ever wanted.

**Verified over real HTTP**: `/product/1` and `/product/3` (horse)
plus `/product/6` and `/product/7` (feed) all render
`<h1 …><span>{label}</span></h1>` at full heading size above the
content, head `<title>` is "{label} | Stutteri Hestehøj", zero
in-body duplicates, and Freja's page still carries the hero,
"More photos" gallery, add-to-cart form and deposit CTA. Facility
node pages (route-title mechanism) were never affected. phpcs
clean. No config changed — both fixes are code/template only.

## Related
- [[shh-stables-platform]]
- [[0031-sdc-based-commerce-product-display]]
- [[0040-facility-images-featured-and-gallery]]
