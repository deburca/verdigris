---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Rider membership/eligibility workflow

## Description
Rider currently has a vague "eligibility" field. Needs a real workflow: waiver
submission → staff approval → active membership → expiry. Booking add-to-cart
must check this state (ties into 0017).

## Acceptance criteria
- Membership content/entity with states: pending, active, expired, revoked
- Waiver capture (Webform, per 0009) linked to membership record
- Add-to-cart access check blocks non-active riders with clear messaging

## Related
- [[shh-stables-platform]]
- [[0017-anonymous-vs-authenticated-booking-access]]
