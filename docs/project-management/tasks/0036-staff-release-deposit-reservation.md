---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-08
updated: 2026-07-08
---
# Task: Staff action — release a horse from its deposit reservation

## Description
Surfaced while testing (2026-07-08): Paddy needed to cancel a
transaction — specifically an **unpaid** deposit — and free the horse
for resale, and hit a wall. The rider-facing self-service cancel
([[0001-horse-deposit-reservation-flow]]'s `CancelDepositForm`) is
deliberately gated on a *placed* order the current user owns
(`CancelDepositAccessCheck`), so it cannot clear:

- a deposit that only ever reached a **draft cart** (order never
  placed — though in that case the horse was never actually reserved,
  since `markReservedDeposit()` only runs on checkout completion);
- an **orphaned** `reserved-deposit` state with no valid backing order
  (exactly the state sample horse 1 was in, an artifact of 0031's own
  testing: the state field had been flipped out-of-band, and the only
  `horse_deposit` order items pointing at it were drafts, one with no
  order at all);
- any reservation **staff** need to clear on a customer's behalf
  without using that customer's account (admins passed the access
  check via `administer commerce_order`, but only by navigating to the
  rider's own order item — no staff-facing entry point existed).

Deposit-specific by design: un-selling a **sold** horse is a separate
concern with different bookkeeping questions — split out as
[[0037-unsell-sold-horse]].

## Acceptance criteria
- [x] A staff-gated action that returns a `reserved-deposit` horse to
      `available` regardless of how it got reserved
- [x] When a placed deposit order backs the reservation, the normal
      0001 rules run through it (release always; refund only within
      the policy window and only against a captured payment — an
      unpaid deposit refunds nothing)
- [x] When nothing valid backs it, the horse is still released
      (logged as a warning with the variation id, so out-of-band
      states leave a trace)
- [x] Reachable from the horse's own product page for staff; invisible
      to (and 403 for) everyone else
- [x] Verified over real HTTP through all three cases

## Resolution (2026-07-08)

Implemented inside `shh_horse_deposit` (it *is* the deposit lifecycle
module — no new module warranted):

- **`DepositManager::releaseReservation($variation)`**: guards on
  `field_sale_state === 'reserved-deposit'`; queries `horse_deposit`
  order items for the variation (newest first, so a re-deposited horse
  cancels its *current* reservation); delegates to the existing
  `cancelDeposit()` for the first item with a **placed** order (normal
  refund-window/captured-payment rules apply); if no placed order
  releases it, force-releases directly to `available` with a logged
  warning. Return shape: `{released, via: deposit_order|direct,
  refunded, reason}`.
- **`ReleaseReservationForm`** (confirm form) at
  `/product/{commerce_product}/release-reservation`, gated by a new
  restricted permission **`release horse deposit reservations`**
  (granted to no role — admins only via bypass, assign to a staff role
  when one exists). Explains before confirming that release is
  unconditional and refund is policy/payment-dependent.
- **Product-page entry point**: the existing
  `shh_horse_deposit_commerce_product_view()` hook now shows a
  staff-only "Release deposit reservation (staff)" button when the
  horse is `reserved-deposit` and the user has the permission —
  the exact page a staff member is looking at when they want the horse
  back on the market (mirrors the visitor-facing "Pay a deposit"
  button, which shows in the `available` state instead;
  `user.permissions` cache context added). Survives 0031's view-alter
  by construction (same sibling-hook mechanism as the deposit CTA).
- Drive-by while editing `DepositManager`: `@datetime.time` injected
  (replacing a `\Drupal::time()` call phpcs flagged),
  `SupportsRefundsInterface` imported properly, `@return` docs
  completed. Pre-existing phpcs debt in the module's older forms left
  untouched.

Verified over real HTTP / real service calls:
1. **Orphaned state** (sample horse 1 as found): `releaseReservation()`
   → `{released: true, via: direct}`, horse `available` — this also
   cleaned up the leftover test artifact that prompted the question.
2. **Placed + paid, inside 7-day window**: fabricated a placed
   `horse_sale` order with a completed 9.000 DKK manual payment, horse
   re-reserved; staff link rendered on `/product/1` for admin (absent
   for anonymous; route 403 anonymous / 200 admin); submitted the real
   confirm form → "released … the deposit was refunded"; payment state
   `refunded`, balance 0 DKK, horse `available`.
3. **Placed + unpaid** (the scenario that started this): placed order
   with **no** payment → `{released: true, via: deposit_order,
   refunded: false, reason: released_unrefunded}`, horse `available`.
   (An in-eval stale re-read initially showed the old state — fresh
   bootstrap confirmed `available`; the real-HTTP case 2 covers the
   live-request behavior.)

Both fabricated test orders (41, 42) and their payment were deleted
afterwards; horse 1 ends `available` — its earlier `reserved-deposit`
was itself an artifact, so this is the correct final state (and the
sample catalog shows a horse again).

No config changed (route/permission/form are code; the permission is
deliberately assigned to no role yet) — `drush config:status` clean,
nothing to export.

## Related
- [[shh-stables-platform]]
- [[0001-horse-deposit-reservation-flow]]
- [[0037-unsell-sold-horse]]
- [[0024-horse-sale-state-enforcement]]
