---
type: task
tags: [cms2/task]
status: backlog
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
- Verify `/node/{node}/availability` actually renders for our hourly/fixed-
  slot facilities (0016) — depends on `bat_fullcalendar`, which per
  [[0009-vendor-fullcalendar-library]] still falls back to a CDN for
  FullCalendar assets; resolve or accept that dependency first
- Link to it from each facility page, and from the facilities overview
  page (0020)
- Confirm the calendar correctly reflects `on-hold` slots (0012) as
  unavailable, not just `booked` ones — an availability calendar showing
  a slot as free when it's actually mid-checkout for someone else would
  be worse than not having one
- **Decide who can actually see this data.** Found while fixing an
  unrelated crash in [[0025-facility-booking-cta]]: no role — not
  `anonymous`, not `authenticated` — currently has the `restful get
  bat_api_events_resource` permission that the calendar widget's own
  event-fetching REST endpoint requires, so today a real rider gets a
  silent `403` from it (an admin/uid-1 session is the only one that
  currently sees any calendar data at all, bugs notwithstanding). Grant it
  to whichever role(s) this task decides should see availability data
  before considering the calendar "working."

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0009-vendor-fullcalendar-library]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0016-facility-fixed-length-slots]]
- [[0025-facility-booking-cta]] (fixed a crash in the calendar widget's
  REST endpoint along the way; see that task's Resolution)
</content>
