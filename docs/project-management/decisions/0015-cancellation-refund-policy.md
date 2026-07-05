---
tags:
  - cms2/decision
status: accepted
created: 2026-07-05
updated:
decided:
site: shh
deciders:
  - Architecture Team
---

# 0015: Cancellation and refund policy enforcement

## Status

accepted

## Context

No cancellation window or refund rule is modeled. Facility bookings need a
time-based policy (e.g. full refund >24h out, none inside); horse sales need a
separate deposit/refund path. Neither exists in the current schema.

## Decision

Add a `cancellation_policy` config entity referenced by Bookable Facility (and
separately by Horse deposit flow, see task 0001). Enforce via a Commerce
order-cancel workflow that checks policy + time-to-slot before authorizing refund.

## Consequences

### Positive
- Policy is data, not hardcoded logic — editable per facility if needed
- Consistent enforcement point for both self-service and staff-initiated cancellations

### Negative
- Requires a cancel/refund UI for riders, not just admin-side

### Neutral
- Horse deposit refunds likely need different rules than hourly bookings — kept as
  a separate policy reference rather than one shared entity

## Alternatives Considered

### Alternative 1: No self-service cancellation, staff-only
Rejected — creates support overhead disproportionate to a small stables operation.

## Implementation Notes

Ties into BAT event state transitions: cancellation reverts `booked` → `available`
only if policy check passes.

## References

- Related decisions: [[0013-bat-bee-booking-framework]]
- Project: [[shh-stables-platform]]
