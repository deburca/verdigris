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
| 3 | **Horses for sale** ⭐ **[code]** | 2–3 featured horses: photo, name, gaits, price, "See all horses". The money content for a stud, so highest of the three. Needs a block that pulls live `available` horses — a static section would go stale the moment one sells. | todo |
| 4 | **The facilities** | Oval Track, Manège, Lunge Ring: photo + one line each; 30-minute slots, 08:00–20:00; link to booking. | todo |
| 5 | **Feed & bedding** | Straw and wrap, per bale, collected at the stable. Short — secondary business, and the availability caveat already lives on the product pages (task 0038). | todo |

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
