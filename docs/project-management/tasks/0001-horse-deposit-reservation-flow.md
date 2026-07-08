---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-06
---
# Task: Horse deposit/reserve flow and withdrawn state

## Description
Extend `sale_state` workflow: add `withdrawn` (owner pulls horse from sale) and a
`reserved-deposit` sub-state distinct from `reserved` (buyer paid deposit vs full
price pending). Deposit is a separate order item type from outright purchase.

## Design decisions (confirmed with client before implementing)
The original task text named the states and the "own refund path" pointer to
0015, but left real business questions open. Confirmed before building:
- **Balance collection is manual/staff-driven**: deposit paid online; the
  remaining balance is arranged offline (bank transfer, in person); staff
  manually mark the horse `sold` once received. No second online checkout
  to build — matches how this business already operates (same `Manual`
  payment gateway built for 0015).
- **Deposit amount is a percentage of the horse's price** (config, default
  20%), not a flat sum — scales sensibly across a 15,000 DKK pony and a
  60,000 DKK competition horse.
- **Deposit refunds get their own distinct policy entity**
  (`deposit_refund_policy`), not a reuse of 0015's `cancellation_policy` —
  that one is inherently slot-time-based ("hours before a booking starts");
  a deposit policy needs a different shape entirely ("days since the
  deposit was paid").

## Acceptance criteria
- [x] `sale_state` supports: available, reserved-deposit, reserved, sold, withdrawn
- [x] Deposit order item type exists with its own refund path (see 0015)
- [x] Admin can transition to `withdrawn` without a linked order

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_horse_deposit`:

- **`field_sale_state` extended** to all 5 values. `withdrawn` (and any
  other transition) is reachable by simply editing the variation's field —
  no special code needed; the field was already a normal editable
  `options_select` widget from task 0011, so "admin can transition without
  a linked order" was mostly already true, just verified explicitly.
- **`horse_deposit` order item type** (distinct from `horse`, same
  `horse_sale` order type — a deposit is still fundamentally a horse-sale
  transaction, no need for a third Commerce order type). Priced via
  `DepositManager::computeDepositAmount()` — a percentage
  (`shh_horse_deposit.settings:deposit_percentage`, default 20%) of the
  variation's own price, computed at cart-add time, same pattern BEE
  already uses for dynamic booking prices.
- **`PayDepositForm`** (`/product/{commerce_product}/deposit`) — a
  dedicated form alongside the standard `AddToCartForm`, mirroring how
  `bee.module`'s `AddReservationForm` already does this for bookings on
  this platform: its own explicit price, its own order item type, not the
  generic add-to-cart flow. Only depositable when `sale_state = available`.
- **`DepositCheckoutCompletionSubscriber`** — on order placement, marks the
  variation `reserved-deposit` (mirrors 0015's/0012's `commerce_order.place.pre_transition`
  pattern).
- **`deposit_refund_policy` config entity** (`refund_window_days`, default
  7), full admin CRUD, referenced via `field_deposit_policy` on the `horse`
  variation type. Fails closed: no policy assigned = no self-service
  deposit cancellation.
- **`DepositManager::cancelDeposit()`** — the key behavioral difference from
  0015's booking cancellation: the horse is **always** released back to
  `available` on cancellation, regardless of refund eligibility. Only
  whether the deposit itself is refunded depends on the policy window
  (days since the deposit was paid). This is deliberately asymmetric with
  0015's "no refund → no release either" rule — there's no "disincentivize
  late cancellation of scarce inventory" reason to keep a horse off the
  market just because its deposit turned out to be non-refundable; the
  seller wants it re-listed immediately.
- **`CancelDepositForm`** (`/deposit/{commerce_order_item}/cancel`) —
  confirmation form previewing the refund outcome, same UX pattern as
  0015's `CancelBookingForm`.

**Verified end to end** via real HTTP + real captured payments:
- `/product/1` shows a "Pay a deposit to reserve instead" link (only while
  depositable); `/product/1/deposit` renders and correctly computes 9,000
  DKK (20% of 45,000 DKK); submitting via real POST created a
  `horse_deposit` order item on a `horse_sale` cart, redirected to checkout.
- Placing the order + a completed Manual-gateway payment correctly
  transitioned the variation to `reserved-deposit`.
- Cancelling within the 7-day window: released to `available`, 9,000 DKK
  refunded.
- Cancelling a second deposit backdated 10 days (outside the window):
  released to `available` regardless, payment left `completed`, **zero**
  refunded — confirming the release/refund asymmetry works as designed.
- `withdrawn` set directly on the variation with no order involved at all.

**Bug hit and fixed**: the install hook's `field_sale_state` allowed-values
merge initially assumed the wrong runtime PHP structure (a list of
`{value, label}` objects) and crashed with the same schema error task 0011
originally hit creating this exact field. Runtime `getSetting('allowed_values')`
is actually a flat associative array (`value => label`) regardless of the
list-of-objects shape it exports to YAML as — confirmed via `var_export()`
before fixing.

## Workflow note (2026-07-08, from task 0037)

Staff-facing *reverse* transitions now exist for the two states set
automatically by order placement: `reserved-deposit → available`
([[0036-staff-release-deposit-reservation]]) and `sold → available`
([[0037-unsell-sold-horse]]), each with its own restricted permission
and product-page entry point. **Deliberately left out**: `reserved`
and `withdrawn` still have no dedicated transitions or automation in
either direction — they are manual administrative states, set and
cleared by editing `field_sale_state` on the variation form (the
"admin can transition to withdrawn without a linked order" criterion
above). If they ever gain lifecycle semantics (e.g. a non-deposit
"reserved" hold with an expiry), that's a new task; a general staff
sale-state switcher was considered in 0037 and rejected because it
would bypass the per-transition bookkeeping (deposit refund rules,
originating-sale identification).

## Related
- [[shh-stables-platform]]
- [[0015-implement-cancellation-refund-policy]]
- [[0015-cancellation-refund-policy]]
- [[0036-staff-release-deposit-reservation]]
- [[0037-unsell-sold-horse]]
