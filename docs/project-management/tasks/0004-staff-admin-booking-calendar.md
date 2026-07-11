---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-11
---
# Task: Staff admin booking calendar and manual override UI

## Description
No staff-facing view exists for cross-facility availability or manual
block/override booking creation (0016's `event_source: admin` path).

## Acceptance criteria
- [x] Combined calendar view across all Bookable Facility nodes
- [x] Staff can create/cancel `admin`-sourced events without touching
      Commerce cart
- [x] Visual distinction between customer and admin events

## Resolution (2026-07-11)

New module `web/modules/custom/shh_staff_booking_calendar`:
`/admin/booking-calendar` (admin-theme route, linked under the admin
Content menu) renders a combined FullCalendar week/month/day view of
**every** facility's events — using the locally vendored FullCalendar
6 bundle from [[0009-vendor-fullcalendar-library]] (no CDN, attached
via bat_fullcalendar's library), a small `Drupal.behaviors` init JS,
and the module's own JSON events feed.

**Access**: gated by bee's own
`manage availability for all bookable_facility nodes` permission — the
same permission that gates bee's per-facility screens and that 0002's
logger uses for actor classification, so "staff" stays ONE concept
platform-wide (no new permission; granted to no role, admins via
bypass, like every staff surface here). Verified 403 for anonymous and
for a logged-in rider on all four routes.

**Deliberate deviation from decision
[[0016-booking-granularity-admin-events]], recorded there too**: no
`event_source` field. The decision predates the build-out; since then
BAT states + 0002's booking log already carry the distinction —
`booked` = customer via checkout (0012 promotes holds), `on_hold` =
cart, `not_available` = orderless staff block (bee's own blocking
semantic), and the log records the acting party per transition. Also
deviating from the decision's "admin events set state straight to
`booked`": staff blocks are **`not_available`**, preserving the
platform invariant that `booked` implies a Commerce order behind it
(0002's classification and this module's own order lookup rely on
it).

**The feed** classifies and colors events (with a page legend):
customer bookings **blue**, titled with the order number and linking
to the Commerce order admin page (the 0002 event→bat_booking→order
item→order chain; an orderless booked event renders "no order");
cart holds **amber**; staff blocks **grey**, linking to the
remove-block confirm form. `available` records are skipped — the
calendar shows what occupies time. Facility names come from the
unit→node resolution (unit labels are stale — unit 1 still says
"Outdoor Arena 1").

**Staff block create/remove without Commerce**:
- `/admin/booking-calendar/block`: facility select + date + 30-minute
  time steps over the 08:00–20:00 booking day (0016's slot rules, for
  UI tidiness only). Creates `bee_hourly_not_available` events
  directly via the BAT entity API (one per unit, so multi-unit
  facilities block fully) — the 0012 event-creation pattern, no cart,
  no order. **Windows holding a booking, hold, or existing block are
  rejected** with a message naming the conflict — cancelling a
  customer booking has its own policy-aware flow (0015) and is
  deliberately impossible from here.
- `/admin/booking-calendar/release/{event}`: confirm form; refuses
  (404) anything that isn't a `*_not_available` event — verified
  against a real booked event.
- No "reason" field: bat_event has no label field to carry one and
  the booking log records who/when; revisit if staff ask.

**Verified over real HTTP as admin + rider**: created a real block on
Manège (10:00–11:00) through the form → feed showed it grey with the
release URL, 0002's log recorded `not_available` by **staff**; a real
test_rider booking of Oval Track (temp membership for 0003's gate,
deleted after) showed the **amber hold mid-checkout, then blue
"booked (11)" linking to order 49** after completion — the full
customer lifecycle visible in the staff feed; releasing the block via
the real confirm form removed it (log: `deleted` by staff); the
booked event correctly 404'd on the release route. In a real headless
Chromium, the calendar rendered with all three event kinds visually
distinct (screenshot; the module JS + vendored FC bundle exercised
via a stubbed-runtime test page, since headless CLI has no admin
session). Test order/event/membership cleaned up; booking-log rows
15–18 remain as genuine audit history.

phpcs clean (`--extensions=php,module,inc,install,theme` — the
Drupal phpcs standard misapplies the PHP uppercase-constant sniff to
`.js`, where uppercase `TRUE` would be invalid JavaScript; JS is
eslint territory). Config: only `core.extension` (module install),
exported.

## Related
- [[shh-stables-platform]]
- [[0016-booking-granularity-admin-events]]
- [[0009-vendor-fullcalendar-library]]
- [[0002-booking-lifecycle-notifications-audit]]
- [[0012-cart-hold-concurrency-prototype]]
