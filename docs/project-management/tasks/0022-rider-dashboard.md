---
type: task
tags: [cms2/task]
status: backlog
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
- A page (e.g. on the user's own account) listing: upcoming facility
  bookings (with cancel links), active horse deposits (with cancel links),
  and facility credit balances per facility (0018) with a link to buy more
- Past/completed bookings shown separately or hidden, not mixed with
  upcoming ones
- Respects the same ownership access checks already enforced on the
  individual cancel routes (0015's `CancelBookingAccessCheck`, 0001's
  `CancelDepositAccessCheck`) — this page is a discovery aid, not a new
  access surface

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0015-implement-cancellation-refund-policy]]
- [[0001-horse-deposit-reservation-flow]]
- [[0018-facility-credit-packs]]
</content>
