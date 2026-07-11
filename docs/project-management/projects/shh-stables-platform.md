---
type: project
tags: [cms2/project]
status: planning
site: shh
created: 2026-07-05
updated: 2026-07-11
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
- In scope: horse sales catalog (**Icelandic horses only**), hourly booking for riding areas + hall, separate cart/checkout per purchase kind, rider eligibility gating; **added by client request 2026-07-11**: commodity feed/bedding sales — straw and wrap ([[0038-straw-and-wrap-sale-items]]) with per-item image galleries ([[0039-product-images-featured-and-gallery]])
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

[[0005-tax-classification-horses-vs-bookings]] is now **done**
(closed 2026-07-07): Commerce's EU VAT tax type enabled, store
registered for DK, `horse` classed `physical_goods` and `bee` classed
`services`; verified via real add-to-cart with correct 25%-inclusive
VAT breakdowns. Client confirmed VAT-registered (2026-07-06) and
answered the margin-scheme question (2026-07-07): the vast majority
of horses are bred in-house, resale a rare exception — so the
standard configuration is correct. **One standing operational
caveat** recorded in the task: a horse bought in from a private
seller must NOT be listed for sale before the accountant confirms
margin-scheme (brugtmoms) treatment and, if it applies, the shop gets
custom margin-invoicing development (Commerce has no built-in
support).

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

