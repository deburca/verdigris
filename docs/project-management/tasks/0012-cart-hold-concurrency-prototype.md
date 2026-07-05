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
# Task: Prototype the booking cart-hold / concurrency mechanism

## Description
Flagged in [[shh-stables-platform-model]] as the hardest problem in the build —
prototype before investing in surrounding UI. A cart-add must transition the
relevant hourly BAT events to `on-hold` with a TTL tied to Commerce cart
expiration; checkout completion promotes them to `booked`; cart expiry reverts
them to `available`. Not provided out of the box by BAT/BEE (see
[[0013-bat-bee-booking-framework]] "Neutral" consequences).

## Acceptance criteria
- [ ] `on-hold` BAT event state wired to Commerce cart-add for a Bookable Facility
- [ ] Hold TTL tied to Commerce cart expiration config (open question: value, and
      whether it's configurable per facility — see [[shh-stables-platform]] open
      questions)
- [ ] Checkout completion promotes held events to `booked`
- [ ] Cart expiry (abandoned cart) reverts held events back to `available`
- [ ] Two-tab/concurrent-user manual test confirms no double-booking of the same
      hourly slot
- [ ] Timezone handling verified: stored in UTC, rendered in Europe/Copenhagen,
      spring-forward/fall-back boundaries tested explicitly

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0013-bat-bee-booking-framework]]
- [[0011-shh-entity-content-type-modeling]]
- [[0013-mixed-order-checkout-prototype]]
</content>
