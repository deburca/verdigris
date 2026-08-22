---
type: task
tags: [cms2/task]
status: in-progress
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: Horse catalog has one for-sale listing, and no way to show the herd

## Description
Confirmed via direct DB query (2026-08-22): only 2 horse variations
exist on this site at all (`Freja`, `for_sale`; a second, `sold`) — an
earlier read of an aggregate COUNT query misread as "1 available, 3
sold" was wrong, corrected here. `/horses` therefore shows a single
listing.

This isn't a code bug — the sample catalog is genuinely this small —
but the client then raised the real underlying issue: **the stud has
around thirty horses, of which only a few are old and trained enough
to be sold.** There was no way to show the whole herd informationally
(the story the homepage's trust content, task 0055, needs to tell)
separately from the small for-sale subset, and no clean way to
"promote" a horse into the sale catalogue when the time comes.

## Resolution (2026-08-22)
**Data model**: `field_sale_state` renamed `available` → `for_sale`
(deliberately positive framing per the client — placing a horse for
sale is an explicit, intentional flip, not the absence of a "not for
sale" tag). The field's default is NULL/unset, used for the herd's
majority: a horse never promoted into the sale pipeline. Every
`'available'` string reference across `shh_horse_sale_state`,
`shh_horse_deposit`, and `shh_horse_catalog` renamed to `'for_sale'` —
including `HorseAvailabilityChecker`, which now treats NULL as
**blocked** (not neutral, the opposite of its old NULL handling) since
an un-promoted herd horse must never be purchasable. Verified directly
against the checker's `check()` method (not just the page): `for_sale`
→ neutral/allowed, `sold` → blocked with its existing reason, NULL →
blocked with a new "not for sale" reason. Data migrated on dev (the
one `available` variation → `for_sale`); no other environment has real
horse data yet.

**New page**: `/our-horses` (`HorseCatalogController::ourHorses()`,
`HorseCardBuilder::allHorses()`) — the whole herd, any sale_state
(including sold horses, which still demonstrate the bloodlines), no
price and no add-to-cart (`buildCard(..., include_price: FALSE)`
suppresses it — a variation still holds *some* price value since
Commerce requires one, but showing it for a horse never priced for a
buyer would misrepresent it). The horse product detail page itself
also hides price/state-badge for a NULL-state horse, for the same
reason. Cross-linked both ways: `/horses` links to "Meet the whole
herd" (including its empty state, when nothing's currently for sale),
`/our-horses` links to "See horses for sale". Meta description added
to both, following task 0054's pattern.

Config exported (`field.storage.commerce_product_variation.field_sale_state`).

**Still open**: the client needs to actually enter the herd — roughly
thirty real horses, most with no sale_state (informational only), a
handful `for_sale`. That's real content work, not a code task.

## Acceptance criteria
- [x] Data model distinguishes "part of the herd, informational only"
      from "for sale" without duplicate records (`for_sale` state,
      renamed from `available`; NULL = herd default)
- [x] An informational "Our horses" catalog exists, separate from the
      for-sale catalog, reusing the same entity/fields
- [x] Promoting a horse to sale is a single field flip, no re-entry
- [ ] Confirm with the client which of the current `sold` horses were
      genuine test transactions vs. real historical sales
- [ ] The real ~30-horse herd entered as actual content (most `NULL`/
      informational, a few `for_sale`) before any public launch or demo
- [ ] `/horses`, `/our-horses`, and the homepage teaser checked again
      once real inventory exists

## Related
- [[shh-stables-platform]]
- [[0019-horse-catalog-page]]
- [[0051-homepage-content-plan]] — the featured-horses teaser this feeds
