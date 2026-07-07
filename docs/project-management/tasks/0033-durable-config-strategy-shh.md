---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-07
---
# Task: Durable config strategy for shh's install-hook-managed state

## Description
Code-review finding on [[0026-rider-account-access-policy]]'s
implementation, and a property of the whole shh module family: every
feature module (`shh_rider_registration`, `shh_site_footer`,
`shh_main_navigation`, `shh_public_availability`, …) applies its
config imperatively in a fire-once `hook_install()`. shh's
`config_sync_directory` (`sites/shh/files/sync`) is **empty**, so
nothing re-asserts this state: the moment config export/import is
adopted (or a Drupal CMS recipe reapplies `user.settings`), values
like `register: visitors_admin_approval` silently revert to their
platform defaults with no error — reinstalling each module is
currently the only re-assertion mechanism. Not urgent while the
project stays on the imperative pattern (which is the deliberate,
consistent house style), but it becomes a real trap the day anyone
runs `drush config:import` on shh. Note `sites/default/files/sync`
*is* populated and carries `register: admin_only` — a nearby example
of exactly the kind of store that would clobber live state.

## Acceptance criteria
- [ ] Decide the strategy: (a) commit to imperative install hooks and
      document that `drush cim` must never run against shh (e.g. a
      guard/README, or removing the empty sync dir setting), or
      (b) adopt config export for shh so the sync store is the source
      of truth and the install hooks' config writes are captured in it
- [ ] Whichever way: `user.settings: register` on shh survives the
      chosen workflow (test the actual revert scenario, don't assume)
- [ ] Document the decision where the next module author will see it
      (e.g. AGENTS.md or a project-management decision entry)

## Related
- [[shh-stables-platform]]
- [[0026-rider-account-access-policy]]
