---
type: task
tags: [cms2/task]
status: done
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
- [x] Add-to-cart access/validation blocks purchase unless
      `field_sale_state == available` (mirrors
      `DepositManager::isDepositable()`'s check, applied to the standard
      purchase path too)
- [x] An order processor/event subscriber for the `horse` order item type
      (same `commerce_order.place.pre_transition` pattern as
      `DepositCheckoutCompletionSubscriber`) transitions `sale_state` to
      `sold` on order placement
- [x] Decide and implement what happens on cart-add before checkout
      completes — does adding to cart put the horse in a transient
      "reserved" hold state (mirroring [[0012-cart-hold-concurrency-prototype]]'s
      booking hold) to close the race between two carts, or is
      `available` → `sold` only checked/enforced at checkout completion
      (narrower race window, simpler, matches this task's minimum bar)?
      **Decided: the narrower window** — see Resolution.
- [x] Verified: a second cart cannot complete checkout for an
      already-`sold`/`reserved` horse

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_horse_sale_state`:

- **`HorseAvailabilityChecker`** (`src/Availability/`) — a service tagged
  `commerce_order.availability_checker`, Commerce's own pluggable
  extension point for `AddToCartForm`'s automatic
  `PurchasedEntityAvailableConstraint` validation (already wired onto
  every order item's `purchased_entity` field by Commerce core). This was
  the key finding: **no form-alter or custom validation wiring was
  needed at all** — registering a checker service is enough, and it is
  enforced by the standard `AddToCartForm` automatically, exactly the
  same way Commerce enforces its own stock-availability checks. Applies
  to *any* order item purchasing a variation with `field_sale_state`
  (not hard-coded to the `horse` bundle), so it also covers any future
  purchase path built as a normal `ContentEntityForm`.
- **`HorseSaleCompletionSubscriber`** (`src/EventSubscriber/`) — same
  `commerce_order.place.pre_transition` pattern as
  `DepositCheckoutCompletionSubscriber` (0001), but for the plain `horse`
  order item type: transitions `field_sale_state` to `sold` when a
  full-price order is placed.
- **`hook_form_BASE_FORM_ID_alter()`** for
  `commerce_order_item_add_to_cart_form` (UX only, not the actual
  security boundary): hides the "Add to cart" button and shows a plain
  notice when the variation isn't available, mirroring
  `PayDepositForm::buildForm()`'s existing "hide the action if not
  currently possible" pattern.

**Decision: accepted the narrower race window** (checked at each
add-to-cart/checkout validation, not a cart-add-time hold like 0012's
`shh_booking_hold`). A horse purchase's checkout is comparatively slow
(arranging payment, billing details) and low-volume compared to hourly
facility slots, so building an equivalent hold mechanism here wasn't
judged worth the added complexity — this task's own acceptance criteria
explicitly allowed this as the minimum bar.

**Verified end to end over real HTTP** (two independent anonymous
sessions/cookie jars):
- Add-to-cart for an `available` horse works unchanged (no regression).
- Setting a horse's `field_sale_state` to `sold` (or `withdrawn`/etc.)
  correctly hides the add-to-cart button and shows a notice; a **forged
  direct POST** replaying the add-to-cart form for that horse is still
  rejected with `"This horse is sold and is no longer available for
  purchase."` and creates no order item — confirmed the block is a real
  server-side validation, not just a hidden button.
- Completed a real full-price purchase end to end (guest checkout,
  Manual/bank-transfer gateway, order placed) — the horse's
  `field_sale_state` automatically flipped to `sold` the moment the
  order was placed, with no manual staff step, closing critical gap 2
  from the gap analysis.
- With the horse now genuinely `sold`, a **second, independent session**
  was correctly blocked from adding it to cart at all (same validation
  error), closing critical gap 1.

**Investigated and ruled out a second suspected gap, found no actual
bug**: initially suspected `shh_horse_deposit`'s `PayDepositForm` had the
same forged-POST exposure, since it builds and saves its
`horse_deposit` order item directly rather than through
`ContentEntityForm`'s validate step, so `HorseAvailabilityChecker` never
runs for it — only `DepositManager::isDepositable()`'s `#access = FALSE`
button-hide in `buildForm()` protects it. Added a defensive re-check in
`submitForm()` as a fix, then verified with a logging probe that
`submitForm()` is **never actually reached** by a forged POST against
the hidden button in the first place — Drupal core's Form API
deliberately refuses to process input for any `#access = FALSE` element
(see the comment above `$process_input` in
`FormBuilder::doBuildForm()`), so the button-hide alone is already a
real server-side block, not just a UI nicety. Reverted the unnecessary
`submitForm()` change; documented the finding in
`HorseAvailabilityChecker`'s class docblock instead so it isn't
re-investigated as a false lead later.

## Related
- [[shh-stables-platform]]
- [[shh-rider-journey-gap-analysis]]
- [[0001-horse-deposit-reservation-flow]]
- [[0012-cart-hold-concurrency-prototype]]
</content>
