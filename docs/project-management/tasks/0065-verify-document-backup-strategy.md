---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: Verify and document the production backup strategy

## Description
No documented backup/recovery procedure exists in this project's docs
beyond DDEV snapshots for local dev. During today's production deploy
(2026-08-22) a `backups/` directory was observed in the production
host's `~/verdigris` checkout, suggesting *something* backs the site
up already (OVH's own hosting-level backups, a cron script, or manual
dumps) — but this project has no record of what it actually is, how
often it runs, what it covers (DB only, or files too), or how a
restore would actually be performed.

This is a "find out and write it down" task, not necessarily a "build
something new" one — the gap is the missing documentation and
verification, not a confirmed absence of backups.

## Acceptance criteria
- [ ] Determine what currently backs up production shh (and vdg/kbg,
      since they share the host) — OVH platform-level, the observed
      `backups/` directory's actual mechanism, or nothing
- [ ] Confirm backup frequency and retention
- [ ] Document a recovery procedure in
      `docs/project-management/infrastructure/shh-deployment-procedure.md`
      or a new sibling doc
- [ ] If genuinely nothing exists, decide and implement a minimal
      scheduled DB dump

## Related
- [[shh-stables-platform]]
- Platform-wide, not shh-specific — see
  `docs/project-management/infrastructure/missing-configurations.md`
