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

# 0014: Pricing rule entity for facility bookings

## Status

accepted

## Context

Bookable Facility currently assumes a flat price-per-hour field. Real requirements
include peak/off-peak, weekday/weekend, and member/non-member rates. A flat field
per node cannot express this matrix.

## Decision

Introduce a dedicated pricing-rule config entity (or Commerce price resolver) keyed
by facility, time window, and rider membership state. Evaluated at add-to-cart time
via a custom `PriceResolver`, not stored on the node.

## Consequences

### Positive
- Rate changes don't require content edits
- Supports promotions/seasonal rates without touching booking logic

### Negative
- Additional entity type and admin UI to build and maintain

### Neutral
- Requires membership state to be resolvable at price-calculation time (depends on 0017)

## Alternatives Considered

### Alternative 1: Flat field per node
Rejected — cannot express peak/off-peak or member-tier variation.

### Alternative 2: Commerce Promotions only
Rejected as sole mechanism — promotions are discount-based, not a base-rate matrix.

## Implementation Notes

Custom `PriceResolverInterface` implementation, priority above default resolver.
See [[shh-stables-platform-model]].

## References

- Related decisions: [[0013-bat-bee-booking-framework]], [[0017-anonymous-vs-authenticated-booking-access]]
- Project: [[shh-stables-platform]]
