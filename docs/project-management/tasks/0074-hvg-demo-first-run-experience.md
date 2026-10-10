---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Improve the first-run experience for demo users

## Description
A new demo user lands on an empty HiveLog. Creating the first apiary seeds
the 31-entry seasonal calendar, so the dashboard immediately shows many
"overdue" items (123 to review, 113 overdue in the test data in early
October), plus a Denmark-specific prompt to set a CBR number. That is a
poor first impression for a demo and for visitors outside Denmark.

## Acceptance criteria
- [ ] Decide what a new demo user should see: guided steps, a pre-made
      sample apiary, or an explanation of the seeded calendar
- [ ] If sample data is used, make sure the 7-day cleanup removes it with
      the account (the cleaner already deletes everything the user owns)
- [ ] Decide how to treat the CBR number prompt for non-Danish visitors
      (HiveLog behaviour: may need a change in the module or a theme-level
      hide, not a site workaround)
- [ ] Welcome message or short help text on first login

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
