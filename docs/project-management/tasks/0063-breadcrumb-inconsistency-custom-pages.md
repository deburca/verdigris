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
# Task: Breadcrumb inconsistency on the custom-controller pages

## Description
Noted in [[shh-account-access-gap-analysis]] (2026-07-06) and still
true (2026-08-22): ordinary node pages (e.g. `/oval-track`) show a
"Home" crumb before the current page title; the five custom-controller
pages built across 0019–0023 and 0022 (`/horses`, `/facilities`,
`/pricing`, `/feed`, the rider dashboard) show only the bare page
title, no parent crumb, because they never set one.

Cosmetic, not functional — deliberately left untracked at the time as
low priority — but worth a small, self-contained fix now that the
platform's discovery pages are the primary way visitors move around
the site.

## Resolution (2026-08-22)
Re-verified over real HTTP rather than assuming the 2026-07-06 finding
still held. It didn't: `/horses`, `/facilities`, `/pricing`, `/feed`,
and the new `/our-horses` (task 0057) all already render the exact
same `Home → current page` breadcrumb structure as an ordinary node
page like `/oval-track` — `aria-labelledby="Home"` followed by the
current page as `aria-current="page"`. Whatever produced the
inconsistency in July is gone; nothing in this session's changes
explains it, so most likely a core/theme update along the way already
fixed it as a side effect, uncalled-out. No code change was needed.

`shh_rider_dashboard` itself is out of scope for this pass — the
module is deliberately disabled as part of the site's phased launch
(commerce_cart/checkout, `shh_booking_hold`, `shh_horse_deposit`,
`shh_rider_dashboard` all off since 2026-07-22, confirmed intentional,
not a bug — see memory `shh-phased-launch-disabled-modules`). Revisit
its breadcrumb, if it even still needs it, when that module is
re-enabled.

## Acceptance criteria
- [x] Each *currently live* custom controller (`shh_horse_catalog`,
      `shh_facilities_overview`, `shh_pricing_comparison`,
      `shh_feed_catalog`) already sets a `Home` breadcrumb — confirmed,
      no change needed
- [x] Verified visually consistent with node-page breadcrumbs over
      real HTTP, across all four live pages
- [ ] `shh_rider_dashboard` — deferred until that module is
      re-enabled (phased launch, not a bug)

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]] — where this was first noted
