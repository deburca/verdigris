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
# Task: No XML sitemap

## Description
`/sitemap.xml` returns a real `404` (confirmed 2026-08-22) — no
`simple_sitemap` or equivalent module installed. Search engines have to
discover every page (horse listings, facility pages, feed products,
the pricing/comparison pages) purely by crawling the site's own
internal links, with no authoritative list of URLs or freshness
signal.

## Acceptance criteria
- [ ] `simple_sitemap` (or equivalent) installed and configured for
      `node`, `commerce_product`, and the custom-controller discovery
      pages (`/horses`, `/facilities`, `/pricing`, `/feed`)
- [ ] `/sitemap.xml` returns a valid sitemap over real HTTP
- [ ] Config exported per [[0020-shh-config-export-strategy]]

## Related
- [[shh-stables-platform]]
- [[0054-meta-description-and-og-tags]] — companion SEO gap
