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
# Task: Rider dashboard ("my bookings, deposits, and credits")

## Description
A logged-in rider currently has no single place to see their own upcoming
bookings, horse deposits, or facility credit balances. Cancel links
(0015, 0001) only appear inline on an individual order item's own rendered
view — there's no list to find them from in the first place.

## Acceptance criteria
- [x] A page (e.g. on the user's own account) listing: upcoming facility
      bookings (with cancel links), active horse deposits (with cancel
      links), and facility credit balances per facility (0018) with a
      link to buy more
- [x] Past/completed bookings shown separately or hidden, not mixed with
      upcoming ones
- [x] Respects the same ownership access checks already enforced on the
      individual cancel routes (0015's `CancelBookingAccessCheck`, 0001's
      `CancelDepositAccessCheck`) — this page is a discovery aid, not a
      new access surface

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_rider_dashboard`:

- **`RiderDashboardController`** at `/user/{user}/bookings` — four
  sections: upcoming bookings (facility + date/time, with a "Cancel"
  link), past bookings (same, no cancel link — split by comparing each
  booking's `booking_start_date` against now, sorted
  soonest-first/most-recent-first), horse deposits currently in
  `reserved-deposit` state (with a "Cancel deposit" link), and facility
  credit balances (with a "Buy more" link to that facility's credit-pack
  purchase route).
- **`RiderDashboardAccessCheck`** — deliberately the same ownership shape
  already used by `CancelBookingAccessCheck`/`CancelDepositAccessCheck`
  (account owner, or `administer commerce_order`) rather than a new
  access model, per this task's own acceptance criteria.
- Every cancel/buy-more link is built via `AccessManagerInterface::checkNamedRoute()`
  against the *existing* cancel routes before being shown — this page
  doesn't duplicate or bypass those routes' own access checks, it just
  surfaces them.
- A "My bookings, deposits, and credits" link is added to the rider's
  own rendered account page (`hook_user_view()`, own-account only) —
  the discovery path this task's acceptance criteria itself suggested
  ("e.g. on the user's own account").

**Verified end to end over real HTTP** as the same non-admin test rider
used throughout this session: completed a real horse deposit purchase
and a real facility credit pack purchase (neither existed yet for this
specific account), then confirmed all four sections render correctly
with real data — upcoming bookings with working Cancel links, the new
deposit with a Cancel deposit link, the new credit balance (10
remaining) with a Buy more link, and an empty past-bookings section.
Confirmed the access check: a second account (uid 2) is correctly
denied (`403`) from viewing this rider's dashboard.

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0015-implement-cancellation-refund-policy]]
- [[0001-horse-deposit-reservation-flow]]
- [[0018-facility-credit-packs]]
</content>
