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
Build the shh site to support three activities: horses listed for sale, hourly
reservation of riding areas, and hourly reservation of the riding hall — all through
a single Commerce-backed checkout.

## Scope
- In scope: horse sales catalog, hourly booking for riding areas + hall, unified cart/checkout, rider eligibility gating
- Out of scope: payment methods beyond what Commerce already supports platform-wide, multi-stable expansion

## Entity / architecture model
See [[shh-stables-platform-model]] for the full entity model, ERD, and implementation notes.

## Current status (2026-07-05)
[[0010-enable-shh-commerce-bat-bee-modules]] is done: Commerce, BAT, and BEE
(20 modules total) are enabled and `hestehoj` is installed and set as the
default theme. Getting there required uninstalling and re-enabling everything
module-by-module to work around two genuine install-time bugs in this BAT/BEE
RC release — see that task's "Resolution" section before repeating this on
another environment. No content types built yet.
Next actionable step is [[0011-shh-entity-content-type-modeling]].

## Tasks
```dataview
TABLE status, priority
FROM #cms2/task
WHERE contains(string(project), this.file.name)
SORT status asc, priority asc
```

## Open questions
- Cart-hold TTL value and whether it should be configurable per facility — see
  [[0012-cart-hold-concurrency-prototype]]
- Rider eligibility gate: route access check vs cart constraint vs both — see
  [[0003-rider-membership-eligibility-workflow]]
- Deposit/hold workflow for horse sales (separate from facility booking holds) —
  see [[0001-horse-deposit-reservation-flow]]

## Related decisions
```dataview
TABLE site, status
FROM #cms2/decision
WHERE (!contains("decision", file.name)) AND (contains(string(site), "shh") OR contains(string(site), "shared"))
SORT file.name asc
```
