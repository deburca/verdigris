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

**Implementation outcome (2026-07-11, task
[[0004-staff-admin-booking-calendar]])**: the staff calendar was
built **without the `event_source` field** — by the time 0004 was
implemented, BAT states plus 0002's booking log already carried the
distinction this decision wanted the field for (`booked` = customer
via checkout, `not_available` = orderless staff block, and the log
records the acting party per transition). The "admin events set
state straight to `booked`" bullet was also deliberately not
followed: staff blocks are `not_available` (bee's own blocking
semantic), preserving the platform invariant that `booked` implies
a Commerce order behind it. The multi-hour granularity half of this
decision was largely superseded by
[[0016-facility-fixed-length-slots]]' fixed 30-minute slots.

## References

- Related decisions: [[0013-bat-bee-booking-framework]], [[0015-cancellation-refund-policy]]
- Project: [[shh-stables-platform]]
