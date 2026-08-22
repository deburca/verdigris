---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: No systematic accessibility audit pass

## Description
Editoria11y is installed platform-wide and the `hestehoj` theme has
done real accessibility work (its own WCAG contrast pass caught a
real muted-background contrast regression during
[[0051-homepage-content-plan]]'s section 3 build) — but that's been
incidental, catching issues found in the course of other work, not a
systematic audit. No formal WCAG 2.1 AA pass has been recorded against
the finished discovery pages, product/facility pages, or the new
homepage sections.

## Acceptance criteria
- [ ] A full pass (Editoria11y's own reports, plus manual
      keyboard/screen-reader spot checks) across: homepage, `/horses`,
      a horse product page, `/facilities`, a facility page, `/feed`, a
      feed product page, `/pricing`, the rider dashboard, checkout
- [ ] Findings tracked as their own tasks rather than fixed ad hoc
- [ ] Repeat as each new homepage section (0055) lands, not just once

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] — the contrast regression this pattern already caught once
