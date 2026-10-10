---
type: task
tags: [cms2/task]
status: todo
priority: medium
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Integrate hvg into deployment and operations

## Description
hvg was installed by hand and is deliberately not part of the three-site
routines. Bring it in line with vdg, kbg and shh once it is stable.

## Acceptance criteria
- [ ] hvg app added to the `drops` CLI config, with the database-import
      step left out or guarded: it drops every production table with no
      backup (it cost kbg its hivelog content on 2026-08-23)
- [ ] Decide whether to add hvg to `all-push` / `all-pull` in the Makefile
- [ ] Uptime and error monitoring cover `https://hivelog.eu/`
      (platform-wide gap: see [[0064-uptime-error-monitoring]])
- [ ] hvg included in the backup check
      ([[0065-verify-document-backup-strategy]])
- [ ] Confirm cron runs hourly in production and that expired demo
      accounts are actually deleted once the first is 7 days old
- [ ] Document the hvg production deploy steps in `infrastructure/`
      (`site:install --existing-config` onto an empty database; the
      `settings.php` requirements; the geofield `composer reinstall`
      remedy), alongside [[shh-deployment-procedure]]

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
