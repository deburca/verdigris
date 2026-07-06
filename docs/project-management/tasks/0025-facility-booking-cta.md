---
type: task
tags: [cms2/task]
status: backlog
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
- [ ] A clear, prominent "Book Now" / "Reserve a slot" link/button on the
      facility node's rendered page, linking to its add-reservation form
      (mirrors the pattern already used for the deposit link in
      `shh_horse_deposit_commerce_product_view()` and the credit pack link
      in `shh_facility_credits_node_view()` — a `hook_ENTITY_TYPE_view()`
      implementation, likely in a new or existing shh module)
- [ ] Verified end to end: landing on the facility page, a rider can reach
      the booking form without knowing the URL in advance

## Related
- [[shh-stables-platform]]
- [[shh-rider-journey-gap-analysis]]
- [[0020-facilities-overview-page]]
- [[0018-facility-credit-packs]]
</content>
