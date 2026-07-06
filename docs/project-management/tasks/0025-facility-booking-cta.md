---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Add a "Book Now" link to the facility page

## Description
Found via [[shh-rider-journey-gap-analysis]]: a Bookable Facility node's
own page (`/oval-track`, `/manege`, `/lunge-ring`) links to buying a
credit pack (0018) but has **no link at all** to the actual reservation
form (`/node/{node}/add-reservation`). Every booking made during this
entire project was reached by typing that URL directly — a real rider has
no way to discover it from the page itself. More fundamental than the
facilities-overview gap ([[0020-facilities-overview-page]]): even once
that page exists and links here, the trail goes cold at this exact spot.

## Acceptance criteria
- [x] A clear, prominent "Book Now" / "Reserve a slot" link/button on the
      facility node's rendered page, linking to its add-reservation form
      (mirrors the pattern already used for the deposit link in
      `shh_horse_deposit_commerce_product_view()` and the credit pack link
      in `shh_facility_credits_node_view()` — a `hook_ENTITY_TYPE_view()`
      implementation, likely in a new or existing shh module)
- [x] Verified end to end: landing on the facility page, a rider can reach
      the booking form without knowing the URL in advance

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_facility_booking_cta`:

- **`hook_ENTITY_TYPE_view()` for `node`** — adds a "Book now" link
  (`button button--primary`, weighted to appear *above* the credit-pack
  link since it's the primary action) on any `bookable_facility` node,
  pointing at bee's own `bee.node.add_reservation` route
  (`/node/{node}/add-reservation`). Exactly the pattern the acceptance
  criteria anticipated — no bee/Commerce changes needed, just the link.

**A more fundamental blocker was found while verifying this task**, not
mentioned in the original task text: **no role on this site — not
`anonymous`, not `authenticated` — actually had the `create bee
reservation` permission that gates access to the add-reservation route
itself**. Every previous "verified over real HTTP" booking test in this
project's history (0012, 0016, 0017, etc.) must have run as an admin/uid-1
session, which bypasses permission checks. A "Book now" link alone would
have sent every real visitor straight to an Access Denied page — the
acceptance criterion "a rider can reach the booking form" would have been
false. Fixed in the same module's `hook_install()`: grants `create bee
reservation` to the `authenticated` role (not `anonymous`, matching
[[0017-anonymous-vs-authenticated-booking-access]]'s decision exactly —
authentication is required before booking).

**Verified end to end over real HTTP as a genuine non-admin account**
(not uid-1): created a plain `authenticated`-only test user
(`shh_test_rider`, uid 3 — a second test account alongside 0018's
existing `test_rider`, uid 2; both harmless, left in place). Logged in
over HTTP, landed on `/oval-track`, confirmed the "Book now" link
renders and points at `/node/3/add-reservation`, followed it (200, not
403 — the fix that actually mattered), submitted a real 30-minute slot
booking, and completed checkout through to a real placed order (order
number 4, `state: completed`). Watchdog confirms
`shh_booking_hold` (0012) placed an on-hold event and correctly promoted
it to booked on order placement — the new link connects to the fully
working booking pipeline built across this whole project, not just a
form that renders.

**A second, unrelated bug was reported by the client after this task
first shipped**: the "Book now" link wasn't visible on `/oval-track`, and
a raw PHP error was showing instead — `Call to a member function
usesStates() on null` in `bat_api`'s `EventsRestResource::get()` (line
196). Root cause: `field_availability_hourly`'s `hourly_calendar` view
mode (rendered above the CTA on every Bookable Facility page) embeds a
FullCalendar widget that fetches event data from
`/bat_api/rest/calendar-events`; that REST resource has always fatally
errored on every call, on this site or otherwise, because
`$this->eventType` is never assigned anywhere in the class and
`usesStates()` doesn't exist on `EventType` at all (confirmed: no other
call site anywhere in `bat`/`bat_api` uses that method name — the correct,
already-existing equivalent, used elsewhere in this exact same method and
17 other places across the module, is `getFixedEventStates()`). This bug
is **unrelated to `shh_facility_booking_cta`'s own code** — confirmed via
a direct `curl` fetch of `/oval-track`'s raw server-rendered HTML, which
always contained the "Book now" link and its correct `href`, both before
and after this fix; the calendar widget's failure is a separate,
client-side (JS/AJAX) concern that doesn't affect the CTA's own markup.
Still fixed properly, via a composer patch (this project's standard
approach for contrib bugs — see decision 0006 and the existing `bee`
patch): `patches/bat_api-fix-events-rest-resource-undefined-eventtype.patch`,
registered in `composer.json` under `drupal/bat_api`, and applied directly
to the installed module so it takes effect immediately in this
environment too. Verified by calling the REST endpoint directly: `500`
fatal before, clean `200 []` (empty result — no matching events in that
range yet) after. Separately confirmed (then reverted, out of scope here):
even with the fatal fixed, **no role has the `restful get
bat_api_events_resource` permission either**, so a real (non-admin) rider
would currently get a silent `403` from this same endpoint instead of a
crash — the calendar widget doesn't actually show real availability data
to anyone but an admin/uid-1 session today. That's a policy decision
([[0021-public-availability-calendar]]'s territory: should anonymous or
authenticated riders see this data at all?), not a bug fix, so it was
deliberately left alone here.

## Related
- [[shh-stables-platform]]
- [[shh-rider-journey-gap-analysis]]
- [[0020-facilities-overview-page]]
- [[0018-facility-credit-packs]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0017-anonymous-vs-authenticated-booking-access]]
</content>
