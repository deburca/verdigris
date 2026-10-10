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
# Task: SEO metadata and landing-page polish

## Description
Gaps seen during the build: the landing page's title tag rendered empty in
a check, there is no meta description or social preview image, and a few
visual details were never checked in a browser (heading contrast on the
beige sections, touch-swiping the carousel on a phone).

## Acceptance criteria
- [ ] Page title and meta description set for `/home` (Metatag is on for
      Canvas pages)
- [ ] Open Graph / social preview image
- [ ] Heading contrast on the muted background sections checked
      (WCAG AA) and fixed if it fails
- [ ] Carousel tested with real touch swipe on iOS and Android, and in the
      Canvas editor preview
- [ ] Run the Editoria11y / accessibility pass on the page
      (see [[0067-accessibility-audit-pass]])
- [ ] Sitemap and robots reviewed for a public marketing site

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
