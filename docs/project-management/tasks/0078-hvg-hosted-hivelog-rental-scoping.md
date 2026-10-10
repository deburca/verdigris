---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Scope the hosted HiveLog / Viculum rental offering

## Description
The longer-term aim is to rent use of the app instead of visitors running
their own server. That implies real accounts that do not expire, billing,
support and data isolation, none of which the demo has. This task is to
scope it and decide, not to build it.

## Acceptance criteria
- [ ] Write down what a paying customer gets (HiveLog on the web, Viculum
      sync, limits, support)
- [ ] Decide how accounts and billing are handled (the shh site already
      uses Drupal Commerce; reuse or separate?), and record it as an ADR
- [ ] Decide whether demo and paid users share one site or separate ones
- [ ] Decide what happens to demo data when a user upgrades
- [ ] Update the landing page "Coming next" section when there is a real
      offer

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
