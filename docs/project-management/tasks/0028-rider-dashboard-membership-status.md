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
- A new section on the rider dashboard showing the rider's current
  membership status, reusing
  `MembershipManager::isEligible()`/`getEligibilityMessage()` (0003) —
  not a new eligibility computation
- If no membership record exists yet, or the most recent one is
  `expired`, show the same "submit the waiver" link the booking form
  shows (via `MembershipManager::canSelfServiceResubmit()`); if
  `revoked`, the same "contact us" message with no resubmit link
- Verified over real HTTP for at least three states (none/pending,
  active, expired) using a real test account

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0022-rider-dashboard]]
</content>
