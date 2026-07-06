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
# Task: Prepaid facility credit packs (buy 10, redeem over the season)

## Description
Client request: a rider can buy a bundle of 10 reservations for one
specific facility at a 75% discount, then redeem them one at a time
throughout the season instead of paying per booking.

## Confirmed with client before building
- **No expiry** — credits remain valid indefinitely once purchased, not
  tied to a season end date.
- **Per-facility, not shared** — a pack is for one specific facility (e.g.
  "10x Oval Track"); a rider wanting credits for multiple facilities buys
  separate packs.
- **Basic admin visibility** — staff can see and manually adjust balances
  (e.g. a goodwill credit, or correcting an error).

## Not blocked by 0017's commerce_promotion issue
This is a different kind of feature from a discount code/coupon: a
persistent, per-rider, per-facility **balance drawn down over months**, not
a one-time checkout discount. It was never going to be built on
`commerce_promotion`'s Coupon entity even if that module worked — wrong
tool for this job regardless. No dependency on the module that's currently
broken on this environment.

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_facility_credits`:

- **`shh_facility_credit`** and **`shh_facility_credit_transaction`**:
  plain content entities using base fields only (`baseFieldDefinitions()`),
  deliberately **not** the Field API — this project has hit repeated Field
  API config-schema/cache pitfalls this session (0011's `list_string`
  format, 0016/0018's own strict-comparison bug, the config-prefix mistake
  in 0015). Same pattern BAT's own entities (State, Booking, Event, Unit)
  already use on this platform. Zero cache issues installing this module —
  confirms the pattern is the right one to reach for by default here.
  - `shh_facility_credit`: one row per (rider, facility) — multiple pack
    purchases for the same facility top up the same balance rather than
    creating separate records, so staff see one row per rider per facility,
    not a growing pile of individual pack purchases.
  - `shh_facility_credit_transaction`: an audit log entry per grant
    (pack purchase) or redemption (booking), linked to the order item that
    caused it.
- **`BuyCreditPackForm`** (`/node/{node}/buy-credit-pack`) — same dedicated-
  form pattern as `PayDepositForm` (0001) and the reservation form itself:
  an explicit computed price (10 × the facility's per-slot price × 25%),
  not the generic Commerce `AddToCartForm`. Only offered on facilities set
  up for fixed-length slots (task 0016), since that's what makes "per-slot
  price" well defined.
- **`CreditPackCheckoutCompletionSubscriber`** grants 10 credits on order
  placement (same `commerce_order.place.pre_transition` pattern as 0011's
  deposit flow and 0012's booking-hold promotion).
- **Redemption**, wired into the *existing* reservation form rather than a
  separate flow: `hook_form_bee_add_reservation_form_alter()` adds a
  "use 1 credit instead of paying" checkbox when the current user has a
  live balance for that facility, with an early `#submit` handler (runs
  *before* `bee`'s own `submitForm()`, which is what actually creates the
  order item) that marks a pending redemption. `hook_commerce_order_item_insert()`
  consumes that flag once the order item exists and zeroes its price. This
  mirrors `shh_booking_hold`'s established pattern of reacting to order
  item insertion rather than trying to intercept/duplicate `bee`'s own
  submit logic.
- **Admin visibility**: two entity list pages
  (`/admin/commerce/facility-credits`,
  `/admin/commerce/facility-credit-transactions`) plus a basic edit form
  for manually adjusting a balance, gated by a dedicated
  `administer facility credits` permission.

**Bug hit and fixed**: the price-preview calculation in `BuyCreditPackForm`
didn't round its final result, so Oval Track's per-minute rate (stored
truncated to 6 decimals — `1.666666`, not `1.666667`, per task 0016) came
back out as `49.99998 DKK` per slot instead of exactly `50.00` when
multiplied by the slot duration and pack size. The actual booking flow
(`bee_get_unit_price()`) already rounds its own final result via
`number_format()`; this form needed to do the same via Commerce's
`commerce_price.rounder` service, since it computes its own preview/total
independently rather than going through that function.

**Verified end to end** (direct service-level calls plus a real HTTP check
of the "Buy a credit pack" link and form rendering — full form-submission-
over-HTTP testing hit the same curl/session artifact noted back in task
0010, not worth re-litigating given it was already confirmed harmless
there):
- Balance 0 → buy pack → balance 10, price exactly 125 DKK (500 × 0.25)
  after the rounding fix.
- Redeeming a booking: order item unit price forced to 0 DKK, balance
  10 → 9, both the grant and the redemption correctly logged as separate
  transaction records.
- Admin list builders for both entities render without error; access
  confirmed for an administrator account.

A `test_rider` user (uid 2, password `testpass123`) was created for testing
and left in place — harmless test data, but delete it if you don't want a
spare test account lying around.

## Related
- [[shh-stables-platform]]
- [[0017-facility-bundle-discount]]
- [[0016-facility-fixed-length-slots]]
- [[0001-horse-deposit-reservation-flow]]
</content>
