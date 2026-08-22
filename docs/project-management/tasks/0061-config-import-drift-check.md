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
# Task: Nothing periodically checks that config/shh/sync still imports cleanly

## Description
Demonstrated live during a production deploy (2026-08-22): two
orphaned Canvas block-config entities
(`canvas.component.block.commerce_checkout_progress` and its
containing folder) had been sitting in `config/shh/sync` for an
unknown period, still declaring a dependency on the uninstalled
`commerce_checkout` module. `config:import`'s post-import validation
correctly refused to proceed — but the only thing that ever runs
`config:import` against the real tracked store is an actual
production deploy. Nothing catches this kind of drift earlier, in dev
or in CI.

Fixed for this specific case (both entities deleted, re-exported,
committed) — this task is about the missing *check*, not that one
incident.

## Acceptance criteria
- [ ] A repeatable way to dry-run `config:import` against
      `config/shh/sync` without touching a real environment (e.g. a
      scratch DB seeded from the current site, or a CI job that spins
      up DDEV and imports)
- [ ] Wired into either the [[0060-no-automated-tests-or-ci]] CI
      pipeline once it exists, or at minimum documented as a
      pre-deploy step in
      `docs/project-management/infrastructure/shh-deployment-procedure.md`
- [ ] Same check considered for `config/vdg/sync` and `config/kbg/sync`
      once those sites have real exported config (per the `Makefile`
      push/pull workflow)

## Related
- [[shh-stables-platform]]
- [[0033-durable-config-strategy-shh]] — the config-export-as-source-of-truth decision this protects
- `docs/project-management/infrastructure/shh-deployment-procedure.md`
