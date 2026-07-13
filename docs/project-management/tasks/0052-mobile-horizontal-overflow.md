---
type: task
tags: [cms2/task]
status: todo
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-13
updated: 2026-07-13
---
# Task: Site-wide horizontal overflow on mobile — content is clipped

## Description
Found while building [[0051-homepage-content-plan]]'s section 2, by
screenshotting the result at phone width.

**Every page on the site overflows horizontally below ~480 px viewport
width.** The document's layout is wider than the screen, so content is
cut off on the right and the page scrolls sideways.

Evidence (headless Chromium, real HTTP, no device emulation — the
window size *is* the viewport):

| Viewport | Content clipped at right edge? |
|---|---|
| 390 px (iPhone) | **yes** |
| 420 px | **yes** |
| 450 px | **yes** |
| 480 px | no |
| 512 px+ | no |

So the layout has an effective **minimum width of ~460–480 px**.

**It is not caused by the new homepage section.** It reproduces on
pages untouched today, including ones with no cards and no images at
all:

- `/privacy-policy` — plain text: body copy runs off the right edge
  mid-sentence (it does not wrap, because its containing block is wider
  than the viewport)
- `/horses`, `/feed`, `/facilities`, `/product/1` — all clipped
- The homepage, before and after section 2

**Severity is higher than "cosmetic".** The navbar's hamburger button
(`navbar--hamburger-container … md:hidden`) sits at the **right-hand
edge** of the header — precisely the region being clipped. In the
390 px render it is not visible, which means **mobile visitors may have
no way to open the menu at all**. For a stable whose buyers and riders
will overwhelmingly browse on phones, that is a launch blocker.

## What has been ruled out
- **Not `.container` / `.region-content`**: both compile correctly —
  `width: 100%` with max-widths only inside `min-width` media queries.
- **Not a `min-w-*` utility in the markup**: the only width-forcing
  classes present are `min-w-0`, `min-w-[44px]`, `w-[44px]` (the
  44 px hamburger tap target) — nothing near 460 px.
- **Not the cards or images**: a text-only page overflows identically.
- **Not `aspect-4/3` on the new icon cards**: removing it (correctly —
  see below) did not change the overflow.

## What is still suspected
Page furniture present on every page: the **navbar**, the breadcrumb,
or the **footer** (its bottom bar uses
`md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]`, and the theme's `.cq-full`
container-query breakout utility is in play site-wide — the same
utility that caused the image-overflow bug in
[[0040-facility-images-featured-and-gallery]], where
`hestehoj:image` broke out of its grid cell).

`.cq-full` is the strongest lead: `width: 100cqw; margin-inline:
calc(50% - 50cqw)` depends on an ancestor with
`container-type: inline-size` (`.layout-content`). If any element
carrying it renders **outside** that container context, `cqw` resolves
against the viewport, and the negative margins can push the layout
wider than the screen.

## Acceptance criteria
- [ ] Root cause identified with browser devtools (not screenshot
      forensics) — find the element whose box exceeds the viewport
- [ ] No horizontal scrolling / clipping at 320 px, 390 px and 430 px
      on: home, `/horses`, `/feed`, `/facilities`, a product page, a
      facility page, `/pricing`, `/privacy-policy`, the rider dashboard
      and the cart/checkout
- [ ] The navbar hamburger is visible and operable at 390 px, and the
      menu opens
- [ ] A regression guard: at minimum, note the check in the go-live
      list; ideally assert `document.scrollWidth <= innerWidth` in a
      simple browser check
- [ ] Fixed in the theme (shared) — verify the other sites in the repo
      are not made worse, since they use the same components

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] (surfaced it)
- [[0040-facility-images-featured-and-gallery]] (the `.cq-full` /
  `bleed` precedent — same class of bug, contained to one component)
- [[0032-adopt-footer-navbar-sdc-components]] (navbar/footer furniture)
