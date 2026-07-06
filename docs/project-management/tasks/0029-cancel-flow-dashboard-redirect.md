---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
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
- `CancelBookingForm::submitForm()` and `CancelDepositForm::submitForm()`
  redirect to `shh_rider_dashboard.dashboard` (for the current user)
  instead of `<front>` on success
- Confirm this doesn't break the case where staff (not the rider
  themselves) perform the cancellation — redirecting to *the rider's*
  dashboard is correct in that case too (it's their bookings), but
  verify the access check on that route still permits it (should,
  since `RiderDashboardAccessCheck` already allows `administer
  commerce_order`)
- Verified over real HTTP: cancel a booking and a deposit as the owning
  rider, confirm the redirect lands on their own dashboard

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0001-horse-deposit-reservation-flow]]
- [[0015-implement-cancellation-refund-policy]]
- [[0022-rider-dashboard]]
</content>
