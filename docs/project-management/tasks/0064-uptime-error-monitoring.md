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
# Task: No uptime or error monitoring

## Description
No uptime check and no application error tracking (e.g. Sentry-style)
exists for a site that now processes real Commerce payments (horse
sales, facility bookings, deposits, credit packs). Reasonable to have
deferred this for a small stables business at this scale, but worth
having in place before a busier season or any paid marketing push —
right now a payment-path failure would only be noticed if a rider or
staff member reports it.

## Acceptance criteria
- [ ] Uptime monitoring on the production URL (e.g. UptimeRobot or
      similar — low-cost, no code change needed)
- [ ] Decide whether application-level error tracking is worth the
      added dependency for this project's scale, or whether Drupal's
      existing `dblog` + manual log review stays sufficient
- [ ] If added: document the alert destination (who actually gets
      paged)

## Related
- [[shh-stables-platform]]
- Platform-wide, not shh-specific — see
  `docs/project-management/infrastructure/missing-configurations.md`
