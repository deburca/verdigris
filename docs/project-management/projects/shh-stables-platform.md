---
type: project
tags: [cms2/project]
status: planning
site: shh
created: 2026-07-05
updated: 2026-07-05
target:
---
# Project: stutteri-hestehoj.dk — stables platform

## Goal
Build the shh site to support three activities: Icelandic horses listed for sale,
hourly reservation of riding areas, and hourly reservation of the riding hall —
each through its own Commerce-backed checkout (see
[[0018-separate-order-types-horse-vs-booking]]: horse sales and facility
bookings are deliberately separate checkout flows, not a unified one — a
combined single-checkout purchase was confirmed not to be a required scenario).

## Scope
- In scope: horse sales catalog (**Icelandic horses only**), hourly booking for riding areas + hall, separate cart/checkout per purchase kind, rider eligibility gating
- Out of scope: payment methods beyond what Commerce already supports platform-wide, multi-stable expansion, combining a horse purchase and a facility booking into one checkout

## Domain notes
- **Stutteri Hestehøj exclusively sells Icelandic horses** — no other breeds
  (no Danish Warmbloods, no Connemara ponies, etc.). Any sample/test content
  created for this site must be Icelandic horses. This isn't just cosmetic:
  Icelandic horses are the classic five-gaited breed, and whether a given
  horse has tölt and/or flying pace (skeið) — on top of the standard walk,
  trot, and canter/gallop — is one of the most important facts a buyer needs
  to see on the listing. **Done**, see [[0014-icelandic-horse-gaits-field]]:
  `field_gaits` added to the `horse` variation type, and the sample catalog
  now has a five-gaited and a four-gaited example so the distinction is
  visible, not just asserted.

## Entity / architecture model
See [[shh-stables-platform-model]] for the full entity model, ERD, and implementation notes.

## Current status (2026-07-05)
[[0010-enable-shh-commerce-bat-bee-modules]] and
[[0011-shh-entity-content-type-modeling]] are both done. Commerce/BAT/BEE (21
modules, including `commerce_payment` which 0010's original list missed) are
enabled, `hestehoj` is the default theme, a Commerce Store exists, and the
full entity model is built: product type `horse` (10 custom fields) and
content type `bookable_facility` (BEE-enabled, hourly, payment on, 6 custom
fields). Sample content exists and add-to-cart is verified working end to end
over real HTTP. Getting here required working around a recurring
stale-cache bug in this BAT/BEE RC release on nearly every multi-field
creation — see both tasks' "Resolution"/"Bugs hit and fixed" sections before
repeating any of this on another environment; the workaround
(`clearCachedFieldDefinitions()` immediately after every field storage save)
is now well understood and cheap to apply.

[[0012-cart-hold-concurrency-prototype]] is also done: a new custom module
(`web/modules/custom/shh_booking_hold`) places an on-hold BAT event at
cart-add time (BEE's default only reserves at checkout completion — a real,
now-confirmed double-booking window), promotes it to booked on checkout, and
releases it back to available when the cart/item is abandoned. Verified over
real HTTP with two independent sessions racing for the same slot: the second
is correctly rejected.

**[[0018-separate-order-types-horse-vs-booking]] (new decision, done):**
building 0012 surfaced that a single shared order type can't have both a
short hold TTL (right for an hourly slot) and a long one (right for a horse
purchase) at once — and separately, that mixing anonymous horse-cart
purchases with login-required booking items in one cart was never cleanly
resolved by [[0017-anonymous-vs-authenticated-booking-access]]. Confirmed
combined single-checkout purchases aren't a required scenario, so the
platform now uses two Commerce order types: `horse_sale` (new, own checkout
flow/number pattern/3-day cart expiration) and `default`/"Facility booking"
(existing, unchanged, 30-min expiration). This resolves the TTL tension
outright and eliminates [[0013-mixed-order-checkout-prototype]]'s original
premise — that task's scope has been revised accordingly (now just verifying
the two independent flows work) and is done.

[[0014-icelandic-horse-gaits-field]] is also done: `field_gaits` added to the
`horse` variation type, `field_breed` defaulted to "Icelandic Horse" (kept,
not removed), and the sample catalog corrected — the original sample product
turned out to be wrong in more than just breed (height, discipline, and
pedigree all referenced real Danish Warmblood specifics), all fixed. A second
sample (four-gaited, no flying pace) was added alongside the five-gaited one
so the field's purpose is demonstrable, not just present. No customer-facing
content on this site now misrepresents the breed.

[[0005-tax-classification-horses-vs-bookings]] is technically configured
(Commerce's EU VAT tax type enabled, store registered for DK, `horse` classed
`physical_goods` and `bee` classed `services`; verified via real add-to-cart:
a 45,000 DKK horse and a 150 DKK booking both correctly show a 25%-inclusive
VAT breakdown). Client confirmed 2026-07-06 the business is VAT-registered.
Still marked **blocked**, not done, on one remaining question: whether the
VAT *margin scheme* applies to any horse sales (depends on how each horse
was acquired — bred in-house vs. bought in) — needs a real answer, not
engineering work. See that task's Resolution for detail.

[[0015-implement-cancellation-refund-policy]] is also done: new custom
module `web/modules/custom/shh_cancellation_policy` — a `cancellation_policy`
config entity referenced by Bookable Facility, a self-service cancellation
route, and enforcement that checks the policy's refund window before
authorizing a refund and releasing the BAT event. Required fixing a
prerequisite gap first: neither order type actually had a payment step
configured (no gateway existed at all), so a "Manual" (bank transfer/cash)
gateway was set up too. Verified end to end with a real captured payment: a
booking outside the 24-hour window gets refunded and released; one inside
the window is denied outright, payment and slot both untouched. Also fixed
two small pre-existing bugs found along the way: a text format
(`basic_html`) used on the horse sample content since 0011 that doesn't
actually exist on this site, and a config schema naming mistake in the new
module itself.

