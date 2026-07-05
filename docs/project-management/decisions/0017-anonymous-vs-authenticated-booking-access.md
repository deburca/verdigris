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
  - Security Team
---

# 0017: Authentication required before facility booking add-to-cart

## Status

accepted

## Context

Booking eligibility (membership, liability waiver) cannot be evaluated for an
anonymous cart. Horse sales have no such constraint. No policy currently exists on
when login is enforced.

## Decision

Require authentication before a Booking order item can be added to cart (route/access
check on the add-to-cart action for Bookable Facility only). Horse products remain
purchasable anonymously through standard Commerce checkout.

## Consequences

### Positive
- Pricing rules (0014) and eligibility checks always have a resolvable rider at
  cart-add time
- Avoids anonymous cart abandonment holding slots without accountability

### Negative
- Adds friction — rider must register before checking availability in detail (calendar
  view itself can remain public/read-only)

### Neutral
- Waiver/membership approval flow (task 0003) becomes a checkout blocker, not just
  a data field

## Alternatives Considered

### Alternative 1: Allow anonymous booking, collect details at checkout
Rejected — defers eligibility/waiver check too late, complicates the cart-hold
mechanism (0013) since no rider identity exists to attach the hold to.

## Implementation Notes

Public read-only availability calendar stays anonymous; only the add-to-cart action
is gated.

## References

- Related decisions: [[0013-bat-bee-booking-framework]], [[0014-pricing-rule-entity]]
- Project: [[shh-stables-platform]]
