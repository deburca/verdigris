---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Enforce sale_state on horse purchase (stop selling unavailable horses)

## Description
Found via [[shh-rider-journey-gap-analysis]]: `field_sale_state` is only
ever checked by `shh_horse_deposit`'s deposit flow. The standard Commerce
`AddToCartForm` used for a full-price purchase never checks it — a
`reserved`, `reserved-deposit`, `sold`, or `withdrawn` horse can still be
added to cart and bought. Nothing transitions a horse to `sold` when a
full-price order is placed either, so even a legitimately-completed sale
doesn't remove the horse from availability.

## Acceptance criteria
- [ ] Add-to-cart access/validation blocks purchase unless
      `field_sale_state == available` (mirrors
      `DepositManager::isDepositable()`'s check, applied to the standard
      purchase path too)
- [ ] An order processor/event subscriber for the `horse` order item type
      (same `commerce_order.place.pre_transition` pattern as
      `DepositCheckoutCompletionSubscriber`) transitions `sale_state` to
      `sold` on order placement
- [ ] Decide and implement what happens on cart-add before checkout
      completes — does adding to cart put the horse in a transient
      "reserved" hold state (mirroring [[0012-cart-hold-concurrency-prototype]]'s
      booking hold) to close the race between two carts, or is
      `available` → `sold` only checked/enforced at checkout completion
      (narrower race window, simpler, matches this task's minimum bar)?
- [ ] Verified: a second cart cannot complete checkout for an
      already-`sold`/`reserved` horse

## Related
- [[shh-stables-platform]]
- [[shh-rider-journey-gap-analysis]]
- [[0001-horse-deposit-reservation-flow]]
- [[0012-cart-hold-concurrency-prototype]]
</content>
