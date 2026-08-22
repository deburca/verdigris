---
type: task
tags: [cms2/task]
status: in-progress
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

## Progress (2026-08-22)
**Correction to this task's own premise**: Editoria11y turned out to
be enabled on the platform generally (vdg) but was **not** actually
enabled on shh specifically — confirmed via `moduleHandler()`, no
`editoria11y` tables existed. Installed it now (already a vendored
Composer dependency, same situation as Metatag/simple_sitemap this
session): `view editoria11y checker` is granted to `authenticated` by
the module's own default install config, so it will now start
scanning pages automatically the next time a logged-in editor/admin
browses the site — no further setup needed. Config exported.

**What this session could and couldn't do**: no headless browser or
Claude-in-Chrome session was available this session (no Chrome/Chromium
installed on this machine, extension declined) — so the "Editoria11y's
own reports" and "manual keyboard/screen-reader spot checks" halves of
the acceptance criteria genuinely could not be performed. Rather than
skip the task or claim more than was done, ran a **structural HTML
review** instead — real static inspection of the rendered markup
across every currently-live page (heading hierarchy, image alt text,
form label association), which doesn't require a browser to be
meaningful.

**Found one real, concrete, site-wide issue**, tracked as its own task
per this task's own acceptance criteria:
[[0068-breadcrumb-h2-precedes-page-h1]] — the breadcrumb's sr-only
`<h2>` renders before every page's real `<h1>`, everywhere except the
homepage.

**Checked and clean**: every page has exactly one `<h1>`; no `<img>`
missing `alt` text anywhere (the project's media/alt-text discipline
from tasks 0039–0041 holds); the contact webform's fields are all
correctly `<label for>`-associated. Also checked, and *not* filed as a
finding: sections read `h2`/`h3`/`h4` without skipped levels, and each
`sdc.hestehoj.section`'s inner `<header>` is spec-correctly non-landmark
(nested inside a `<section>`, so it never picks up an implicit
`banner` role) — a plausible-looking concern that turned out, on
reading the actual HTML/ARIA mapping spec, not to be a real issue.

Two pages from the original list — the rider dashboard and checkout —
are currently unreachable: `shh_rider_dashboard`, `commerce_cart`, and
`commerce_checkout` are deliberately disabled as part of the site's
phased launch (see memory `shh-phased-launch-disabled-modules`), not a
bug. Their audit is deferred to whenever those modules are
re-enabled.

Left `in-progress`, not `done`: the acceptance criteria's live-browser
half (automated Editoria11y scans actually running, keyboard/screen-reader
spot checks) is real, outstanding work for a session with browser
access — this pass covered what was structurally checkable without
one.

## Acceptance criteria
- [x] Editoria11y actually enabled on shh (was not, despite this
      task's own original premise)
- [x] Structural review (heading hierarchy, alt text, form labels)
      across every currently-live page
- [x] Findings tracked as their own task ([[0068-breadcrumb-h2-precedes-page-h1]])
      rather than fixed ad hoc
- [ ] Editoria11y's own scan reports reviewed (needs a real browser
      session — not available this session)
- [ ] Manual keyboard/screen-reader spot checks (same)
- [ ] Rider dashboard / checkout — deferred until those modules are
      re-enabled (phased launch)
- [ ] Repeat as each new homepage section (0055) lands, not just once

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] — the contrast regression this pattern already caught once
