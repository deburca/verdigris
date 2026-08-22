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
# Task: Homepage Band C trust content — "About the stud" and testimonials

## Description
[[0051-homepage-content-plan]] laid out 13 homepage sections in four
bands; Band D (practicalities) is fully built, Band B (the three
offers) is fully built, and section 7 (the gaits explainer) in Band C
is built — but sections 6 ("About the stud") and 8 ("Testimonials")
remain `todo`, both explicitly blocked on client-supplied content. As
of 2026-08-22 the live homepage still has no such section between "Why
an Icelandic horse?" and "How booking works" (confirmed by walking the
rendered heading structure over real HTTP).

0051's own framing: this is "what a buyer actually reads before
spending 45.000 DKK" — not decorative filler. Pulled out into its own
task because it's the single largest remaining gap in an otherwise
functionally-complete platform, and because it's purely a content
(not code) blocker worth tracking and chasing separately from 0051's
broader housekeeping.

## Acceptance criteria
- [ ] Client supplies: the stud's story/breeding philosophy/how long
      established, and the mares/stallions behind the herd (section 6)
- [ ] Client supplies: one buyer testimonial, one rider testimonial —
      0051 recommends exactly two, no more (section 8)
- [ ] Section 6 built as Canvas content (no code needed, per 0051's
      pattern — `section`/`text`/`image` components)
- [ ] Section 8 built with `card-testimonial`
- [ ] Verified over real HTTP; document order matches the agreed
      Band C position (between section 7 and Band D)

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] — parent task; this pulls out its two remaining `todo` rows
