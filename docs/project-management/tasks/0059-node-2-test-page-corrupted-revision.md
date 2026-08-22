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
# Task: node/2 ("Test Page") has a corrupted revision and 404s

## Description
First noted incidentally in [[shh-account-access-gap-analysis]]
(2026-07-06) and confirmed still present (2026-08-22, `curl
/node/2` → real `404`): node 2, the stock `page`-type sample content,
has **two rows both flagged `revision_default = 1`** in its
`node_revision` table. Entity storage can't resolve a single default
revision, so
`\Drupal::entityTypeManager()->getStorage('node')->load(2)` returns
`NULL` and both `/node/2` and its alias `/test-page` 404.

Not shh-specific business logic and the node isn't linked from
anywhere on the site, which is why it was left untracked when first
found — but it's genuine data corruption sitting in the database, and
worth fixing properly (or deleting the node outright, since it's stock
sample content) rather than leaving stale for whoever next touches
that content type.

## Acceptance criteria
- [ ] Inspect the two `revision_default = 1` rows for node 2 and
      determine the correct one (or confirm the content is disposable
      sample data)
- [ ] Either repair the revision table (clear the incorrect
      `revision_default` flag) or delete the node outright
- [ ] `/node/2` loads (or is confirmed intentionally gone) over real
      HTTP

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]] — where this was first noted