[[0001-horse-deposit-reservation-flow]] is also done: new custom module
`web/modules/custom/shh_horse_deposit` — `sale_state` extended to all 5
values, a `horse_deposit` order item type priced at 20% of the horse's
price (config, not flat), and its own `deposit_refund_policy` entity
(deliberately distinct from 0015's slot-time-based `cancellation_policy` —
this one is "days since deposit paid"). The key design decision, confirmed
with the client first: cancelling a deposit **always** releases the horse
back to available regardless of refund eligibility — only whether the
money comes back depends on the window. That's the opposite of 0015's
booking-hold behavior, deliberately, since there's no "disincentivize
late cancellation" reason to keep a horse off-market. Verified end to end
with real HTTP + real captured payments, both inside and outside the
refund window.

Also renamed "Outdoor Arena 1" → **Oval Track**, added **Manège** and
**Lunge Ring** as new facilities, and implemented
[[0016-facility-fixed-length-slots]]: all three now restricted to fixed
30-minute booking slots, 08:00–20:00, priced 50/30/20 DKK respectively.
BEE already had most of the plumbing (opening-hours restriction, per-minute
pricing) — just needed data, plus one small new module
(`shh_facility_slots`) for the "exactly 30 minutes, slot-aligned" rule BEE
has no concept of. Also surfaced a real bug in `bee.module` itself: its
`hour`-frequency pricing silently computes 0.00 DKK for any non-whole-hour
duration (truncates via whole hours only) — not something introduced here,
but worth knowing before pricing any other sub-hour facility.

[[0017-facility-bundle-discount]] is also done: booking the Oval Track,
Manège, and Lunge Ring all for the exact same 30-minute slot now totals 80
DKK instead of 100. Two significant things came out of this:
1. **Fixed a real cart bug**: `bee.module`'s reservation form emptied the
   cart on every new booking, so a rider could never have more than one
   facility booked in the same order — patched (composer patch, registered
   in `composer.json`) so bookings accumulate normally.
2. **`commerce_promotion` cannot currently be enabled on this site** — hit a
   genuine, 100%-reproducible bug where installing its new `coupons` field
   on the `commerce_order` entity type triggers a cache-invalidation
   cascade into Canvas's eager Views-block-derivative rebuild, which fails
   because the field's table doesn't exist yet at that exact instant. Not
   fixable by retry/reordering — it's Drupal core's own
   "install schema, then invalidate cache" sequence colliding with Canvas's
   synchronous rebuild-on-any-cache-clear behaviour. Worth reporting
   upstream. Built the discount as a plain custom Commerce order processor
   instead — didn't actually need Promotion's admin UI/coupons for one
   fixed rule anyway.

[[0018-facility-credit-packs]] is also done: a rider can buy a 10-session
credit pack for one specific facility at 75% off (125 DKK instead of 500),
with no expiry, then redeem credits one at a time on future bookings
instead of paying. Deliberately not built on `commerce_promotion` —
confirmed this is a different concept entirely from a checkout discount
code (a persistent per-rider balance drawn down over months, not a one-time
coupon), so 0017's Canvas/Promotion blocker doesn't apply here. Built as
plain content entities using base fields only (not the Field API), which
side-stepped every Field API pitfall this session has otherwise hit
repeatedly — worth defaulting to this pattern for any future simple ledger-
style data on this platform.

**Customer-facing pages audit** ([[shh-customer-facing-pages]]): a rider
with no direct link currently cannot browse horses, browse facilities, see
availability up front, or see their own bookings/deposits/credits in one
place — everything built so far (0001, 0011, 0016–0018) is reachable only
via direct/individual links, no discovery path. Tracked as
[[0019-horse-catalog-page]], [[0020-facilities-overview-page]],
[[0021-public-availability-calendar]], [[0022-rider-dashboard]], and
[[0023-pricing-comparison-page]] (a side-by-side comparison of single-slot
vs. 10-pack vs. same-timeframe-bundle pricing per facility) — all backlog,
none started.

Next actionable step: [[0003-rider-membership-eligibility-workflow]] is
high-priority and still backlog, or the new page-discovery tasks above, or
continue with whichever backlog task you'd like to prioritize next.

## Tasks
```dataview
TABLE status, priority
FROM #cms2/task
WHERE contains(string(project), this.file.name)
SORT status asc, priority asc
```

## Open questions
- Cart-hold TTL value: **resolved at the order-type level** (30 min, the
  `default`/"Facility booking" order type's `cart_expiration`, decoupled from
  horse sales by [[0018-separate-order-types-horse-vs-booking]]) — **still
  open**: whether it should be configurable *per facility* rather than one
  platform-wide value. See [[0012-cart-hold-concurrency-prototype]]
- Rider eligibility gate: route access check vs cart constraint vs both — see
  [[0003-rider-membership-eligibility-workflow]]
- ~~Deposit/hold workflow for horse sales (separate from facility booking
  holds)~~ — **resolved**, see [[0001-horse-deposit-reservation-flow]]

## Related decisions
```dataview
TABLE site, status
FROM #cms2/decision
WHERE (!contains("decision", file.name)) AND (contains(string(site), "shh") OR contains(string(site), "shared"))
SORT file.name asc
```
