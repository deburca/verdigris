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

**[[shh-rider-journey-gap-analysis]] (high priority, now fully resolved)**:
walking both core journeys step by step surfaced two gaps more serious
than "hard to find" — the horse product page never checked `sale_state`
before allowing a purchase and nothing marked a horse `sold` after one
completed ([[0024-horse-sale-state-enforcement]], done), and the facility
page has no link to its own booking form at all
([[0025-facility-booking-cta]], done).

[[0024-horse-sale-state-enforcement]] is done: new custom module
`web/modules/custom/shh_horse_sale_state` closes both critical gaps from
the journey analysis. A `HorseAvailabilityChecker` service tagged
`commerce_order.availability_checker` — Commerce's own pluggable
extension point, already wired onto every order item's `purchased_entity`
field validation — blocks add-to-cart/checkout for a non-`available`
horse with **no form-alter needed for the actual security boundary**; a
`HorseSaleCompletionSubscriber` (same `commerce_order.place.pre_transition`
pattern as 0001's deposit subscriber) marks a horse `sold` the moment its
order is placed. Deliberately accepted a narrower race window (checked at
each validation point) rather than building a cart-add-time hold like
0012's — judged not worth the complexity for a comparatively slow,
low-volume horse-purchase checkout, and explicitly allowed by this task's
own acceptance criteria. Verified over real HTTP with two independent
anonymous sessions: a genuinely-sold horse is rejected by a **forged
direct POST** (not just a hidden button), and completing a real purchase
end to end automatically flips `sale_state` to `sold` with no staff step.
Also investigated, then ruled out, a suspected twin issue in
`shh_horse_deposit`'s `PayDepositForm` (same direct-save-not-through-
`ContentEntityForm` pattern) — confirmed via a logging probe that Drupal
core's Form API already refuses to process input for any `#access =
FALSE` element, so that form's existing button-hide is already a real
server-side block, not just a UI nicety. No code change was needed there;
see 0024's Resolution for the detail, so it isn't re-investigated as a
false lead later.

[[0025-facility-booking-cta]] is also done: new custom module
`web/modules/custom/shh_facility_booking_cta` adds a "Book now" link on
every Bookable Facility node, pointing at bee's own
`bee.node.add_reservation` route. **A more fundamental blocker was found
while verifying this task**: no role on this site — not `anonymous`, not
`authenticated` — actually had the `create bee reservation` permission
that gates that route, meaning every previous "verified over real HTTP"
booking test in this project's history must have run as an admin/uid-1
session (which bypasses permission checks); a rider clicking the new link
would have hit Access Denied. Fixed in the same module's `hook_install()`
by granting that permission to `authenticated` (not `anonymous`, matching
[[0017-anonymous-vs-authenticated-booking-access]]'s decision exactly).
Verified over real HTTP as a genuine non-admin test account: logged in,
followed the new link from `/oval-track`, and completed a real booking
through to a placed order — watchdog confirms `shh_booking_hold` (0012)
correctly placed and promoted the on-hold event, so the link connects to
the fully working booking pipeline, not just a form that renders.

[[0003-rider-membership-eligibility-workflow]] is also done: new custom
module `web/modules/custom/shh_rider_membership` closes the gap 0025 just
surfaced (`create bee reservation` access open to any authenticated
account with no eligibility gate at all). Confirmed the task's own framing
was wrong before building anything — there was no existing "vague
eligibility field" anywhere on the site to extend, so this is a from-scratch
build: a plain-base-fields `Membership` entity (pending/active/expired/revoked,
same pattern as 0018's `FacilityCredit`), a `shh_rider_waiver` webform
created programmatically in `hook_install()`, an auto-created pending
membership on submission, staff approval via a simple status-dropdown
form that auto-computes the expiry date, a cron sweep that auto-expires
stale memberships, and the actual hard block via a `#validate` handler on
bee's `AddReservationForm` (the same technique 0016's
`shh_facility_slots` already uses on this exact form — bee's form is a
plain `FormBase`, so 0024's `commerce_order.availability_checker` service
pattern doesn't apply here). A real bug was found and fixed along the
way: an empty `datetime_timestamp` widget doesn't submit NULL, so making
`approved`/`expires` staff-editable form fields silently defeated the
"only auto-set if empty" approval logic — fixed by making them view-only,
genuinely system-computed. Verified end to end over real HTTP with a
non-admin test account through every path: blocked with no membership,
pending, expired (cron-flipped, renew link shown), revoked (contact-us
message, deliberately **no** resubmit link), and successfully booking
immediately after staff approval.

The entire discovery-page backlog ([[shh-customer-facing-pages]]) is now
done too — [[0019-horse-catalog-page]] through
[[0023-pricing-comparison-page]], implemented and verified together in
one session:

- **[[0019-horse-catalog-page]]**: new `shh_horse_catalog` module,
  `/horses` lists every `available` horse as a `hestehoj:card`.
  Surfaced a bigger, previously-invisible gap while verifying "linked
  from primary navigation": **this site had no rendered navigation
  anywhere at all** (checked verdigris too, same gap platform-wide) —
  the `main` menu had one link and no block ever displayed it, despite
  the theme already shipping a fully-styled `menu--main.html.twig`.
  Fixed with a new `shh_main_navigation` module placing a standard menu
  block in the `header` region (scoped to hestehoj).