[[0027-site-footer-and-contact-link]] is done: new custom module
`web/modules/custom/shh_site_footer`, a deliberate near-clone of
`shh_main_navigation` — a "Contact us" link added to the existing
`footer` menu and a `system_menu_block:footer` block placed in the
hestehoj `footer` region (the theme's `page.html.twig` and generic
menu template were already fully prepared; the region had simply
never had a block, same as `main` before 0019). Verified over real
HTTP, anonymous and as the non-admin `shh_test_rider` account, across
all five discovery pages plus node and webform pages. Two pre-existing
findings noted in the task, not fixed there: the footer's stock
"Privacy policy" link is invisible to visitors because its target
(node 1) ships **unpublished** in Drupal CMS (a content decision,
related to [[0006-gdpr-data-retention-policy]]); and `/user/login` —
the exact page a turned-away rider lands on — renders via `gin_login`
in the **admin theme**, so it can never show a hestehoj footer block;
the contact-path-at-the-login-wall gap should be folded into
[[0026-rider-account-access-policy]]'s implementation. The question of
eventually replacing both placeholder menu blocks (0019's header,
0027's footer) with the theme's slotted `navbar`/`footer` SDC
components — which needs both a rendering-mechanism decision (Canvas
page regions vs. custom code) and client content for the slots — is
now tracked as [[0032-adopt-footer-navbar-sdc-components]] (backlog,
low priority; natural companion to the 0030/0031 display migration).

[[0019-canvas-content-templates-for-structured-content]]'s deferred
Canvas migration question is now operationalized into two concrete
tasks: [[0030-canvas-content-template-bookable-facility]] (prototype
Canvas's `ContentTemplate` for `bookable_facility` — the two open
compatibility questions from the decision, then migrate if viable) and
[[0031-sdc-based-commerce-product-display]] (`commerce_product` can't
use `ContentTemplate` at all — hard-restricted to `node` upstream — so
this instead investigates a custom-code SDC display mirroring how the
discovery pages (0019–0023) already render components directly,
without needing Canvas's per-bundle support).

[[0026-rider-account-access-policy]] is **done** (client answered
2026-07-07: self-registration with mandatory admin approval): new
custom module `web/modules/custom/shh_rider_registration` sets
`register: visitors_admin_approval` on shh only and adds a
self-hiding "Log in / Register" link to the main navigation. The
waiver deliberately stays a separate post-registration step (two
staff checkpoints: account approval, then membership approval —
revisit only if that proves too heavy). **Found and patched a real
Drupal CMS platform bug** during verification: `drupal_cms_helper`'s
register-form alter leaks `notify=TRUE` into anonymous
self-registrations (Form API resolves `#access = FALSE` elements to
their `#default_value`), so every applicant got the wrong
"administrator created your account" email with a dead login link
and **the admin never got the pending-approval notification** —
latent in every Drupal CMS site that opens registration, invisible
under the shipped `admin_only` default; composer-patched per
decision 0006, worth reporting upstream. Verified end to end over
real HTTP as a genuinely never-seen account (`soren_holm`): nav link
→ register → correct pending emails → staff approve → real emailed
one-time login link → password → waiver-gated at booking → waiver →
membership approved via real staff form → books Oval Track → order 6
`completed`, 50 DKK, hold placed and promoted. The full
public-visitor→booked-rider journey now works from a cold start.
Post-review hardening (same day): `hook_uninstall()` restores
`admin_only` and removes the nav link (verified with a real
uninstall/reinstall cycle), and a `site.path` guard makes both hooks
an explicit no-op on non-shh sites; remaining review findings
tracked as [[0033-durable-config-strategy-shh]],
[[0034-guest-checkout-approval-policy-alignment]], and
[[0035-shh-install-hook-cleanup]].

[[0002-booking-lifecycle-notifications-audit]] is **done** (closed
2026-07-07): new custom module `web/modules/custom/shh_booking_log` —
an append-only `shh_booking_log` content entity (plain base fields,
the 0018/0003 pattern) written from bat_event insert/update/delete
hooks, so every booking path is covered by construction: 0012's
holds/promotes/releases, 0015's cancellations, cart-expiry cron, and
0016's orderless staff-created events. Actor recorded as
customer/staff/system; admin trail at `/admin/reports/booking-log`
(restricted permission); three rider emails (`booking_confirmed` —
deliberately in addition to the Commerce receipt, which carries no
slot date/time at all; `booking_cancelled`; `hold_expired` for
system-expired holds only, not self-removed cart items). All four
lifecycle scenarios verified over real HTTP as a non-admin rider
(plus admin for the staff-event case). Two real bugs found and fixed
during verification: the cart-add hold row misclassified the rider
as staff (the event→booking→order chain isn't saved yet at event
insert time — actor classification now falls back to bee's own
staff permission when no order resolves), and the confirmation email
cited the internal order id instead of the order number (Commerce
sets the number only on the in-flight order object during
pre_transition — `shh_booking_hold`'s promote subscriber moved to
`place.post_transition`, so that module is part of this task's diff
too).

[[0009-vendor-fullcalendar-library]] is **done** (closed 2026-07-07),
with a corrected premise: the FC3-era `fullcalendar_library` module is
disabled and unused (a metapackage-dependency leftover) — the real CDN
dependency was `bat_fullcalendar` (RC11) loading FullCalendar **v6**
from jsdelivr, the standard bundle *unpinned* and the premium Scheduler
bundle pinned but **unlicensed and user-reachable** (bee attached the
scheduler variant for hourly types, whose JS hardcodes premium
resourceTimeline toolbar buttons and never injects a
schedulerLicenseKey — while every configured view is the standard MIT
`dayGridMonth`). Resolved by vendoring `fullcalendar/fullcalendar`
6.1.21 via a composer repository entry (the Webform-assets pattern) and
two composer patches: bat's library now loads the local copy, and bee
attaches the standard variant instead of scheduler everywhere —
licensing question eliminated rather than licensed. Verified over real
HTTP: both calendar pages CDN-free, the library now ships inside the
local JS aggregate, anonymous events feed intact. **Important
operational find:** `patches.lock.json` was stale (composer-patches 2.x
applies only what's in that lock; a bare `composer reinstall` silently
stripped the 0017 bee cart patch) — now relocked covering all 8 patched
packages; after any patch change: `composer patches-relock` +
`composer patches-repatch`, never bare reinstall.

[[0028-rider-dashboard-membership-status]] is **done** (closed
2026-07-07): a "Membership" section at the top of the rider dashboard,
reusing `shh_rider_membership`'s own eligibility API verbatim (message
from `getEligibilityMessage()`, the booking form's exact waiver-button
component, gated by `canSelfServiceResubmit()` so a revoked rider gets
the contact-us message with no self-service route around staff).
Verified over real HTTP in all five membership states with real test
accounts — including expiry via the real cron sweep — and uid 3's
membership restored to active afterwards.

[[0034-guest-checkout-approval-policy-alignment]] is **done** (closed
2026-07-07): approval is universal (derived from 0026's decision).
Investigation found THREE Commerce paths that create active accounts
without consulting `user.settings.register` (the guest_new_account
flow setting plus both the Login and CompletionRegister panes — the
panes even log the new user in). Enforced at the shared chokepoint
instead of hiding checkboxes: `shh_rider_registration` gained a
runtime `hook_user_presave()` guard (blocks any web-created active
account while the admin-approval policy is on; CLI and
`administer users` sessions exempt) + pending-approval notifications +
a `hook_user_login()` backstop ending the panes' automatic sessions.
Verified with three real anonymous horse purchases (each misconfig
enabled then restored): accounts come out blocked and unable to log
in, orders complete and assign normally, and the untouched default
path still creates no account at all.

[[0033-durable-config-strategy-shh]] is **done** (closed 2026-07-08),
and it changed the platform's config regime: **config export is now
the source of truth for shh** — see the new decision
[[0020-shh-config-export-strategy]]. The task's trap was reproduced
live first (cex → flip `register:` in the store → cim → 0026's
registration policy silently reverted), then the strategy went
through one deliberate reversal: an import-blocking guard module was
built to protect the imperative pattern, before client review
surfaced the deciding fact that **shh is headed to production for
external users** — so the guard was discarded (never committed) in
favor of a deployable pipeline. `config_sync_directory` now points
at git-tracked `config/shh/sync` (852-item baseline; the old
location under `sites/shh/files/` is gitignored, which is what made
the trap silent), with `config_split` 2.0.2 carrying `field_ui` +
`views_ui` in a `local` split (`config/shh/local`) that DDEV
activates via a `$config` override — a production import uninstalls
both by construction. Verified: cex→cim roundtrip is a no-op, the
tamper scenario is now predictable in both directions and
git-visible, and the site serves normally afterwards. **Every future
task inherits a new workflow rule: after any config-affecting
change, `cex` and commit the config diff in the same commit as the
code** — a stale export is the new failure mode. Note settings.php
is gitignored: the required block (sync path + split activation) is
recorded in the decision entry.

[[0035-shh-install-hook-cleanup]] is **done** (closed 2026-07-08),
in full: new `shh_common` module with
`shh_common_ensure_menu_link()` replacing the four drifted copies of the
create-unless-present menu-link block (drift resolved: keyed by
menu + URI, existing links never overwritten, `enabled` explicit;
verified through a real uninstall/reinstall cycle on both branches);
the drupal_cms_helper patch comment trimmed 14 → 4 lines — where
**`composer patches-repatch` was caught reporting "Patching …" while
silently leaving the package pristine** on a malformed hunk header,
so after any relock/repatch, grep the patched file, don't trust the
exit code; and `shh_rider_registration`'s docblock/info.yml cut to
code-facts with task 0026 owning the policy narrative. The upstream
drupal_cms_helper bug report (drafted under this task) was **filed by
Paddy 2026-07-08 as Drupal CMS issue #3591417**
(https://git.drupalcode.org/project/drupal_cms/-/work_items/3591417 —
note the Drupal CMS queue lives on GitLab work items, so
drupal.org/i/3591417 resolves to an unrelated node); the task's
original 3481627 reference turned out to be the core admin-create UX
issue the alter mimics (context, not an existing report of this bug).
Our composer patch retires when #3591417 (or core 3481627 removing
the alter) lands — check before any drupal_cms_helper version bump.

[[0031-sdc-based-commerce-product-display]] and
[[0032-adopt-footer-navbar-sdc-components]] are both **done** (closed
2026-07-08, same session) — the SDC display work advances without the
Canvas ContentTemplate track, see [[0019-canvas-content-templates-for-structured-content]]'s
Outcome section. Re-checked `drupal.org/i/3498525` first: still an
open [META], so `ContentTemplate` stays `node`-only and can't touch
`commerce_product` — as anticipated. **0031**: new
`shh_horse_product_display` module renders `/product/{id}` horse pages
with `hestehoj:heading`/`badge`/`text`/`image` via a
`hook_ENTITY_TYPE_view_alter()` that runs *after* everything else, so
the add-to-cart form, 0024's sale-state enforcement, and the 0001
deposit CTA all survive untouched (verified: real anonymous
add-to-cart on an available horse creates a `horse_sale` order; forged
POST on the sold horse is rejected with 0024's message and zero order
items). `HorseCatalogController`'s gait-label and image-media helpers
were factored into `shh_common` (and 0035's helper renamed to the
prefixed `shh_common_ensure_menu_link` so phpcs is clean). **0032**:
the header and footer now render through the theme's slotted
`hestehoj:navbar`/`hestehoj:footer` SDCs via block plugins
(`shh_navbar`/`shh_footer`) — **custom code, not Canvas page
regions**, keeping one SDC-composition approach site-wide. Per the
client's slot direction: site-name wordmark branding, navbar shows the
access-filtered main menu (CTAs deliberately live in page content, not
furniture), footer carries address/email read live from the Commerce
store + a placeholder `social` menu + the footer menu + a computed
copyright line. Verified anonymous and as a non-admin rider (the "Log
in / Register" link correctly hides when authenticated). **Two open
client items**, both non-blocking: real social profile URLs (Facebook/
Instagram are placeholders, editable as content) and an eventual
leaflet map embed for the address.

[[0036-staff-release-deposit-reservation]] is **done** (closed
2026-07-08, raised and implemented the same day): testing surfaced
that nothing could free a deposit-reserved horse unless a placed order
existed and its owner (or an admin navigating the customer's own order
item) used the self-service cancel — an unpaid/orphaned
`reserved-deposit` state was simply stuck (sample horse 1 was in
exactly that state, an artifact of 0031's testing). New
staff-gated action in `shh_horse_deposit`:
`DepositManager::releaseReservation()` + a confirm form at
`/product/{id}/release-reservation` (new restricted permission
`release horse deposit reservations`, assigned to no role yet) + a
staff-only button on the product page when a horse is
`reserved-deposit`. Release is unconditional; refund runs only through
a placed order's normal 0001 policy rules (an unpaid deposit refunds
nothing). Verified over real HTTP through all three cases (orphaned →
direct release; placed+paid → released and refunded, payment balance
0; placed+unpaid → released, unrefunded). Horse 1 ends `available`
again — the catalog has a listing once more. The deliberately-excluded
counterpart, un-selling a **sold** horse (different money questions:
full purchase price, per-case staff refund decisions — relist and
refund should decouple), is recorded as [[0037-unsell-sold-horse]]
(backlog, low).

[[0007-availability-calendar-rate-limiting]] is **done** (closed
2026-07-08): the public `/bat_api/rest/calendar-events` endpoint
(anonymous per decision 0017, opened by 0021, uncacheable per unique
URL and doing real availability calculation per request) is now
flood-limited from within `shh_public_availability` — 60 requests/60 s
by default (config-overridable), keyed per user id when authenticated
and per IP when anonymous, 429 + `Retry-After` when exceeded, with a
new no-role restricted permission `bypass availability calendar rate
limiting` as the staff escape hatch. Anonymous `page_cache` still
absorbs repeated identical URLs before the limiter (correct: cache
hits are cheap; a scraper's unique query strings always miss and get
counted), and the 429 itself is uncacheable by construction. The PII
criterion was verified against a full-year anonymous pull: only unit
names / "N/A" titles plus times, colors and state flags — booked
slots are indistinguishable from staff-blocked ones, and no rider
identifier appears. Verified over real HTTP: anonymous tripped at
exactly 60, a logged-in rider had (and tripped) an independent
bucket, admin bypassed 63-for-63. No config changed.

[[0030-canvas-content-template-bookable-facility]] is **done**
(closed 2026-07-08) — investigated for real, decided **not to
migrate**, which also flips decision 0019 from deferred to
**accepted**. A real enabled
`canvas.content_template.node.bookable_facility.full` was created on
dev, rendered over HTTP, and deleted. Empirical answers to 0019's two
open questions: the facility CTA `hook_ENTITY_TYPE_view()` hooks
**survive** (both buttons rendered — the Canvas view builder bypasses
field formatters, not entity-view hooks), and prop bindings work
(title, `field_surface`); but the availability calendar **cannot be
expressed** in a template — the template replaces the whole view
display so the `entity_reference_entity_view` formatter (the
FullCalendar embed) never runs, and Canvas 1.7.1 has no prop source,
adapter, or component source that renders formatter output (same gap:
`office_hours_table`, label formatters). A hybrid was rejected
(template for scalars + hook-injected custom code for the rest =
both paradigms on one page). Bonus interop bug found: Drupal CMS's
`content_template_disable_preview` ECA rule errors on **every**
template save for this bundle because bee ships no config schema for
its node-type `third_party_settings` — and its `preview_mode` write
lands despite the reported validation failure (restored). Candidate
upstream issue against bee. Custom-code SDC composition stays the
single site-wide approach; the Canvas track is closed unless
drupal.org/i/3498525 lands *and* formatter output becomes expressible.

[[0037-unsell-sold-horse]] is **done** (closed 2026-07-08): the
missing reverse of 0024's automatic `available → sold` flip. New in
`shh_horse_sale_state` (set and un-set now live in the same module):
`RelistManager` + a staff confirm form at `/product/{id}/relist`
(new restricted permission `relist sold horses`, assigned to no
role) + a staff-only product-page button on sold horses. Scope
decisions recorded in the task: a dedicated `sold → available`
action rather than a general sale-state switcher (a generic surface
would bypass per-transition bookkeeping — deposit refund rules,
originating-sale identification); its own permission rather than
sharing 0036's (deposit release and sale undo carry different money
exposure); watchdog + Commerce's own permanent order/payment records
rather than a 0002-style log entity (rejected as disproportionate
for one staff action). Relisting is fully decoupled from refunding —
the form identifies the sale being undone (order link, payments,
payments-tab link) and touches no money. Verified over real HTTP
through the full cycle: real checkout as test_rider flipped horse 1
`sold` (order 43); while sold, the relist route was 403 for
non-staff, forged purchases stayed rejected (even after staff viewed
the form); the real confirm POST relisted it; a second real checkout
(order 44) went through and re-flipped it `sold`, with the form then
pointing at order 44, not 43. Test orders deleted, horse 1 ends
`available`. The `reserved`/`withdrawn` gap (no transitions, manual
field edits only) is now recorded in 0001's workflow notes.

**New client requirement (2026-07-11)**: SHH also sells baled straw
and **wrap** (silage-like feed wrapped in multiple layers of plastic)
— tracked as [[0038-straw-and-wrap-sale-items]] and
[[0039-product-images-featured-and-gallery]] (staff image uploads;
one featured image on list cards, ALL images on the individual item
page — for horses and the new items alike; the horse half is pure
display work, since `field_media` already stores unlimited media but
every consumer renders only the first image).

[[0038-straw-and-wrap-sale-items]] is **done** (closed 2026-07-11,
same day): one shared `feed` bundle family (product type "Feed &
bedding" → variation type → order item type; straw and wrap are two
*products*, `multipleVariations` on for later unit sizes), riding
the existing `horse_sale` order type **relabelled "Product sale"** —
0018's split drivers (cart TTL, login policy) match the horse side
exactly, so a third order type would just be a third parallel cart.
The feed variation type deliberately has no `field_sale_state`,
which is precisely what keeps 0024's availability checker away
(verified both directions: a feed purchase produced zero horse
machinery activity, and a sold horse still rejects a forged POST
while feed sells normally). The feed add-to-cart form display shows
**quantity** and hides `unit_price` — deliberately not copying the
horse form's unit-price-override leak (that follow-up stays open).
New `shh_feed_catalog` module: `/feed` catalog page, main-nav link
between Horses and Book a facility. The client answered most open
items the same day: **variations are per harvest year** (built as a
real Commerce `year` attribute on the feed variation type, so the
add-to-cart form grew a year selector for free), confirmed
VAT-inclusive prices — straw 2025 at 250 DKK, wrap 2025 at 350,
wrap 2026 at 300 — and **pickup only** (delivery is never offered
online, both product bodies say so). ⚠ Standing operational
reminder: **wrap 2026 (variation 8) rises to 350 DKK during 2027**
— a manual price edit, deliberately not automated. Catalog cards
show the cheapest variation price ("From 300,00 DKK"). Verified
over real HTTP as test_rider twice: straw ×3 + wrap ×1 → 495,00 DKK
/ 99,00 VAT → order `HS-9`; then post-answers, wrap **2026** ×2 +
straw ×1 → 850,00 DKK / 170,00 VAT → order `HS-10`, the attribute
POST resolving the right year variation — both with pending manual
payments, both test orders deleted. Still open: stock tracking
(published = listed today) and per-bale unit confirmation.

[[0039-product-images-featured-and-gallery]] is **done** (closed
2026-07-11, same day): featured image = **first image item in field
delta order** (staff drag it first in the widget; a leading video no
longer hides a later image), and every item page now shows **all**
its images as a grid of 1:1 `hestehoj:image` tiles — no new theme
SDC, no lightbox (revisit on client ask), custom-code SDC
composition unchanged. The helper family lives in `shh_common`
(`…_props_all`, `…_props_from_media`, `…_image_gallery`); horse
pages get a "More photos" section (hero keeps the featured image, a
single-image horse gets no gallery furniture), feed pages get a
"Photos" section via the sibling-hook mechanism showing the **union
of images across the year variations** (photos belong to the
product, not the harvest year). Two notable finds: the site had
**zero media entities** — every image code path since 0031 had
never run against real content (canvas's `src_with_alternate_widths`
worked on first exercise); and the horse variation form's media
widget was a plain autocomplete with **no upload path at all** —
both variation forms now use the `media_library` widget, proven
over real HTTP with a genuine multipart upload through
`/media/add/image` (the widget's selection modal is AJAX-only core
UI; upload and reference were each proven separately). Verified
anonymous across `/horses`, `/feed` and all four product pages
(including Þór as the single-image + sold case, with 0024's notice
and the add-to-cart/deposit-CTA survival on Freja's). Eight
GD-generated placeholder photos seed the catalog until the client
sends real ones.

[[0040-facility-images-featured-and-gallery]] is **done** (closed
2026-07-11, requested and implemented the same day as 0039): the
riding facilities now carry images in the 0039 model — a new
multi-value `field.storage.node.field_media` (deliberately not
Drupal CMS's cardinality-1 `field_featured_image`, which can't
model "featured = first of many"), `media_library` widget on the
`bookable_facility` node form, featured image on the `/facilities`
cards, and the shared "Photos" gallery on the facility pages via a
`hook_node_view()` sibling hook in `shh_facilities_overview` (the
booking CTA and FullCalendar availability embed verified intact
alongside). Zero new image plumbing — 0039's `shh_common` helpers
took a node bundle unchanged. Sample content: Oval Track ×2,
Manège ×1, and Lunge Ring deliberately imageless so the
zero-image degrade case stays live. Verified anonymous over real
HTTP on all four pages plus the staff node form as admin.
Same-day client review caught a real display bug — fixed under
0040: **`hestehoj:image` is full-bleed by design** (its `cq-full`
utility breaks out of any wrapper to span the full content width),
so the horse hero and every gallery tile overflowed their grid
cells; latent since 0031 and invisible until this day's first-ever
real media. Fixed with a new `bleed` prop on the component
(`full` default preserves every existing usage; `contained` = no
breakout class, so the committed CSS build needed no rebuild — NB
this machine's theme toolchain is stale, tailwind 4.1.18 vs the
committed build's 4.2.1; don't rebuild `build/main.min.css` without
a proper dependency install first). The client's lightbox question
is recorded as [[0041-gallery-lightbox]] (assessment: worthwhile,
modest scope — native `<dialog>`, theme JS component pattern, no
contrib/CDN), backlog until approved. The same review round also
caught that **product pages have had no title at all since 0031**
— fixed as [[0042-restore-product-page-title]]: commerce products'
page H1 comes from core rendering the display's own `title`
element (`EntityViewController::buildTitle()`), the exact element
0031 hid as a supposed duplicate, blanking H1 and head `<title>`
alike; restored, plus a theme
`field--commerce-product--title.html.twig` (the
`field--node--title` pattern) so the title renders inline at
heading size instead of inside the generic field template's
body-text div.

[[0041-gallery-lightbox]] is **done** (closed 2026-07-11, client
approved the recommendation the same day): a new `hestehoj:gallery`
SDC owns the tile grid plus a native-`<dialog>` lightbox —
prev/next with wrap-around, arrow keys, backdrop-click close,
Escape/focus via `showModal()`; `gallery.js` follows the theme's
`ComponentInstance` pattern, `gallery.css` is deliberately plain
CSS (rebuild-independent — the Tailwind toolchain note from 0040),
and without JS every tile is a plain link to the original file.
`shh_common`'s gallery builder now takes media entities and renders
the component; two dead utility classes from 0039 fixed along the
way (`sm:grid-cols-3` was never compiled — the grid had silently
been 2-col everywhere). Verified over real HTTP on all five
image-bearing pages and in a real headless Chromium (JS init marker
in the live DOM + full-page screenshot); the click→dialog step
itself is native browser API, one human click-through welcome.

**[[0043-bee-price-frequency-form-reset]] (new, HIGH)**: the
client's live test edit of Lunge Ring during 0041 verification
exposed that **bee's node-form alter hardcodes
`field_price_frequency` to `hour` over the stored value**
(bee.module:303) — so *any* staff save of *any* facility form
silently reinstates 0020's 0,00-DKK pricing bug (and that's how
0020's original "drift" happened, mystery finally solved). Lunge
Ring's data re-fixed immediately; **do not save facility node
forms until the bee patch lands**. Lunge Ring also now carries a
Freja photo from that same test edit — a content decision left to
the client (it also means the zero-image degrade case is no longer
live on the site, only verified).

Next actionable step: [[0043-bee-price-frequency-form-reset]]
(high — every staff facility edit is a pricing landmine until
patched); then the remaining backlog is medium/low:
[[0004-staff-admin-booking-calendar]] and the client-input-gated
[[0006-gdpr-data-retention-policy]]. Client items outstanding: real
photos for products (0039) and facilities (0040), 0038's
stock-tracking and per-bale-unit answers, the wrap-2026 price rise
during 2027, and the real social URLs from 0032. Also worth a
small follow-up: the
horse add-to-cart form exposes "Override the unit price" + a currency
selector to anonymous visitors (pre-existing `commerce_order_item.horse`
form-display config, surfaced during 0031, deliberately left untouched
there), and the client still owes the real social URLs for 0032.

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
