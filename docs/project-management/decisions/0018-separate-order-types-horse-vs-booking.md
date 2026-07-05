---
tags:
  - cms2/decision
status: accepted
created: 2026-07-05
updated:
decided: 2026-07-05
site: shh
deciders:
  - Architecture Team
---

# 0018: Separate Commerce order types for horse sales vs facility bookings

## Status

accepted — amends [[0013-bat-bee-booking-framework]]'s "Single mixed Order"
consequence and [[shh-stables-platform-model]]'s "Shared" Order layer.

## Context

The original design (0013) put both Horse and Booking order items on one
shared order type ("Order — can carry both Horse and Booking order items
simultaneously; branch fulfillment/refund/tax logic on item type"), so a
customer could buy a horse and book the riding hall in a single checkout.

Building [[0012-cart-hold-concurrency-prototype]] surfaced a concrete problem
with this: the cart-hold TTL (`cart_expiration`) is set per order *type*, not
per order *item type*. A single shared order type cannot simultaneously have
a short hold window appropriate for an hourly slot (customers shouldn't block
a slot for days while they dither) and a long window appropriate for a
horse-purchase decision (customers shouldn't lose their cart 30 minutes into
considering a 45,000 DKK purchase). There's also a pre-existing tension with
[[0017-anonymous-vs-authenticated-booking-access]]: horse purchases stay
anonymous-friendly, bookings require login before add-to-cart — mixing both
in one cart means an anonymous cart could suddenly need to acquire an
authenticated identity mid-session when a booking item is added, which 0017
doesn't actually resolve.

On review, combined single-checkout purchases (buy a horse *and* book a
riding slot in the same order) were confirmed **not** to be a required
business scenario — acceptable to drop in favor of two independent,
simpler flows.

## Decision

Split into two Commerce order types instead of one:

- **`default`** (relabeled "Facility booking" — id kept as `default` because
  `bee.module`'s `AddReservationForm::submitForm()` hardcodes
  `cartProvider->getCart('default', $store)`; changing the id would require
  patching bee.module, which this decision deliberately avoids). Carries the
  `bee` order item type (BEE-provisioned bookings). Keeps its existing
  30-minute `cart_expiration`.
- **`horse_sale`** (new). Carries the `horse` order item type. Own checkout
  flow (`horse_sale`, cloned from `default`'s `multistep_default` flow, guest
  checkout still allowed), own number pattern (`HS-[pattern:number]`, distinct
  from booking order numbers for staff clarity), and its own `cart_expiration`
  (3 days — a placeholder pending real business input, not a final figure).

Horse purchases go through Commerce's standard `AddToCartForm`, which already
resolves order type dynamically per order item
(`commerce_order.chain_order_type_resolver`) — no code change needed there,
only reassigning `commerce_order_item_type.horse`'s `orderType` config value.

## Consequences

### Positive
- Resolves the cart-hold-TTL tension from 0012 outright: each purchase kind
  gets its own `cart_expiration`, no compromise value needed
- Resolves the anonymous/authenticated identity tension with 0017: the
  booking flow can assume/require an authenticated rider from the start; the
  horse-sale flow never needs to
- [[0013-mixed-order-checkout-prototype]]'s original scope (prove tax/refund/
  eligibility logic branches correctly *within* one order) is no longer
  needed — each order type is now homogeneous, so 0005 (tax) and 0015
  (refund) never need to inspect order item bundle within an order
- More idiomatic Commerce: splitting order types by fundamentally different
  fulfillment (physical good + deposit workflow vs. time-based service, no
  shipping) is the standard pattern, not a custom one
- Distinct order numbering (`HS-` prefix) gives staff an at-a-glance
  distinction in admin order lists without needing to open each order

### Negative
- No combined checkout: a customer wanting to buy a horse and book a slot in
  the same visit now completes two separate checkouts/payments. Confirmed
  acceptable for this business, but is a real UX difference from the
  originally-stated project goal ("all through a single Commerce-backed
  checkout" — that goal statement is being revised as part of this decision)
- More configuration surface in absolute terms: two checkout flows, two
  cart_expiration values, two number patterns, two order-type admin views —
  each individually simpler, but there are now two of everything
- Customer lifetime spend reporting (horse purchases + booking spend
  combined) requires joining two order histories instead of querying one

### Neutral
- The `default` order type keeping its literal machine name while
  conceptually being "the booking order type" is a naming quirk driven by
  bee.module's hardcoded assumption, not a deliberate choice — documented
  here so it isn't mistaken for an oversight later
- The 3-day horse-sale `cart_expiration` is a placeholder; no real business
  requirement was gathered for this specific number

## Alternatives Considered

### Alternative 1: Keep one shared order type, add per-item-type hold logic
Track hold expiry independently of the order's own `cart_expiration` (e.g. a
custom queue/cron sweep keyed by order item, ignoring order-level
`cart_expiration` entirely). Rejected — reinvents Commerce Cart's own
abandoned-cart cleanup for no benefit once combined checkout isn't required;
strictly more code than the order-type split for a problem the split solves
for free.

### Alternative 2: Rename `default` to `facility_booking` and patch bee.module
Rejected — patching contrib to rename a hardcoded string is unnecessary
churn (and a maintenance burden across BAT/BEE upgrades) when simply leaving
the "horse" side to move to a new order type achieves the same outcome with
zero contrib patches.

## Implementation Notes

- `commerce_order_item_type.bee` stays `orderType: default` (unchanged)
- `commerce_order_item_type.horse` reassigned to `orderType: horse_sale`
- New config: `commerce_order.commerce_order_type.horse_sale`,
  `commerce_checkout.commerce_checkout_flow.horse_sale`,
  `commerce_number_pattern.commerce_number_pattern.horse_sale`
- `commerce_order.commerce_order_type.default` relabeled "Facility booking"
  (id unchanged)
- Verified end to end: horse add-to-cart creates a `horse_sale` order;
  facility add-reservation creates a `default` order; the
  [[0012-cart-hold-concurrency-prototype]] on-hold/promote/release mechanism
  is unaffected by the split (still operates correctly on the `default`
  order type's `bee` items)
- See [[0013-mixed-order-checkout-prototype]] for the corresponding task
  scope change

## References

- Related decisions: [[0013-bat-bee-booking-framework]],
  [[0017-anonymous-vs-authenticated-booking-access]]
- Related task: [[0012-cart-hold-concurrency-prototype]]
- Project: [[shh-stables-platform]]
</content>
