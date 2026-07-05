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
# Task: Booking lifecycle notifications and audit trail

## Description
No record exists of booking state transitions (held, confirmed, cancelled, expired)
or the emails/notifications tied to them. Needed for both rider communication and
support/dispute resolution.

## Acceptance criteria
- Log entity or watchdog channel records BAT event state transitions with actor
  (customer/admin/system-expiry) and timestamp
- Email sent on: booking confirmed, booking cancelled, hold expired
- Admin-created events (0016) logged with same trail

## Related
- [[shh-stables-platform]]
- [[0016-booking-granularity-admin-events]]
