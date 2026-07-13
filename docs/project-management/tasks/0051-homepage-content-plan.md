---
type: task
tags: [cms2/task]
status: in-progress
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-13
updated: 2026-07-13
---
# Task: Homepage content plan — build out the sections

## Description
With the platform functionally complete (all 50 prior tasks done), the
work turns to **content**. The homepage today is Canvas page 1
("Home"): a hero billboard plus three links (Horses, Feed & Bedding,
Oval track/Manege). It states what the stable *has*; it does not yet
orient a first-time visitor, sell a horse, or explain how to become a
rider.

This task holds the agreed structure and tracks the sections as they
are built, one at a time.

## Where content goes

The homepage is a **Canvas page** (`canvas_page` 1), composed from the
`hestehoj` SDC component library — so most sections are **content, not
code**: `hero-billboard`, `hero-side-by-side`, `card`, `card-icon`,
`card-pricing`, `card-testimonial`, `cta`, `accordion(-container)`,
`section`, `group`, `image`, `gallery`, `blockquote`, `badge`,
`button`, `text`, `heading`.

Exceptions needing code are flagged **[code]** below — chiefly anything
that must pull live data (e.g. featured horses from Commerce), which a
static Canvas section cannot do.

## The plan (agreed 2026-07-13)

### Band A — Orientation

| # | Section | Notes | Status |
|---|---|---|---|
| 1 | **Hero** (sharpened) | One promise, one action — and the page's first `h1`. | **done** 2026-07-13 |
| 2 | **What we offer — three-way split** ⭐ | The site serves three distinct audiences who must self-select at once: **Buy a horse** → `/horses`, **Book a facility** → `/facilities`, **Buy feed & bedding** → `/feed`. Three `card-icon`s. | **done** 2026-07-13 |

### Band B — The offers (teasers, not full pages)

| # | Section | Notes | Status |
|---|---|---|---|
| 3 | **Horses for sale** ⭐ **[code]** | Live featured horses + "See all". | **done** 2026-07-13 |
| 4 | **The facilities** | Live cards + booking-hours line. | **done** 2026-07-13 |
| 5 | **Feed & bedding** | Live teaser, per-bale prices, availability caveat. | **done** 2026-07-13 |

### Band C — Trust

| # | Section | Notes | Status |
|---|---|---|---|
| 6 | **About the stud** | The story, breeding philosophy, how long, the mares/stallions behind the herd. What a buyer actually reads before spending 45.000 DKK. **Client content needed.** | todo |
| 7 | **Why Icelandic horses / the gaits** | Tölt and flying pace; four- vs five-gaited. Makes the per-horse `field_gaits` badges (task 0014) meaningful to a novice buyer, and quietly signals expertise. | todo |
| 8 | **Testimonials** | One buyer, one rider (`card-testimonial`). Two is plenty. **Client content needed.** | todo |

### Band D — Practicalities

| # | Section | Notes | Status |
|---|---|---|---|
| 9 | **How booking works** ⭐ | Riders hit a real wall: register → **wait for staff approval** → sign the waiver → **wait for membership approval** → book. Two human checkpoints (tasks 0026, 0003). Unexplained, people assume the site is broken. A 3–4 step `card-icon` row fixes it. | todo |
| 10 | **Pricing at a glance** | Single slot vs 10-session pack vs multi-facility bundle → `/pricing`. `card-pricing` exists for exactly this. | todo |
| 11 | **Where we are & visiting** | Address, map, opening hours, and how to arrange a horse viewing (see the gap below). Address/email already render in the footer live from the Commerce store (task 0032); a leaflet map is a recorded 0032 enhancement. | todo |
| 12 | **FAQ** (`accordion`) | Deposits and refund windows, VAT, transport, can I try a horse, what to bring. | todo |
| 13 | **Closing CTA** | "Come and see us" → contact. | todo |

**Deliberately deferred**: a News/blog section. A homepage carrying
three-year-old "latest news" is worse than none — revisit only if
someone will own it.

## Section 5 — done (2026-07-13) — Band B complete

**New block plugin `shh_featured_feed`** (in `shh_feed_catalog`): both
products with photo, teaser and **per-bale price** (cheapest published
year, prefixed "From" when the years differ), under an intro carrying
the two facts a buyer needs up front — *collected at the stable*, and
*availability is confirmed when we contact you* (task 0038's
over-promise caveat, now on the homepage rather than only the product
pages).

