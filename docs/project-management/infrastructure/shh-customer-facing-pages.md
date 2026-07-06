---
tags: [cms2/infrastructure, cms2/notes]
site: shh
created: 2026-07-06
updated: 2026-07-06
---

# shh — Customer-Facing Landing Pages Audit

Catalog of pages/views/blocks a rider or buyer actually needs to find
things on stutteri-hestehoj.dk, vs. what currently exists. Prompted by a
direct request to map this out; gaps were tracked as tasks, not fixed
here.

> **Update 2026-07-06**: all five gap-tracking tasks below
> ([[0019-horse-catalog-page]] through
> [[0023-pricing-comparison-page]]) are now done. See each task's own
> Resolution for detail; the table below is updated to reflect the
> current state.

## Exists today

| Page | Path | Notes |
|---|---|---|
| Horse catalog (browse) | `/horses` | New (0019) — every `available` horse, linked from primary navigation |
| Horse product page | `/product/{id}` | Individual horse — now discoverable via the catalog page above |
| Facilities overview | `/facilities` | New (0020) — all three facilities, bundle-discount/credit-pack explainer, linked from primary navigation |
| Facility page | `/oval-track`, `/manege`, `/lunge-ring` | Individual facility — now discoverable via the overview page above; also has a public availability calendar embedded (`field_availability_hourly`) and a "Book now" CTA (0025) |
| Pricing comparison | `/pricing` | New (0023) — single-slot vs. credit-pack vs. bundle pricing side by side, linked from the facilities overview page |
| Rider dashboard | `/user/{user}/bookings` | New (0022) — upcoming/past bookings, active deposits, credit balances, linked from the rider's own account page |
| Book a facility | `/node/{node}/add-reservation` | Linked from the facility page (0025) |
| Buy horse deposit | `/product/{id}/deposit` | Linked from the horse product page (0001) |
| Buy facility credit pack | `/node/{node}/buy-credit-pack` | Linked from the facility page (0018), and from the rider dashboard above |
| Cancel booking | `/booking/{order_item}/cancel` | Now also surfaced from the rider dashboard (0022), not just inline on the order item's own view (0015) |
| Cancel deposit | `/deposit/{order_item}/cancel` | Same — also surfaced from the rider dashboard now (0022) |
| Cart | `/cart` | Standard Commerce |
| Checkout | `/checkout/{order}` | Standard Commerce |
| Admin: product catalog | `/admin/commerce/products` | **Staff-only** — this is *not* a public browse page, despite the name; `/horses` above is |
| Admin: facility credit balances | `/admin/commerce/facility-credits` | Staff-only (0018) |
| Admin: cancellation/deposit policies | `/admin/commerce/config/*` | Staff-only (0001, 0015) |
| Admin: rider memberships | `/admin/people/rider-memberships` | Staff-only (0003) |
| BEE availability management route | `/node/{node}/availability` | Confirmed (0021) this is a **staff-only management screen** (blocks/unblocks slots), not suitable for public use — not the same thing as the public calendar embedded on the facility page itself |

## Gaps — resolved

Previously: a rider/buyer with no direct link couldn't browse horses or
facilities, see availability up front, see their own bookings/deposits/
credits in one place, discover the bundle discount or credit packs, or
compare pricing. All resolved by
[[0019-horse-catalog-page]], [[0020-facilities-overview-page]],
[[0021-public-availability-calendar]], [[0022-rider-dashboard]], and
[[0023-pricing-comparison-page]] — see each task's Resolution for what
was built and how it was verified.

## Related
- [[shh-stables-platform]]
- [[0009-vendor-fullcalendar-library]]
</content>
