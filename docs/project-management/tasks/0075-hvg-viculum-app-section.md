---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Add Viculum iOS download link and phone screenshots

## Description
The landing page pitches Viculum for iOS but has no download link and only
web screenshots. There is no real destination yet to link to.

## Acceptance criteria
- [ ] App Store (or TestFlight) URL obtained and added as a button in the
      hero and the "Web and iOS" section
- [ ] Phone screenshots of Viculum captured and added, ideally in a phone
      frame, to the carousel or a dedicated section
- [ ] "Viculum for iOS" card text confirmed accurate against the shipped
      app (currently written from the module's capabilities)
- [ ] The screenshots live in `hvg_landing/images/` and the page builder is
      updated; remember the page is seeded once, so existing pages are
      edited in Canvas (see the ADR)

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