**Live data matters most here.** Task 0038's stock decision is that
**publish/unpublish is the only availability lever** — applied per
product *or per harvest-year variation*. A hardcoded teaser would keep
advertising a year of wrap that staff unpublished the day it ran out.
**Verified exactly that**: unpublishing wrap 2026 flipped the homepage
from *"From 300,00 DKK per bale"* to *"350,00 DKK per bale"* (only 2025
left, so no "From") — immediately, no cache clear. Both list cache tags
are set, per section 3's lesson.

Query and card shared via a new `FeedCardBuilder`, used by the block and
`/feed` alike — the third and last of the pattern
(`HorseCardBuilder`, `FacilityCardBuilder`, `FeedCardBuilder`).

**Band B is complete**: the homepage now reads
hero → What we offer → Horses for sale → Ride with us → Feed & bedding.
Next is Band C (trust), which **needs client content**: the stud's story
(section 6) and testimonials (section 8). Section 7 (the gaits
explainer) can be written without them.

## Section 4 — done (2026-07-13)

**New block plugin `shh_featured_facilities`** (in
`shh_facilities_overview`), rendering all three facilities with photo,
kind, indoor/outdoor, capacity and **live slot price**, under the
heading *"Ride with us"* and an intro line: *"Book by the half hour,
from 08:00 to 20:00. Ride one, or book several for the same slot and
pay less."*

**Live data, not static copy** — the same judgement as section 3, and
the history backs it: the facilities have been **renamed** before
("Outdoor Arena 1" → "Oval Track"), their photos only arrived in task
0040, and their prices are computed from config — the very numbers that
silently drifted in task 0020 and made bookings cost 0,00 DKK.
Hardcoded homepage copy would have drifted from all three.

**Query and card shared, not duplicated**: both were factored out of
`FacilitiesOverviewController` into a new `FacilityCardBuilder` service,
now used by the homepage block *and* `/facilities` — the same pattern as
`HorseCardBuilder`. The controller's own `buildCard()` is deleted, not
left as a second copy.

**Section order corrected.** Section 3 had been inserted *above* "What
we offer", contradicting the agreed plan (Band A orientation first, then
Band B offers). The page now reads, top to bottom:

> hero → What we offer → Horses for sale → Ride with us

## Section 3 — done (2026-07-13)

**New block plugin `shh_featured_horses`** (in `shh_horse_catalog`),
placed on the Canvas homepage directly under the hero — the stud's money
content, above the three-way split. Canvas derives a component from any
block plugin, so code and Canvas content compose cleanly.

**The query and the card are shared, not duplicated.** Both were factored
out of `HorseCatalogController` into a new `HorseCardBuilder` service,
now used by the homepage block *and* `/horses`. One definition of "a
horse that is for sale" — `field_sale_state: available` on a published
variation, the same rule 0024 enforces at add-to-cart — so the two
surfaces can never drift apart and the catalog can never advertise a
horse the checkout would refuse.

**Renders nothing when nothing is for sale**, so the homepage loses the
section rather than showing an empty shelf. And the grid **fits the
number of horses** (1 → a single centred card; 2 → a centred pair; 3+ →
the three-column grid): a stud often has one or two, and a lone card
stranded at the left of a three-column grid reads as "something failed to
load" rather than "we have one lovely mare".

**Two bugs caught while verifying**, both worth knowing:

1. **A stale-cache bug that defeated the entire point of the section.**
   The block tagged `commerce_product_list` — but `field_sale_state`,
   the field that decides whether a horse appears at all, lives on the
   **variation**, and saving a variation does **not** invalidate the
   *product* list tag. Relisting a sold horse changed nothing on the
   page. **A horse could stay advertised on the homepage after it sold**
   until some unrelated cache clear. Fixed by tagging
   `commerce_product_variation_list` as well — in the block *and* in the
   `/horses` catalog, which had inherited the same flaw. Verified: sale
   state now flips both surfaces immediately, both ways.
2. **A contrast regression**: the section's `muted` background rendered
   the heading and card text in the muted foreground, visibly washing
   them out beside the crisp section below — the sort of thing the
   theme's WCAG work (hestehoj task 0005) exists to prevent. Dropped.

Also re-learned the Tailwind lesson from the footer sparkle: `max-w-sm`
was not in the compiled CSS because nothing had ever used it. A theme
rebuild generated it.

## Section 1 — done (2026-07-13)

