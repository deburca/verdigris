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
# Task: Reconfirm Facebook/Instagram footer links are real

## Description
[[0032-adopt-footer-navbar-sdc-components]] (2026-07-08) placed the
footer's `social` menu with `facebook.com/stutterihestehoj` and
`instagram.com/stutterihestehoj`, explicitly logged at the time as
placeholders pending the client's real URLs — "two open client items,
both non-blocking: real social profile URLs... and an eventual
leaflet map embed."

As of 2026-08-22 those same URLs are still live in the footer. I
cannot verify from this environment whether they now point at real,
client-confirmed accounts or are still the original guesses — worth a
direct check before launch, since a wrong social link is worse than no
social link.

## Acceptance criteria
- [ ] Client confirms the real Facebook and Instagram URLs (or states
      the accounts don't exist / shouldn't be linked)
- [ ] `social` menu links updated to match, or removed if no real
      accounts exist
- [ ] Config exported

## Related
- [[shh-stables-platform]]
- [[0032-adopt-footer-navbar-sdc-components]] — where these were placeholder-flagged
