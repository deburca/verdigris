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

# 0016: Multi-hour booking granularity and admin-created events

## Status

accepted

## Context

Two gaps in the BAT event model: (1) whether a 2+ hour booking is one variable-duration
event or N sequential hourly events, and (2) staff need to block/reserve slots
(maintenance, private lessons) outside the customer checkout flow — nothing
currently distinguishes admin-created events from customer bookings.

## Decision

- Multi-hour bookings create N sequential hourly BAT events sharing one Booking
  order item reference (single order item, multiple event IDs) — keeps pricing and
  per-hour cancellation granularity intact rather than one opaque block event.
- Add an `event_source` field on BAT events: `customer` | `admin`. Admin-created
  events skip Commerce entirely and are created directly via a staff calendar UI
  (see task 0004), setting state straight to `booked`.

## Consequences

### Positive
- Per-hour cancellation/refund math stays simple (0015 applies uniformly)
- Admin blocks don't pollute the order/cart pipeline

### Negative
- Multi-hour booking UI must aggregate N events into one visual block for the rider

### Neutral
- Admin events need their own lightweight audit trail (task 0002)

## Alternatives Considered

### Alternative 1: Single variable-duration BAT event per booking
Rejected — complicates partial-hour cancellation and per-hour pricing lookups.

## Implementation Notes

See [[shh-stables-platform-model]] for BAT event state detail.

## References

- Related decisions: [[0013-bat-bee-booking-framework]], [[0015-cancellation-refund-policy]]
- Project: [[shh-stables-platform]]
