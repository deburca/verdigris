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
# Task: Pricing comparison page

## Description
A single page laying out, per facility, the different ways to pay so a
rider can compare them before choosing — currently these three pricing
paths exist but are never shown side by side anywhere:
- **Single slot**: normal per-30-minute price (0016) — Oval Track 50 DKK,
  Manège 30 DKK, Lunge Ring 20 DKK
- **Credit pack**: 10 slots for one facility at 75% off (0018) — 125 DKK
  instead of 500 for Oval Track, etc.
- **Same-timeframe bundle**: all three booked for the same slot for a flat
  20 DKK off the combined total (0017) — 80 DKK instead of 100

## Acceptance criteria
- [x] A page/table showing, per facility: single-slot price, 10-pack
      price + effective per-slot price, and a worked example of the
      three-facility bundle price
- [x] Numbers pulled from live config
      (`field_price`/`field_slot_duration_minutes` per facility,
      `shh_facility_credits`' pack size/discount constants,
      `shh_facility_bundle_discount`'s configured discount amount)
      rather than hardcoded, so it can't silently drift out of sync if
      a price changes
- [x] Linked from the facilities overview page (0020) once that exists

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_pricing_comparison`:

- **`PricingComparisonController`** at `/pricing` — a native Drupal
  `#type: table` render element (facility / single slot / 10-session
  pack / effective per-slot price in a pack), plus a worked
  same-timeframe bundle example below it, built by matching
  `shh_facility_bundle_discount.settings`'s `product_ids` back to their
  facility nodes via `field_product`.
- All numbers come from the `FacilityPricingHelper` service extracted
  during [[0020-facilities-overview-page]] (shared single source of
  truth — never recomputed or hardcoded here) plus
  `shh_facility_bundle_discount.settings`'s live `discount_amount`.
- Linked from the facilities overview page (0020), per this task's own
  acceptance criteria.

**Verified over real HTTP** — every number matches this task's own
documented expected values exactly: Lunge Ring 20 / 50 (75% off) / 5.00
DKK effective, Manège 30 / 75 / 7.50 DKK, Oval Track 50 / 125 / 12.50
DKK, and the bundle example "normally 100.00 DKK, now 80.00 DKK (20.00
DKK off)". This also incidentally re-confirmed the facility pricing data
fix made while building 0020 (before that fix, these same numbers would
have been wrong/zero).

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0016-facility-fixed-length-slots]]
- [[0017-facility-bundle-discount]]
- [[0018-facility-credit-packs]]
- [[0020-facilities-overview-page]]
</content>
