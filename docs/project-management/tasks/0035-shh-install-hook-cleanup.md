---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-07
---
# Task: shh install-hook cleanup — shared menu-link helper, trim duplicated prose

## Description
Three low-severity cleanup findings from the code review of
[[0026-rider-account-access-policy]]'s implementation, bundled:

1. **Extract a shared menu-link helper.** The
   "loadByProperties → MenuLinkContent::create if absent" block now
   exists in four near-verbatim copies (`shh_horse_catalog`,
   `shh_facilities_overview`, `shh_site_footer`,
   `shh_rider_registration`) and has already drifted: the first two
   use `if ($existing) return;` and omit `enabled => TRUE`, the last
   two use `if (!$existing)` with `enabled => TRUE`. A small shared
   helper (e.g. `shh_ensure_menu_link($menu, $uri, $title, $weight)`
   in a tiny `shh_common` module) collapses the four call sites and
   stops the drift.
2. **Trim the drupal_cms_helper patch comment.** The patch embeds a
   14-line prose comment restating what composer.json's patch
   description and task 0026 already say; the repo's other contrib
   patches keep inline comments to ~5 lines. Oversized hunks are the
   most rebase-fragile part of a patch — shrink it to 2–3 lines
   pointing at the composer entry / drupal.org/i/3481627. (Or better:
   report the bug upstream to Drupal CMS and drop the patch when a
   fixed release lands — the report itself is still not filed.)
3. **De-duplicate policy prose in `shh_rider_registration`.** The
   waiver-not-merged / two-staff-checkpoints narrative lives in three
   places (install docblock, info.yml description, task 0026
   Resolution); keep the code comments to what the code does and let
   the task doc own the policy, so the anticipated "fold waiver into
   registration" revisit can't leave stale narrative behind.

## Acceptance criteria
- [ ] One shared helper used by all four menu-link install hooks, with
      the `enabled`/guard-style drift resolved deliberately
- [ ] drupal_cms_helper patch comment ≤ ~5 lines; patch still applies
      (`composer install` clean) and registration behavior unchanged
- [ ] Upstream issue filed for the drupal_cms_helper notify bug, link
      recorded in task 0026
- [ ] shh_rider_registration docblock/info.yml trimmed to code-facts

## Related
- [[shh-stables-platform]]
- [[0026-rider-account-access-policy]]
- [[0019-horse-catalog-page]]
- [[0027-site-footer-and-contact-link]]
