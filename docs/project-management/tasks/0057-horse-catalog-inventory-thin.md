---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: Horse catalog has one available listing against three sold

## Description
Confirmed via direct DB query (2026-08-22): of the sample/test horses
in `commerce_product_variation__field_sale_state`, one is `available`
and three are `sold`. `/horses` therefore shows a single listing.

This isn't a code bug — the sold states are genuine, mostly from
earlier tasks' own real end-to-end test purchases (0024, 0037, 0046)
rather than actual sales — but it's a real content/operations gap: the
homepage's "Horses for sale" teaser (0051 section 3) and the whole
"About the stud"/testimonials trust-building work (task 0055) is being
built around a catalog that, as it stands, has almost nothing to show.
A near-empty catalog undercuts the trust content before a visitor even
reaches it.

## Acceptance criteria
- [ ] Confirm with the client which of the current `sold` horses were
      genuine test transactions (safe to reset back to `available` or
      delete) vs. real historical sales (leave as `sold`)
- [ ] Real current inventory entered as actual product content (not
      further test/sample data) before any public launch or demo
- [ ] `/horses` and the homepage teaser checked again once real
      inventory exists

## Related
- [[shh-stables-platform]]
- [[0019-horse-catalog-page]]
- [[0051-homepage-content-plan]] — the featured-horses teaser this feeds
