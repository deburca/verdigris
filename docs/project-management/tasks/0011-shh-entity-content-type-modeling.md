---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Build the shh entity/content-type model (Store, Horse, Bookable Facility)

## Description
Once modules are enabled ([[0010-enable-shh-commerce-bat-bee-modules]]), build the
base entity model documented in [[shh-stables-platform-model]]: no Commerce store,
product types, or bookable content type exist yet on shh.

## Acceptance criteria
- [ ] Commerce Store created (1)
- [ ] Product type **Horse** + Variation type **Horse**: SKU = horse ID, price,
      stock = 1; fields for breed, sex, age/DOB, height, discipline, pedigree,
      vetting/health status, media (photos/video); `sale_state` workflow field
      (`available → reserved → sold` baseline — see
      [[0001-horse-deposit-reservation-flow]] for the extended states)
- [ ] Order item type **Horse** (purchase line)
- [ ] Content type **Bookable Facility**: BEE-enabled, hourly granularity,
      Commerce payment on, price-per-hour; fields for `facility_kind`
      (arena/hall), surface/dimensions, indoor flag, capacity, peak-pricing config
- [ ] BEE configured on Bookable Facility so it provisions the BAT Unit/Type and
      the **Booking** order item type automatically (do not hand-wire BAT to
      Commerce)
- [ ] One BAT unit confirmed per facility node (not using BEE's multi-unit feature
      — facilities are specific, not interchangeable inventory)
- [ ] Sample horse product and sample facility node created and verified end to
      end (view page, add to cart)

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0010-enable-shh-commerce-bat-bee-modules]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0013-mixed-order-checkout-prototype]]
</content>
