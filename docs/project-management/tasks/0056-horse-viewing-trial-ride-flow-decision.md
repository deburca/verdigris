---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: Decide and build the horse viewing/trial-ride request path

## Description
Surfaced by [[0051-homepage-content-plan]] while planning the
homepage and still open: **there is no way to arrange a viewing or
trial ride of a horse anywhere on the site.** A buyer can pay a 20%
deposit to reserve one ([[0001-horse-deposit-reservation-flow]]), but
almost nobody wires 9.000 DKK for a horse they've never sat on. Today
the only acknowledgement of this is a single sentence added in 0051's
section 11 ("Viewings and trial rides are arranged by appointment —
get in touch and we will find a time") pointing at the generic contact
form — there is no horse-specific request, no link from the horse
product page itself, and no way for staff to know *which* horse a
viewing request is about without reading free-text.

0051 explicitly left this as a client decision: stay copy-only
(current state) or build a real request flow. Tracked here as its own
task since it's a genuine journey gap with money attached (it sits
directly upstream of every deposit/purchase), not a polish item.

## Acceptance criteria
- [ ] Client decision: copy-only (current, already shipped) vs. a
      dedicated "Request a viewing" flow
- [ ] If a flow: a per-horse request form (webform or lightweight
      custom form, following the existing `shh_horse_deposit` /
      `BuyCreditPackForm` pattern of a dedicated form over generic
      contact) linked from the horse product page, capturing which
      horse and preferred times, notifying staff
- [ ] If copy-only stays: confirm the horse product page itself (not
      just the homepage) states viewings are available by appointment
      — currently it does not
- [ ] Verified over real HTTP as an anonymous visitor

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] — where this gap was surfaced
- [[0001-horse-deposit-reservation-flow]] — the purchase step this sits upstream of
