---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-07
---
# Task: Show membership status on the rider dashboard

## Description
Found via [[shh-account-access-gap-analysis]]: `/user/{user}/bookings`
(0022) shows upcoming/past bookings, active deposits, and facility
credit balances, but not the rider's own membership state (none /
pending / active / expired / revoked — see 0003). A rider blocked from
booking currently only discovers why by attempting a booking and
reading the message on the reservation form itself
(`MembershipManager::getEligibilityMessage()`) — the dashboard would be
a more natural, proactive place to show this, especially for a
`pending` rider checking whether staff have approved them yet.

## Acceptance criteria
- [x] A new section on the rider dashboard showing the rider's current
  membership status, reusing
  `MembershipManager::isEligible()`/`getEligibilityMessage()` (0003) —
  not a new eligibility computation
- [x] If no membership record exists yet, or the most recent one is
  `expired`, show the same "submit the waiver" link the booking form
  shows (via `MembershipManager::canSelfServiceResubmit()`); if
  `revoked`, the same "contact us" message with no resubmit link
- [x] Verified over real HTTP for at least three states (none/pending,
  active, expired) using a real test account

## Resolution (2026-07-07)

Small addition to `shh_rider_dashboard` (no new module): a "Membership"
section at the **top** of `/user/{user}/bookings` — for an ineligible
rider it's the answer to "why can't I book yet?", so it goes before the
booking lists. `RiderDashboardController::buildMembershipSection()`
injects `shh_rider_membership.manager` (now a declared module
dependency) and mirrors the booking-form alter exactly: the message is
`getEligibilityMessage()` verbatim, and the waiver link is the same
`hestehoj:button` component pointing at the `shh_rider_waiver` webform,
shown only when `!isEligible() && canSelfServiceResubmit()`. No new
eligibility logic anywhere — the dashboard cannot disagree with the
reservation form. The section carries `#cache max-age 0` (same reason
the booking form is uncached): staff approval or a cron expiry must
show immediately, not a stale cached verdict.

Verified over real HTTP with real accounts — **all five** states, not
just the required three:
- **active** (`soren_holm`, uid 5): "Membership active.", no link.
- **none** (`test_rider`, uid 2 — genuinely has no membership record):
  submit-waiver message + button; the button's `/form/shh-rider-waiver`
  target confirmed 200 for that rider.
- **expired** (`shh_test_rider`, uid 3): expiry backdated, then flipped
  by the REAL mechanism (`drush cron` → `autoExpireStale()`), not a
  direct status write — renewal message + button.
- **pending** (uid 3, status set directly): awaiting-staff-approval
  message; the waiver button also shows, exactly as the booking form
  behaves (`canSelfServiceResubmit()` deliberately returns TRUE while
  pending — "harmless either way" per its own docblock; the duplicate
  guard in `createPendingFromWaiver()` makes a resubmission a no-op).
- **revoked** (uid 3, status set directly): contact-us message,
  **no** resubmit link.

uid 3's membership was restored afterwards to its pre-test state
(active, expires 2027-07-06) and the dashboard immediately showed
"Membership active." again — also confirming the no-stale-cache
behavior.

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0022-rider-dashboard]]
</content>
