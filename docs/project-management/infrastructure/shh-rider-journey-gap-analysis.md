---
tags: [cms2/infrastructure, cms2/notes]
site: shh
created: 2026-07-06
updated: 2026-07-06
priority: high
---

# shh — Rider Journey Gap Analysis: "Buy a Horse" / "Book a Facility"

Walking both core journeys step by step as an actual rider would experience
them, not just checking that the underlying features individually work.
Two **critical** gaps found that are more serious than the discovery-page
gaps already tracked in [[shh-customer-facing-pages]] — these aren't "hard
to find," they're "the system doesn't stop a horse being sold twice."

## Journey: Buy a Horse

| Step | Status |
|---|---|
| Find a horse to buy | ❌ Gap — no catalog page ([[0019-horse-catalog-page]]) |
| View horse details (breed, gaits, vetting, price) | ✅ Works |
| **See whether a horse is still available before buying** | 🔴 **Critical gap — see below** |
| Choose: pay in full, or pay a deposit (0001) | ✅ Both options present on the product page |
| Pay in full → checkout → order placed | ✅ Works (VAT correct per 0005) |
| **Horse marked "sold" after full purchase** | 🔴 **Critical gap — never happens** |
| Pay a deposit → order placed | ✅ Correctly marks `reserved-deposit` (0001) |
| Arrange remaining balance | ⚠️ By design, manual/staff-driven (confirmed with client) — but nothing *tells staff a deposit was just paid* |
| Cancel a deposit | ✅ Works, but only reachable via a link buried on the order item's own view ([[0022-rider-dashboard]]) |

### 🔴 Critical gap 1: nothing stops buying an unavailable horse
Confirmed by code review: `field_sale_state` is referenced **only** inside
`shh_horse_deposit` (which checks it before allowing a deposit). It is
**never** checked by the standard Commerce `AddToCartForm` used for a full
purchase. Right now, a horse marked `reserved`, `reserved-deposit`,
`sold`, or `withdrawn` can still be added to cart and bought by a second
buyer — there is no technical reason two people couldn't both "buy" the
same horse. This is a data-integrity/business gap, not a discovery gap.

### 🔴 Critical gap 2: nothing marks a horse "sold"
`shh_horse_deposit`'s `DepositCheckoutCompletionSubscriber` transitions
`sale_state` to `reserved-deposit` when a *deposit* order is placed — but
there is no equivalent subscriber for the standard `horse` order item type.
After a full-price purchase completes, the horse's `sale_state` stays
`available` indefinitely unless a staff member remembers to change it by
hand, with no prompt reminding them to. Combined with gap 1, a horse could
be sold to multiple buyers before anyone notices.

### ⚠️ Secondary gap: no staff notification on deposit payment
When a deposit is paid, nothing alerts staff that they need to follow up
to arrange the balance (the confirmed manual/staff-driven design from
0001 assumes staff *know* to look). Overlaps with the already-backlog
[[0002-booking-lifecycle-notifications-audit]] — not duplicating that task,
just noting the deposit flow is a concrete case of it.

## Journey: Book a Facility

| Step | Status |
|---|---|
| Find a facility to book | ❌ Gap — no overview page ([[0020-facilities-overview-page]]) |
| View facility details | ✅ Works |
| **Find the link to actually book it** | 🔴 **Critical gap — see below** |
| See availability before picking a time | ❌ Gap — calendar route exists but unlinked/unverified ([[0021-public-availability-calendar]]) |
| Pick a valid 30-min slot, 8am–8pm (0016) | ✅ Works, validated correctly |
| Add to cart — no double-booking (0012) | ✅ Works, verified under real concurrency |
| Use a credit instead of paying (0018) | ✅ Works, but only if you already found the credit-pack purchase link |
| Book all three facilities same slot for a discount (0017) | ✅ Works, but completely undiscoverable ([[0023-pricing-comparison-page]]) |
| Pay, checkout | ✅ Works (VAT correct) |
| Cancel a booking | ✅ Works, same buried-link issue as deposits ([[0022-rider-dashboard]]) |

### 🔴 Critical gap 3: the facility page has no "Book Now" link at all
Confirmed by direct check: `/oval-track` shows the "Buy a 10-session credit
pack" link (0018) but **nothing linking to
`/node/{node}/add-reservation`** — the actual booking form. Every booking
tested this entire project was reached by typing that URL directly. A real
rider landing on the facility page today has no way to discover it without
being told the URL out of band. This is more fundamental than the overview-
page gap (0020) — even once 0020 exists and links to the facility page,
the trail still goes cold there today.

## Priority read
Gaps 1–3 above are more urgent than the discovery-page backlog
(0019–0023): those are "hard to find," these are "doesn't work as a real
transaction, or doesn't work at all." Recommend tackling
[[0024-horse-sale-state-enforcement]] and
[[0025-facility-booking-cta]] before the catalog/overview pages, since a
catalog page would currently just funnel more people into these two holes
faster.

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0024-horse-sale-state-enforcement]]
- [[0025-facility-booking-cta]]
- [[0002-booking-lifecycle-notifications-audit]]
</content>
