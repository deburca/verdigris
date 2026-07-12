---
tags:
  - cms2/decision
status: proposed
created: 2026-07-12
updated:
decided:
site: shh
deciders:
  - Architecture Team
---

# 0021: Per-facility cart-hold TTL vs one platform-wide value

## Status

proposed

## Context

The facility-booking cart expiration is a single platform-wide value:
30 minutes on the `default`/"Facility booking" order type
([[0018-separate-order-types-horse-vs-booking]] decoupled it from
horse sales, which get 3 days). Since [[0012-cart-hold-concurrency-prototype]],
a cart-add also places an on-hold BAT event, so the TTL directly
controls how long an abandoned cart keeps a slot off the public
calendar. The open question from the project file: should this be
configurable per facility instead?

## Decision

**Proposed: keep the single platform-wide 30-minute TTL until a
facility demonstrates a real need.** All three facilities share the
same booking shape (fixed 30-minute slots, 08:00–20:00, low volume,
same clientele); no facility has exhibited hold-contention that a
per-facility value would fix. Commerce's cart expiration is an
order-type setting, so "per facility" cannot reuse it — it would mean
custom expiry logic in `shh_booking_hold` (per-node TTL field
consulted by the expiry sweep), which is real complexity for a
hypothetical.

Revisit trigger: a facility whose slots are genuinely scarce (holds
regularly blocking real riders), or a new facility type with a
different booking rhythm (e.g. full-day hire).

## Consequences

### Positive
- No new configuration surface or custom expiry code to maintain
- The 0012 hold/release pipeline stays exactly as verified

### Negative
- A future scarce facility inherits a TTL tuned for the general case
  until this decision is revisited

### Neutral
- The project file's long-standing open question gets an owner and a
  written default instead of floating

## Alternatives Considered

### Alternative 1: Per-facility TTL field now
Rejected for now — custom expiry logic duplicating Commerce's cart
expiration, for a need no facility currently has.

## References

- Related decisions: [[0018-separate-order-types-horse-vs-booking]]
- Tasks: [[0012-cart-hold-concurrency-prototype]]
- Project: [[shh-stables-platform]]
