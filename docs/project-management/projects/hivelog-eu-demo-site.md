---
type: project
tags: [cms2/project]
status: active
site: hvg
created: 2026-10-10
updated: 2026-10-10
target:
---
# Project: hivelog.eu — public HiveLog / Viculum demo site

## Goal
A public site, hivelog.eu (`hvg`), where beekeepers can try the HiveLog
module in a browser with a throw-away account, learn about the Viculum iOS
app, and register interest in two things not yet on sale: a hosted
(rented) HiveLog + Viculum service and hive sensors (weight and climate).
Live in production since 2026-10-09 with a landing page, self-service demo
accounts and an interest form. See [[0022-hivelog-eu-demo-site-architecture]].

## Scope
- In scope: the `hvg` multisite (theme, Canvas landing page, demo accounts,
  interest form, mail), its deployment and operations, and the content
  needed to market the app and (later) hosting and sensors.
- Out of scope: changes to the HiveLog module itself (own repo,
  `deburca/hivelog`), the Viculum iOS app (own repo), and building the
  rental/billing system until it is decided (see [[0078-hvg-hosted-hivelog-rental-scoping]]).

## Entity / architecture model
Described in [[0022-hivelog-eu-demo-site-architecture]]. In short: custom
modules `hvg_landing` and `hvg_demo`, Beeswax theme (own repo,
`deburca/beeswax`), config in `config/hvg/sync`, deploy via `make hvg-pull`.

## Tasks
```dataview
TABLE status, priority
FROM #cms2/task
WHERE contains(string(project), this.file.name)
SORT status asc, priority asc
```

## Open questions
- How long should a demo account live (7 days is a first guess), and what
  signup rate limit is right once real traffic arrives?
- Which Viculum distribution link goes on the page (App Store or TestFlight)?
- What does the rental offering need (accounts, billing, isolation)?

## Related decisions
- [[0022-hivelog-eu-demo-site-architecture]]
- [[0001-multisite-architecture]]
