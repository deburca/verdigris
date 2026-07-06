---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Facilities overview / "Book a facility" landing page

## Description
No page lists all three bookable facilities together. A rider needs a
direct link to `/oval-track`, `/manege`, or `/lunge-ring` individually —
there's no single entry point to discover and compare them.

## Acceptance criteria
- [x] A public page/view listing all `bookable_facility` nodes: title,
      facility kind, price per slot, indoor/outdoor, capacity, link to
      book
- [x] Should be the natural place to explain the three-facility bundle
      discount (0017) and credit packs (0018) exist at all — currently
      neither is discoverable except by landing on the right individual
      facility page
- [x] Linked from primary navigation

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_facilities_overview`:

- **`FacilitiesOverviewController`** at `/facilities` — one `hestehoj:card`
  per published `bookable_facility` node (facility kind label,
  indoor/outdoor, capacity, and per-slot price computed live — see
  below), plus an explainer section for the bundle discount and credit
  packs, and (once task 0023 landed) a link to the pricing comparison
  page.
- Extracted the per-slot/pack price computation out of
  `shh_facility_credits`' `BuyCreditPackForm` into a new shared
  `FacilityPricingHelper` service (same module), so this page and
  0023's pricing comparison page share a single source of truth instead
  of duplicating the calculation — refactored `BuyCreditPackForm` itself
  to use it too, eliminating the duplication entirely rather than just
  avoiding adding more of it.

**A critical, live, currently-active bug was found while building this
page's price display**: `field_price_frequency` had drifted to `hour`
for all three facilities (should be `minute` per task 0016's own
documented resolution — a `hour`-frequency booking on a non-whole-hour
duration is a known `bee.module` bug that silently computes **0.00 DKK**,
flagged in that same task's notes). Confirmed live: real order items
from this session's own earlier testing (0025's booking, and a repeat of
it) had both been charging **0.00 DKK** for a real "Oval track" booking.
Two of the three facilities' `field_price` had also drifted from the
correct per-minute rate to a value ×60 too high (a plausible
hour-rate/minute-rate mix-up). Fixed all three facilities' data back to
the per-minute rates task 0016 originally set (Oval Track 1.666666,
Manège 1.0, Lunge Ring 0.666666) and `field_price_frequency: minute`;
verified with a fresh real booking immediately charging the correct
**50 DKK**, and the credit-pack page correctly showing 500/125 DKK
again.

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0016-facility-fixed-length-slots]]
- [[0017-facility-bundle-discount]]
- [[0018-facility-credit-packs]]
- [[0019-horse-catalog-page]]
- [[0023-pricing-comparison-page]]
</content>
