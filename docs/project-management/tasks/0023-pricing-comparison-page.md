---
type: task
tags: [cms2/task]
status: backlog
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
- A page/table showing, per facility: single-slot price, 10-pack price +
  effective per-slot price, and a worked example of the three-facility
  bundle price
- Numbers pulled from live config (`field_price`/`field_slot_duration_minutes`
  per facility, `shh_facility_credits`' pack size/discount constants,
  `shh_facility_bundle_discount`'s configured discount amount) rather than
  hardcoded, so it can't silently drift out of sync if a price changes
- Linked from the facilities overview page (0020) once that exists

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0016-facility-fixed-length-slots]]
- [[0017-facility-bundle-discount]]
- [[0018-facility-credit-packs]]
- [[0020-facilities-overview-page]]
</content>
