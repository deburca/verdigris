---
type: task
tags: [cms2/task]
status: done
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
- [x] `on-hold` BAT event state wired to Commerce cart-add for a Bookable Facility
- [x] Hold TTL tied to Commerce cart expiration config (open question: value, and
      whether it's configurable per facility — see [[shh-stables-platform]] open
      questions — **partially resolved, see Resolution**: tied to the shared
      "default" order type's `cart_expiration`, not yet per-facility)
- [x] Checkout completion promotes held events to `booked`
- [x] Cart expiry (abandoned cart) reverts held events back to `available`
- [x] Two-tab/concurrent-user manual test confirms no double-booking of the same
      hourly slot
- [~] Timezone handling: site timezone confirmed Europe/Copenhagen, code
      reviewed for DST-unsafe patterns (none found — only `DateInterval`-based
      arithmetic, no fixed-offset math). **Not done**: an actual booking test
      spanning a real spring-forward/fall-back transition moment — see
      Resolution for why this is flagged rather than checked off outright.

## Resolution (2026-07-05)

### Root cause confirmed
Traced BEE's actual cart flow (`AddReservationForm::submitForm()` +
`\Drupal\bee\EventSubscriber\OrderEventSubscriber::finalizeCart()`): BEE only
creates the actual BAT event (the thing that blocks a slot) at **checkout
completion** (`commerce_order.place.pre_transition`), not at cart-add. Between
add-to-cart and checkout completion there is no reservation at all — two
concurrent shoppers can both pass `validateForm()`'s availability check and
both add the same hourly slot. This confirms the architecture doc's framing
exactly.

### What was built
New custom module `web/modules/custom/shh_booking_hold`:
- **New BAT state** `bee_hourly_on_hold` (blocking, alongside BEE's stock
  `available`/`not_available`/`booked`).
- **`BookingHoldManager` service** (`src/BookingHoldManager.php`):
  - `placeHold()` — runs on `commerce_order_item` insert (bundle `bee`).
    Structurally mirrors `OrderEventSubscriber::finalizeCart()`'s event-
    creation logic, but creates the event **now**, in the on-hold state.
    Wrapped in a `\Drupal::lock()` keyed per-node to close the race window
    between two near-simultaneous cart-adds (this is what makes the two-tab
    test reliable rather than probabilistic — see "Verified" below).
  - `promoteToBooked()` — called from a new event subscriber on the same
    `commerce_order.place.pre_transition` event BEE itself uses. Since our
    hold already set `booking_event_reference`, BEE's own subscriber's
    `if (!isset($booking_event_reference))` guard makes it a no-op — no
    ordering dependency between the two subscribers.
  - `releaseHold()` — called from `hook_commerce_order_predelete()` (skipped
    if the order was actually placed) and `hook_commerce_order_item_predelete()`
    (covers a single item being removed from an otherwise-live cart). Reacting
    to order deletion — rather than a separate cron/TTL sweep — is what makes
    the hold's expiry **tied to Commerce Cart's own config-driven cleanup**
    (`\Drupal\commerce_cart\Cron`, keyed off the order type's
    `cart_expiration` third-party setting) instead of a second, independent
    timer that could drift out of sync.
- `hook_install()` sets `cart_expiration` on the `default` order type to 30
  minutes if not already set.

### Verified (manual, over real HTTP + direct API)
1. POSTed a reservation for facility node 3, 2026-07-06 10:00–11:00 →
   redirected to checkout; confirmed event created directly in `bee_hourly_on_hold`
   state (not `booked`).
2. From a **separate** anonymous session, POSTed the identical slot → rejected
   at form validation with "No available units" (the hold from step 1 made it
   correctly invisible to `getAvailableUnits()`).
3. Applied the order's `place` workflow transition programmatically (same
   event a real checkout completion fires) → the on-hold event was promoted
   to `bee_hourly_booked`.
4. Created a second booking (different slot) in a fresh cart, then deleted
   that cart order (simulating abandonment/expiry) → its event was correctly
   reverted from `bee_hourly_on_hold` to `bee_hourly_available`.

### Known limitations / follow-ups (deliberately not solved by this prototype)
- **Shared order-type TTL.** `cart_expiration` lives on the `default` order
  type, which — per the mixed-order architecture — is shared between horse
  sales and facility bookings. A 30-minute hold is reasonable for an hourly
  slot but aggressive for someone still deciding on a 45,000 DKK horse. This
  is the concrete shape of the "configurable per facility" open question —
  solving it properly likely means moving hold-expiry logic off the order's
  `cart_expiration` entirely (e.g. a per-order-item expiry check) rather than
  relying on Commerce Cart's order-level cron. Left open for
  [[0013-mixed-order-checkout-prototype]] to reconcile.
- **Lock granularity.** The lock in `placeHold()` is per-node, not per-
  timeslot — coarser than necessary but fine at this platform's expected
  booking volume; note it if this ever needs to scale.
- **DST transition** not tested against a real spring-forward/fall-back
  instant (see acceptance criteria above) — code review didn't find unsafe
  patterns, but this wasn't empirically exercised.
- Access to `/node/{node}/add-reservation` currently requires the `create bee
  reservation` permission, which is **not** granted to any role by design —
  this is intentionally [[0017-anonymous-vs-authenticated-booking-access]]'s
  decision to make, not this task's. It was granted temporarily to the
  `anonymous` role for the manual test above and explicitly reverted
  afterward.

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0013-bat-bee-booking-framework]]
- [[0011-shh-entity-content-type-modeling]]
- [[0013-mixed-order-checkout-prototype]]
- [[0017-anonymous-vs-authenticated-booking-access]]
</content>