- **[[0020-facilities-overview-page]]**: new `shh_facilities_overview`
  module, `/facilities` lists all three with a bundle-discount/
  credit-pack explainer. Extracted `BuyCreditPackForm`'s price
  computation into a shared `FacilityPricingHelper` service (reused by
  0023). **Found and fixed a critical, live, currently-active bug**
  while building this: `field_price_frequency` had drifted to `hour` on
  all three facilities (should be `minute` per 0016's own docs), which
  makes `bee.module`'s own pricing silently compute **0.00 DKK** for any
  non-whole-hour booking — confirmed real order items from this
  session's own earlier testing had charged 0 DKK. Fixed the data;
  verified a fresh booking immediately charged the correct 50 DKK.
- **[[0021-public-availability-calendar]]**: corrected the task's own
  assumption — `/node/{node}/availability` is bee's **staff-only**
  management screen, not a public viewer; the real public calendar is
  already embedded on each facility page. New `shh_public_availability`
  module grants the missing `restful get bat_api_events_resource`
  permission to `anonymous`/`authenticated` per decision 0017. Verified
  on-hold slots show as unavailable via real HTTP (after tracing a
  confusing but ultimately harmless red herring in how the REST
  endpoint's `unit_types` parameter works).
- **[[0022-rider-dashboard]]**: new `shh_rider_dashboard` module,
  `/user/{user}/bookings` — upcoming/past bookings, active deposits,
  credit balances, each cancel/buy-more link gated by the *existing*
  cancel routes' own access checks. Verified with real deposit and
  credit-pack purchases end to end, plus a real 403 for a second
  account trying to view someone else's dashboard.
- **[[0023-pricing-comparison-page]]**: new `shh_pricing_comparison`
  module, `/pricing` — single-slot/pack/bundle side by side, sharing
  0020's `FacilityPricingHelper`. Every number verified against this
  task's own documented expected values exactly.

Considered whether `/oval-track`/`/product/{id}` (currently classic
field-formatter rendering) could move to Canvas/SDC like the new
discovery pages — researched Canvas's `ContentTemplate` mechanism, which
does exist for this, but is `node`-only (excludes `commerce_product`)
and has two unverified compatibility questions (whether the existing
CTA hooks and the availability calendar widget survive it). Deferred,
not decided — see [[0019-canvas-content-templates-for-structured-content]].

**[[shh-account-access-gap-analysis]] (new, high priority)**: walked
the site again as a real visitor now that discovery works. The core
booking/sales mechanics are all fine; what's missing is almost entirely
about getting a real visitor into an account in the first place. Two
critical findings: **`user.settings: register: admin_only` site-wide
(all three sites) means a brand-new rider cannot create an account at
all**, so "Book now" on any facility page leads straight to a login
wall with no way through — this is a business decision, not an
engineering one, tracked as
[[0026-rider-account-access-policy]]; and **no login link, no footer,
and no "Contact us" path exist anywhere on the site**, so a visitor
turned away at that wall has no way to even ask for help — tracked as
[[0027-site-footer-and-contact-link]] (small, no decision needed,
mirrors `shh_main_navigation`'s exact pattern). Two smaller, lower
priority findings also tracked:
[[0028-rider-dashboard-membership-status]] (the dashboard doesn't show
*why* a rider can't book) and
[[0029-cancel-flow-dashboard-redirect]] (cancel forms redirect to the
homepage, predating the dashboard). Also noted, not tracked as tasks:
a cosmetic breadcrumb inconsistency on the five new pages, and two
housekeeping items unrelated to shh logic (the sample horse catalog is
currently empty from earlier tasks' own real test purchases; a
pre-existing, unrelated data-integrity bug on the stock "Test Page"
node was found incidentally and is not linked from anywhere).

Next actionable step: [[0026-rider-account-access-policy]] first — it
blocks the most direct path to actually using the booking flow as a
real rider, and several other items ([[0027-site-footer-and-contact-link]]
in particular) are more useful once that's answered. Otherwise,
continue with whichever new priority you'd like next (e.g. revisiting
[[0005-tax-classification-horses-vs-bookings]]'s still-open VAT margin
scheme question, [[0009-vendor-fullcalendar-library]]'s CDN dependency,
or [[0019-canvas-content-templates-for-structured-content]]'s deferred
Canvas migration question).

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
- ~~Rider eligibility gate: route access check vs cart constraint vs
  both~~ — **resolved**: a `#validate` handler on bee's
  `AddReservationForm` (neither route access nor a cart constraint — see
  [[0003-rider-membership-eligibility-workflow]] for why)
- ~~Deposit/hold workflow for horse sales (separate from facility booking
  holds)~~ — **resolved**, see [[0001-horse-deposit-reservation-flow]]

## Related decisions
```dataview
TABLE site, status
FROM #cms2/decision
WHERE (!contains("decision", file.name)) AND (contains(string(site), "shh") OR contains(string(site), "shared"))
SORT file.name asc
```
