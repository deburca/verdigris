---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-08
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
- [x] One shared helper used by all four menu-link install hooks, with
      the `enabled`/guard-style drift resolved deliberately
- [x] drupal_cms_helper patch comment ≤ ~5 lines; patch still applies
      (`composer install` clean) and registration behavior unchanged
- [x] Upstream issue filed for the drupal_cms_helper notify bug, link
      recorded in task 0026 — filed by Paddy 2026-07-08 as Drupal CMS
      issue #3591417:
      https://git.drupalcode.org/project/drupal_cms/-/work_items/3591417
- [x] shh_rider_registration docblock/info.yml trimmed to code-facts

## Resolution (2026-07-08)

**1. Shared helper — done.** New two-file module
`web/modules/custom/shh_common` (info.yml + .module, no install hook,
no config): `shh_ensure_menu_link(string $menu, string $uri, string
$title, int $weight)`. All four install hooks
(`shh_horse_catalog`, `shh_facilities_overview`, `shh_site_footer`,
`shh_rider_registration`) now call it; each module gained a
`shh_common:shh_common` dependency (which also fixes, transitively, the
latent gap where shh_horse_catalog and shh_facilities_overview used
`MenuLinkContent` without declaring `menu_link_content` at all). Drift
resolved deliberately: create-unless-present keyed by menu + URI (an
existing link's title/weight/enabled are never overwritten — staff
edits survive a reinstall), `enabled => TRUE` always set explicitly.
The rider_registration `hook_uninstall()` link *removal* is not
create-shaped and deliberately stays local to that module. Verified
with a real uninstall → reinstall cycle of shh_horse_catalog through
both branches: existing link → exactly 1 link before and after (no
duplicate); link deleted first → recreated with
title/weight/enabled all correct. `shh_common` enabled on shh
(config export updated: `core.extension` is the only config diff).

**2. Patch comment trimmed — done, with a self-inflicted lesson.**
The 14-line prose comment in
`patches/drupal_cms_helper-scope-register-form-alter-to-admin-create.patch`
is now 4 lines pointing at the composer.json entry + task 0026. First
attempt shipped a malformed hunk header (`+44,11` for a hunk adding 7
lines to 6 — must be `+44,13`), and **`composer patches-repatch`
reinstalls the pristine package and reports "Patching …" even when the
hunk then fails to apply** — the guard line was silently missing until
grepped for. Always verify the patched file's content after a
relock/repatch, not just the command's exit. After fixing the header:
relock + repatch clean, guard line present, `composer install` clean,
all 8 patched packages' hunks re-verified in the live files (bee ×2,
bat, bat_api, canvas, byte_theme, mercury, drupal_cms_helper).
Registration behavior unchanged by construction (hunk logic
byte-identical, only the comment shrank); re-verified anonymous
`/user/register` renders (200, mail field present, no notify element).

**3. Prose de-duplicated — done.**
`shh_rider_registration.install`'s hook_install docblock is now
code-facts only (what it sets, why the link self-hides) with one
pointer to task 0026 for the policy narrative; info.yml description
likewise (also now mentions the 0034 runtime guard the module has
carried since that task). Task 0026's Resolution remains the single
owner of the waiver-not-merged / two-checkpoints story.

**4. Upstream report — filed.** Drafted here (title, version,
problem, impact, reproduce steps, proposed fix), filed by Paddy
2026-07-08 as **Drupal CMS issue #3591417**
(https://git.drupalcode.org/project/drupal_cms/-/work_items/3591417 —
the queue lives on GitLab work items; `drupal.org/i/3591417` resolves
to an unrelated node). Investigated the task's own
`drupal.org/i/3481627` reference first: that is the **core**
admin-create UX issue the alter mimics (cited by the method's own
`@todo`), i.e. context — not a pre-existing report of this bug. When
core 3481627 lands and drupal_cms_helper drops the alter, both the
bug and our patch retire together — check #3591417 before any
drupal_cms_helper version bump.

Verified over real HTTP as anonymous after all changes: all three
main-nav links + footer "Contact us" render, `/`, `/horses`,
`/facilities`, `/pricing` all 200.

## Related
- [[shh-stables-platform]]
- [[0026-rider-account-access-policy]]
- [[0019-horse-catalog-page]]
- [[0027-site-footer-and-contact-link]]
- [[0006-composer-patch-management]]
