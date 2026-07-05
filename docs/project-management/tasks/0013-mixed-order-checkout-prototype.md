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
# Task: Prototype the mixed Horse + Booking order checkout

## Description
Second of the two prototype-first items from [[shh-stables-platform-model]]. A
single Commerce order must carry both Horse order items and Booking order items
simultaneously, with fulfillment/refund/tax logic branching on item type rather
than inspecting product fields.

## Acceptance criteria
- [ ] Cart containing one Horse product and one Booking (facility hour) checks
      out as a single order
- [ ] Order confirmation/receipt correctly displays both line item types
- [ ] Tax logic branches correctly per item type (coordinate with
      [[0005-tax-classification-horses-vs-bookings]])
- [ ] Refund/cancellation logic branches correctly per item type (coordinate with
      [[0015-cancellation-refund-policy]])
- [ ] Rider eligibility gate (see
      [[0003-rider-membership-eligibility-workflow]] and
      [[0017-anonymous-vs-authenticated-booking-access]]) verified to only block
      the Booking item, not the Horse item, when a cart contains both

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0011-shh-entity-content-type-modeling]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0005-tax-classification-horses-vs-bookings]]
- [[0015-cancellation-refund-policy]]
</content>
