---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Fixed 30-minute booking slots (8am–8pm) for Oval Track, Manège, Lunge Ring

## Description
Client request: Oval Track, Manège, and Lunge Ring should only be bookable
in fixed 30-minute slots between 8am and 8pm, priced per slot: Oval Track
50 DKK, Manège 30 DKK, Lunge Ring 20 DKK.

## What BEE already provided (no new code needed)
- **Opening-hours restriction**: `field_open_hours` (an `office_hours`-type
  field) + `field_use_open_hours`, both already present on `bookable_facility`
  since [[0011-shh-entity-content-type-modeling]] (part of BEE's own
  provisioning) but never populated. `AddReservationForm::validateForm()`
  already rejects any booking outside the configured window — just needed
  data (08:00–20:00, all 7 days), not code.
- **Sub-hour pricing**: `field_price_frequency` already supports `minute`
  as well as `hour` (`bee_add_price_frequency_field()`), and
  `bee_get_unit_price()`'s "minute" branch correctly computes
  `price_per_minute × minutes_booked`. Set `field_price` to the per-minute
  rate and `field_price_frequency: minute` for each facility — no code
  needed here either.
  **Found while doing this**: the `hour` branch is actually broken for any
  non-whole-hour duration — it computes hours via `$interval->h` (whole
  hours only, e.g. a 30-minute `DateInterval` has `h=0`), so a 30-minute
  booking priced with `frequency: hour` would compute **0.00 DKK**, not
  half the hourly rate. This is a real bug in `bee.module` itself, not
  something introduced here — worth knowing before configuring any other
  sub-hour-priced facility on this platform in the future.

## What didn't exist and needed building
BEE has no concept of "must be *exactly* N minutes, aligned to a slot
boundary" — its "hourly" `bookable_type` is actually flexible/arbitrary-
duration bookings (any start/end within the open-hours window), not
literally hour-long. New custom module
`web/modules/custom/shh_facility_slots`:
- `field_slot_duration_minutes` (integer, optional) on `bookable_facility` —
  opt-in per facility, not a global rule, consistent with this project's
  "policy as data" pattern (0014/0015/0001). Empty = BEE's default
  flexible-duration behavior for any future facility that doesn't want
  fixed slots.
- A `#validate` callback added to `bee_add_reservation_form` (alongside,
  not replacing, BEE's own validation) rejecting: a start time not aligned
  to a slot boundary (minutes not a multiple of the slot duration), or a
  requested duration not exactly equal to the slot duration.

## Acceptance criteria
- [x] All three facilities restricted to 08:00–20:00, every day
- [x] Bookings must be exactly 30 minutes, aligned to :00/:30 — verified
      rejected: wrong duration (60 min), misaligned start (:15)
- [x] Oval Track 50 DKK / Manège 30 DKK / Lunge Ring 20 DKK per 30-minute
      slot — verified via real add-to-cart: all three computed exactly,
      no rounding artifacts despite Oval Track's and Lunge Ring's
      per-minute rate being a repeating decimal (1.666667, 0.666667 —
      rounds cleanly to 50.00/20.00 at the actual 30-minute booking length)
- [x] Verified end to end over real HTTP: valid slot succeeds and prices
      correctly for all three facilities; invalid duration, misaligned
      start, and outside-hours are each rejected with a clear message

## Related
- [[shh-stables-platform]]
- [[0011-shh-entity-content-type-modeling]]
- [[0016-booking-granularity-admin-events]]
</content>
