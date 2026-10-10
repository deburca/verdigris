---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Danish translation of hivelog.eu

## Description
The three other sites are Danish/English (see
[[0069-danish-english-multilingual-all-sites]]); hvg is English only.
Danish beekeepers are an obvious audience (HiveLog already has a Danish
interface translation and the CBR registry feature).

## Acceptance criteria
- [ ] Decide the default language and URL scheme for hvg
- [ ] Add language and translation modules following the per-site pattern
      from 0069 (a `hvg_multilingual` module shipping the `.po` snapshot)
- [ ] Translate the landing page and the interest form (page content is not
      config: the seeder and Canvas need translation handling)
- [ ] Place a language switcher

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
