---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-12
---
# Task: Facility utilization and revenue reporting

## Description
No reporting exists for facility utilization rate or revenue split by order item
type (horse sales vs bookings vs deposits).

## Acceptance criteria
- [x] Admin report: utilization % per facility per week/month
- [x] Admin report: revenue by order item type over a date range
- [x] Exportable (CSV) for accounting

## Resolution (2026-07-12)

New module `web/modules/custom/shh_reporting`: two staff reports
under `/admin/reports/` (menu-linked, gated by `access site reports`
like the retention status page; verified 403 for anonymous and a
logged-in rider), each with a "Download CSV" export carrying the
same range. Ranges are `from`/`to` (+ `granularity`) query
parameters with preset links (last 8 weeks / last 6 months / YTD;
last 30 days / this month / last month / YTD) — deliberately no FAPI
filter form.

**Facility utilization** (`/admin/reports/facility-utilization`):
per facility per ISO week or month — open hours (12 h/day per 0016's
08:00–20:00 booking day × unit count, periods clipped to the range),
booked hours, staff-blocked hours (reported separately, not counted
as utilization), and booked÷open %. Sourced from BAT events, the
platform's occupancy truth: `*_booked` events (0015 cancellations
release events, so they correctly stop counting) and
`*_not_available` blocks (0004); duplicate events on the same unit
and window (0012 creates one per rider of capacity) are deduped — a
slot is occupied once no matter how many riders share it. Facility
names via the unit→node map, **now factored into
`shh_common_bat_unit_facility_map()`** (second consumer; 0004's
staff calendar refactored onto it, its events feed re-verified).

**Revenue by order item type** (`/admin/reports/revenue`): placed,
non-canceled orders in the range, grouped by order item type
(labels from the order item type entities — horse, deposits, BEE
bookings, feed, credit packs). Item lines are gross (VAT-inclusive,
before order-level adjustments); order-level adjustments (e.g.
0017's bundle discount) can't be attributed to one item type and get
their own lines, so the report **always reconciles with Commerce's
own order totals**, shown as the footer line.

**Verified over real HTTP as admin against hand-computed truth**:
revenue for 2026-07-01–12 = Horse 4 orders/112,000 DKK + BEE 7
orders/9 items/250 DKK + credit pack 50 DKK = 112,300 DKK exactly
matching the 11 orders' summed totals; utilization for week 2026-W28
= Oval Track 3.0 booked hours (exactly the six 30-minute booked
events in the DB) + 0.5 blocked (the one staff block) over 84 open
hours = 3.6%, Manège/Lunge Ring 0%. CSV exports checked line by
line. phpcs clean; config: `core.extension` only, exported
(decision 0020).

## Related
- [[shh-stables-platform]]
- [[0004-staff-admin-booking-calendar]]
- [[0002-booking-lifecycle-notifications-audit]]
- [[0016-facility-fixed-length-slots]]