Three changes to the existing hero (`cta` component):

1. **It is now the page's `h1`.** The homepage previously had **no `h1`
   at all** — the hero was an `h2` — which is both an SEO defect and a
   screen-reader one (no top-level landmark for the page). Heading level
   raised to 1; section 2's heading sits below it as an `h2`, so the
   document outline is now correct.
2. **The headline is a promise, not a label.** It said *"Stutteri
   Hestehøj"* — which the navbar wordmark already says two inches above
   it, and which tells a visitor nothing. Now: **"Icelandic horses, bred
   in Holbæk"**, with the client's own evocative line kept as the
   supporting text (*"Five-gaited and four-gaited horses from our own
   breeding — and a stable you can ride at. Oval track, manège and lunge
   ring. Fields with a view."*).
3. **One action, not three.** The three hero buttons duplicated the
   "What we offer" split now sitting directly beneath them. A hero
   should ask for one thing: **"See horses for sale"** → `/horses`, the
   stud's primary conversion. Facilities and feed are one scroll away in
   section 2.

Copy is the client's voice — expect them to tweak the wording; the
structure is the point.

## Section 2 — done (2026-07-13)

A `section` (three equal columns on desktop, one on mobile) with a
`heading` in its header slot and three linked `card-icon`s: **Icelandic
horses for sale** → `/horses` (Phosphor `horse` icon), **Book a riding
facility** → `/facilities` (`calendar-check`), **Feed & bedding** →
`/feed` (`stack`). Written straight into the Canvas page's component
tree — content, not code — by an idempotent one-shot script (re-running
replaces the section rather than appending a second copy).

**Fixed a live bug in passing**: the hero's third button pointed at
**`/facilites`** — a typo, and a **404**. Now `/facilities`, and the
first two buttons were relabelled ("Horses for sale", "Book a
facility") to match the language used everywhere else.

**Rejected `tile_size: 4:3` on the cards.** A fixed aspect ratio makes
each card a grid item whose *automatic minimum width is derived from its
content height* — with tall text on a narrow screen the card demands
~490 px. Text cards must size to their content, so no aspect ratio.

**A mobile-overflow scare, retracted the same day.** Verifying at phone
width appeared to show every page clipped below ~480 px — raised as
[[0052-mobile-horizontal-overflow]], then **dropped: it was not a bug.**
Headless Chromium **clamps its window to 500 px**, so a
`--window-size=390` screenshot is the left-hand 390 px of a 500 px
render — cropped, not clipped. Measured properly in-browser, every page
has `scrollWidth == viewport`, zero overflowing elements, and nothing
wider than 390 px when the layout is squeezed to a phone box. The site
is fine on mobile; see 0052 for the full post-mortem.

**Note for the next section**: with the three-way split now doing the
orientation job properly, the hero's three buttons are redundant —
which is exactly what section 1 ("sharpen the hero to one promise, one
action") should resolve.

## Journey gap surfaced by this planning (needs a decision)

**There is no way to arrange a viewing or trial ride of a horse.** A
buyer can pay a 20% deposit to reserve one
([[0001-horse-deposit-reservation-flow]]) — but almost nobody wires
9.000 DKK for a horse they have never sat on. That step is entirely
offline today, and nothing on the site says so. At minimum the horse
pages and section 11 should say *"viewings by appointment — contact
us"*. Whether it stays a phone call or becomes a request flow is a
client decision; if the latter, it gets its own task.

## Acceptance criteria
- [ ] Sections built in the agreed order, each reviewed before the next
- [ ] Live-data sections implemented as code, not static copy that rots
      (featured horses)
- [ ] Client content gathered where flagged (story, testimonials, photos,
      opening hours, viewing policy)
- [ ] The viewing/trial-ride gap decided (copy-only vs request flow)
- [ ] Verified over real HTTP as an anonymous visitor; responsive at
      mobile and desktop

## Related
- [[shh-stables-platform]]
- [[0019-horse-catalog-page]], [[0020-facilities-overview-page]],
  [[0023-pricing-comparison-page]] (the pages these sections tease)
- [[0026-rider-account-access-policy]], [[0003-rider-membership-eligibility-workflow]]
  (the approval checkpoints section 9 must explain)
- [[0038-straw-and-wrap-sale-items]], [[0014-icelandic-horse-gaits-field]]
- [[0032-adopt-footer-navbar-sdc-components]] (store-driven address; map enhancement)
