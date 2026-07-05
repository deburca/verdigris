---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Verify separate Horse and Booking checkout flows

## Superseded scope
Originally "prototype the mixed Horse + Booking order checkout" — a single
Commerce order carrying both item types, with fulfillment/refund/tax logic
branching on item type. **Superseded by
[[0018-separate-order-types-horse-vs-booking]]**: combined single-checkout
purchases were confirmed not to be a required business scenario, so the
platform now uses two separate Commerce order types instead of one shared
order. This removes the entire premise of the original task — there is no
mixed order to prototype tax/refund/eligibility branching within.

## Description
Verify the two independent checkout flows both work correctly post-split:
Horse purchases on the new `horse_sale` order type (via Commerce's standard
`AddToCartForm`), and facility bookings on the `default`/"Facility booking"
order type (via `bee`'s `AddReservationForm`, unaffected by the split since
it already hardcoded that order type). See
[[0011-shh-entity-content-type-modeling]] for the underlying product/content
types and [[0012-cart-hold-concurrency-prototype]] for the booking-side
cart-hold mechanism this must not regress.

## Acceptance criteria
- [x] Horse add-to-cart creates an order of type `horse_sale` (verified: order
      correctly created with that type via the standard product add-to-cart
      form, resolved dynamically via `commerce_order.chain_order_type_resolver`
      — no code change needed on the horse side)
- [x] Facility add-reservation creates an order of type `default` (verified:
      unaffected by the split, since `bee.module`'s `AddReservationForm`
      hardcodes this order type id)
- [x] [[0012-cart-hold-concurrency-prototype]]'s on-hold → booked → available
      mechanism still functions correctly post-split (re-verified: cart-add
      still places an on-hold event correctly on the `default` order type's
      `bee` item)
- [x] Each order type's checkout flow, number pattern, and `cart_expiration`
      confirmed independently configured and functioning (`horse_sale`: own
      checkout flow, `HS-` numbering, 3-day cart expiration; `default`: existing
      flow/numbering, 30-min expiration)

## Not in scope (superseded, tracked only for the record)
- Mixed-order tax/refund/eligibility branching — moot, each order type is now
  homogeneous. [[0005-tax-classification-horses-vs-bookings]] and
  [[0015-cancellation-refund-policy]] can each target their own order type
  directly rather than inspecting order item bundle within a shared order.
- Combined order confirmation/receipt showing both item types — no longer
  applicable, receipts are per order type now.

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0018-separate-order-types-horse-vs-booking]]
- [[0011-shh-entity-content-type-modeling]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0005-tax-classification-horses-vs-bookings]]
- [[0015-cancellation-refund-policy]]
</content>
