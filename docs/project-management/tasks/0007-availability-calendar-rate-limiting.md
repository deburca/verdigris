---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Rate limiting for public availability calendar

## Description
Public read-only availability endpoint (0017) has no rate limiting, exposing it
to scraping or scripted slot-sniping.

## Acceptance criteria
- Rate limit applied to availability/calendar endpoint (flood control or reverse-proxy rule)
- Confirm no sensitive rider PII is exposed in the public calendar response

## Related
- [[shh-stables-platform]]
- [[0017-anonymous-vs-authenticated-booking-access]]
