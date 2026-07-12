---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-12
---
# Task: Redirect cancel flows back to the rider dashboard

## Description
Found via [[shh-account-access-gap-analysis]]: `CancelBookingForm`
(0015) and `CancelDepositForm` (0001) both redirect to `<front>` (the
homepage) after a successful cancellation. Both predate the rider
dashboard (0022), which didn't exist yet when they were built. Now
that `/user/{user}/bookings` is the natural "my bookings, deposits,
and credits" hub — and is where a rider would realistically have
clicked "Cancel" from in the first place — returning there instead of
the homepage closes the loop properly.

## Acceptance criteria
- [x] `CancelBookingForm::submitForm()` and
      `CancelDepositForm::submitForm()` redirect to
      `shh_rider_dashboard.dashboard` instead of `<front>` on success
- [x] Confirm this doesn't break the case where staff (not the rider
      themselves) perform the cancellation — redirecting to *the
      rider's* dashboard is correct in that case too (it's their
      bookings), but verify the access check on that route still
      permits it (should, since `RiderDashboardAccessCheck` already
      allows `administer commerce_order`) — **verified with a real
      staff cancellation**
- [x] Verified over real HTTP: cancel a booking and a deposit as the
      owning rider, confirm the redirect lands on their own dashboard

## Resolution (2026-07-12)

New shared helper `shh_common_rider_dashboard_url(int $uid)`: the
dashboard URL for that uid, falling back to the front page when there
is no owning account or the dashboard module isn't installed — so
callers can use it unconditionally. Both cancel forms now resolve it
from **the order's customer**, not the current user:

- `CancelBookingForm` (`shh_cancellation_policy`) and
  `CancelDepositForm` (`shh_horse_deposit`) each gained a
  `dashboardUrl()` method used by **both** `submitForm()`'s redirect
  **and** `getCancelUrl()` — the confirm form's "Cancel" back-out link
  dumped the rider on the homepage too, which is the same wart the
  task describes; fixing only the success path would have left it.
- `shh_common` is now a declared dependency of both modules (it was
  already an undeclared runtime dependency of neither — this is the
  first use).

**Keying on the order's owner rather than the current user is the
whole subtlety**: when staff cancel on a rider's behalf they land on
*that rider's* dashboard (which they may view — 0022's
`RiderDashboardAccessCheck` allows `administer commerce_order`), not
on a `/user/1/bookings` page about themselves. A guest horse-deposit
order (uid 0 — horse checkout allows guests) correctly falls back to
the front page.

**Verified over real HTTP**, driving the genuine flows:
1. **Rider cancels own deposit**: real deposit purchase as `test_rider`
   (order 53, horse 1 → `reserved-deposit`) → the confirm form's
   back-out link already read `/user/2/bookings` → the real confirm
   POST redirected to **`/user/2/bookings`**, rendering the dashboard,
   with the refund message and horse 1 back to `available`.
2. **Staff cancels a rider's booking**: real facility booking as
   `test_rider` (order 54) → cancelled as **admin** → redirected to
   **`/user/2/bookings`** — the *rider's* dashboard, not admin's — and
   it rendered (the access check permits staff), with "cancelled and
   refunded, the slot has been released".
3. Helper fallbacks checked directly: uid 2 → `/user/2/bookings`,
   uid 0 → `/`.
4. Test orders and payments deleted; horse 1 ends `available`; zero
   draft carts; `config:status` clean; phpcs clean on the new code
   (the pre-existing style warnings in these older modules were left
   alone — out of scope).

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0001-horse-deposit-reservation-flow]]
- [[0015-implement-cancellation-refund-policy]]
- [[0022-rider-dashboard]]
