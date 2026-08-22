---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: node/2 ("Test Page") 404s — not corruption, already-trashed content

## Description
First noted incidentally in [[shh-account-access-gap-analysis]]
(2026-07-06), diagnosed there as **corruption**: node 2, the stock
`page`-type sample content, appeared to have two rows both flagged
`revision_default = 1` in its `node_revision` table, which was assumed
to be why entity storage returned `NULL` and both `/node/2` and its
alias `/test-page` 404'd.

## Resolution (2026-08-22) — the original diagnosis was wrong
The duplicate `revision_default` flag was real (fixed: cleared on the
stale revision, vid 3 — matching both the `node` base table and
`node_field_data` — left as the sole default) but **was never the
actual cause of the 404**. The real cause: `node_field_data.deleted`
held a real timestamp. This site has the **Trash** module (3.0.32)
enabled, and node 2 had already been soft-deleted through it at some
point — 404ing trashed content is Trash's correct, intended behaviour,
not a bug.

Confirmed disposable (stock Drupal CMS demo content, title literally
"Test Page", not linked from any menu or content on the site) and
purged permanently: `drush trash:purge node 2`. Verified all traces
gone (`node`, `node_revision`, `path_alias` all zero rows for nid 2),
`/node/2` and `/test-page` correctly 404 (genuinely gone now, not
corrupted), and the sitemap ([[0058-xml-sitemap-missing]]) still
generates cleanly.

## Acceptance criteria
- [x] Inspected the two `revision_default = 1` rows — real but a red
      herring, not the actual cause
- [x] Root cause found: intentional Trash-module soft-deletion, not
      corruption
- [x] Confirmed disposable and purged via `trash:purge`
- [x] `/node/2` confirmed intentionally gone over real HTTP

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]] — where this was first noted
