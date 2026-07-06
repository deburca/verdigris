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
# Task: Public facility availability calendar

## Description
`bee` ships a `/node/{node}/availability` route out of the box, but it
isn't linked from anywhere on the site and hasn't been verified to render
correctly here. A rider currently has to open the booking form and try
specific times blind (rejected if unavailable) rather than seeing open
slots up front.

## Acceptance criteria
- [x] Verify `/node/{node}/availability` actually renders for our
      hourly/fixed-slot facilities (0016) — depends on
      `bat_fullcalendar`, which per
      [[0009-vendor-fullcalendar-library]] still falls back to a CDN
      for FullCalendar assets; resolve or accept that dependency first
- [x] Link to it from each facility page, and from the facilities
      overview page (0020)
- [x] Confirm the calendar correctly reflects `on-hold` slots (0012) as
      unavailable, not just `booked` ones — an availability calendar
      showing a slot as free when it's actually mid-checkout for
      someone else would be worse than not having one
- [x] **Decide who can actually see this data.** Found while fixing an
      unrelated crash in [[0025-facility-booking-cta]]: no role — not
      `anonymous`, not `authenticated` — currently has the `restful get
      bat_api_events_resource` permission that the calendar widget's
      own event-fetching REST endpoint requires, so today a real rider
      gets a silent `403` from it (an admin/uid-1 session is the only
      one that currently sees any calendar data at all, bugs
      notwithstanding). Grant it to whichever role(s) this task decides
      should see availability data before considering the calendar
      "working."

## Resolution (2026-07-06)

**Correction to this task's first acceptance criterion**: `/node/{node}/availability`
does **not** render for a public visitor at all — confirmed directly
(`BeeAvailabilityAccessCheck` requires `manage availability for all/own
bookable_facility nodes`, and a real anonymous request to that route
gets bounced to the login page). This route is bee's own **staff
management screen** (a calendar plus an admin form to manually
block/unblock slots), not a public availability viewer, and was never
going to be suitable for one. The actual "public availability calendar"
already exists elsewhere: `field_availability_hourly`'s `hourly_calendar`
view mode, embedded directly on every Bookable Facility node's own page,
via the same FullCalendar widget whose REST data feed
[[0025-facility-booking-cta]] already fixed a fatal crash in.

New custom module `web/modules/custom/shh_public_availability`:

- Grants `restful get bat_api_events_resource` to **both** `anonymous`
  and `authenticated` — closing this task's own permission question, per
  decision 0017's explicit implementation note: "Public read-only
  availability calendar stays anonymous; only the add-to-cart action is
  gated." `anonymous` already had `view calendar data for any
  availability_hourly/daily event`; this was the missing half.

Since the calendar lives on the facility page itself rather than a
separate route, "link to it" is satisfied by the facilities overview
page (0020) linking to each facility page, where the widget is already
embedded.

**Verified on-hold handling over real HTTP**, end to end: added a
booking to cart without completing checkout (placing an on-hold BAT
event per 0012), then queried the REST endpoint anonymously — the
initial query returned nothing at all due to a research dead-end (see
below), but once corrected, the slot showed up with `"blocking":1`,
identical to a genuinely `booked` slot, confirming BAT's own state
configuration (`bee_hourly_on_hold` state has `blocking: 1`, same as
`bee_hourly_booked`) already handles this correctly — no code change
was needed for the on-hold behaviour itself.

**A confusing false alarm worth recording so it isn't re-investigated**:
manually querying the REST endpoint with `unit_types=hourly` (the
bookable-type machine name, matching what the query parameter's name
suggests) returned an empty result even for slots with confirmed,
correctly-stored blocking events — looking like a serious bug in
`bat_api`'s event aggregation. Traced all the way down to BAT's
day/hour/minute state-store tables (confirming the underlying data was
correct) before finding the real explanation: `unit_types` must be the
**numeric `unit_type` content-entity ID** (e.g. `2` for Oval Track's
unit), not the bookable-type string — confirmed by checking the actual
embedded widget's own `drupalSettings` (`"unitType":"2"`). The real
widget already sends the correct value automatically; this was purely a
manual-testing mistake, not a bug in the endpoint itself.

`bat_fullcalendar`'s CDN-loaded FullCalendar assets ([[0009-vendor-fullcalendar-library]])
remain accepted as-is — out of scope here.

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0009-vendor-fullcalendar-library]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0016-facility-fixed-length-slots]]
- [[0025-facility-booking-cta]] (fixed a crash in the calendar widget's
  REST endpoint along the way; see that task's Resolution)
</content>
