---
type: task
tags: [cms2/task]
status: backlog
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

## Acceptance criteria
- [ ] Each custom controller (`shh_horse_catalog`,
      `shh_facilities_overview`, `shh_pricing_comparison`,
      `shh_feed_catalog`, `shh_rider_dashboard`) sets a breadcrumb
      (typically `Home` → the page itself, or `Home` → parent
      discovery page → detail page where nested)
- [ ] Verified visually consistent with node-page breadcrumbs across
      all five

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]] — where this was first noted
