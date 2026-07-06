---
tags: [cms2/infrastructure, cms2/notes]
site: shh
created: 2026-07-06
updated: 2026-07-06
---

# shh — Customer-Facing Landing Pages Audit

Catalog of pages/views/blocks a rider or buyer actually needs to find
things on stutteri-hestehoj.dk, vs. what currently exists. Prompted by a
direct request to map this out; gaps are tracked as tasks, not fixed here.

## Exists today

| Page | Path | Notes |
|---|---|---|
| Horse product page | `/product/{id}` | Individual horse only — no listing/browse page links to these (see gap below) |
| Facility page | `/oval-track`, `/manege`, `/lunge-ring` | Individual facility only — no listing page |
| Book a facility | `/node/{node}/add-reservation` | Linked from the facility page |
| Buy horse deposit | `/product/{id}/deposit` | Linked from the horse product page (0001) |
| Buy facility credit pack | `/node/{node}/buy-credit-pack` | Linked from the facility page (0018) |
| Cancel booking | `/booking/{order_item}/cancel` | Only surfaced inline on the order item's own rendered view (0015) — not from a dashboard |
| Cancel deposit | `/deposit/{order_item}/cancel` | Same limitation (0001) |
| Cart | `/cart` | Standard Commerce |
| Checkout | `/checkout/{order}` | Standard Commerce |
| Admin: product catalog | `/admin/commerce/products` | **Staff-only** — this is *not* a public browse page, despite the name |
| Admin: facility credit balances | `/admin/commerce/facility-credits` | Staff-only (0018) |
| Admin: cancellation/deposit policies | `/admin/commerce/config/*` | Staff-only (0001, 0015) |
| BEE availability route | `/node/{node}/availability` | Route exists (ships with `bee`), but linked from nowhere and unverified — depends on `bat_fullcalendar`, which is still CDN-fallback per [[0009-vendor-fullcalendar-library]] |

## Gaps — no discovery path exists

A rider/buyer who doesn't already have a direct link cannot currently:
- Browse the list of horses for sale
- Browse the list of bookable facilities
- See a public availability calendar for a facility before committing to
  the booking form
- See their own bookings/deposits/credit balances in one place (cancel
  links only appear buried in individual order item views)
- Discover the bundle discount (0017) or credit packs (0018) exist at all,
  beyond stumbling onto the one facility page that mentions it

Tracked as: [[0019-horse-catalog-page]],
[[0020-facilities-overview-page]],
[[0021-public-availability-calendar]],
[[0022-rider-dashboard]].

## Related
- [[shh-stables-platform]]
- [[0009-vendor-fullcalendar-library]]
</content>
