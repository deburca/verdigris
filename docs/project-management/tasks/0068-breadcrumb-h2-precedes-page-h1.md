---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: Breadcrumb's sr-only `<h2>` renders before the page's `<h1>`, site-wide

## Description
Found during [[0067-accessibility-audit-pass]]'s structural review
(2026-08-22): `web/themes/custom/hestehoj/templates/navigation/breadcrumb.html.twig`
line 17 wraps the breadcrumb nav's screen-reader-only label in an
`<h2 id="system-breadcrumb">Breadcrumb</h2>`. The breadcrumb nav sits
in the page header, above `<main>` — so on every page site-wide except
the homepage, a screen-reader user navigating by heading finds this
`<h2>` **before** ever reaching the page's real `<h1>` title. Confirmed
across every page type checked: `/horses`, `/our-horses`,
`/facilities`, `/oval-track`, `/lunge-ring`, `/feed`, `/pricing`,
`/news`, a news post, a product page — all show `h2` then `h1` in DOM
order. Only the homepage is clean (task 0051 section 1 specifically
fixed *its* missing-h1 problem, but didn't touch the breadcrumb
template, and the homepage doesn't render this breadcrumb block at
all).

This is a real WCAG 2.1 AA concern (SC 1.3.1 Info and Relationships;
SC 2.4.6 Headings and Labels best practice) — a heading-based outline
should start with the page's actual title, not an unrelated navigation
label.

The `<h2>` is also redundant on its own terms: the `<nav>` it lives in
already carries `aria-label="breadcrumb"` (line 16), which alone gives
the nav landmark its accessible name — the nested heading adds nothing
a screen reader doesn't already have. Confirmed nothing in the theme's
CSS or JS references `#system-breadcrumb`, so it's safe to change
freely.

## Resolution (2026-08-22)
Removed the `<h2 id="system-breadcrumb">` line outright rather than
swap it for a `<span>` — `aria-label="breadcrumb"` on the `<nav>`
already fully covers the accessible-name requirement, so there was
nothing left for a replacement element to do. Left a comment in the
template explaining why, so it isn't reintroduced as a "missing label"
fix later.

Confirmed the same pattern exists verbatim in the `zwarte_piet` and
`quick_silver` theme starter kits (vdg/kbg) — out of scope here (this
task is shh-only), not fixed.

Verified over real HTTP across all eleven pages the original finding
covered — homepage, every discovery/listing page, a facility page, a
horse product page, a news post — every one now has `<h1>` as its
first heading in DOM order. Breadcrumb nav itself renders unchanged.
No config change (theme template only).

## Acceptance criteria
- [x] `breadcrumb.html.twig`'s `<h2 id="system-breadcrumb">` removed
      — `aria-label="breadcrumb"` on the `<nav>` already covers the
      accessible-name requirement
- [x] Verified over real HTTP: every page's first heading in DOM order
      is its own `<h1>`, not the breadcrumb label

## Related
- [[shh-stables-platform]]
- [[0067-accessibility-audit-pass]] — where this was found
- [[0051-homepage-content-plan]] — section 1's earlier, narrower h1 fix (homepage only)
