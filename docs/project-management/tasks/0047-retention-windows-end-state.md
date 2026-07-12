---
type: task
tags: [cms2/task]
status: todo
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-12
updated: 2026-07-12
---
# Task: Confirm the end state of the two unconfigured retention windows

## Description
[[0006-gdpr-data-retention-policy]] closed with `contact_messages`
configured (365 days, matching the published policy's "deleted 12
months after you send them") but `closed_accounts` and `booking_log`
still `null` — purge disabled, and `/admin/reports/data-retention`
shows "Not confirmed — purge disabled" for both, indefinitely.

That may well be the *correct* end state given
[[0044-immediate-account-deletion-anonymisation]]:
- **booking_log**: 0044 anonymises operational records when an
  account is deleted, so surviving log entries are no longer
  personal data — indefinite retention of anonymised records is
  defensible and probably wanted (audit value).
- **closed_accounts**: the published policy says records are deleted
  when *the rider* closes their account (0044 self-service). The
  remaining gap is accounts the rider never closes: staff-blocked or
  abandoned registrations (e.g. rejected applicants) that sit as
  blocked accounts holding PII forever. A grace-period purge for
  those is exactly what the `closed_accounts` category was built
  for.

This task is deciding, not building — the machinery exists and is
tested (0006). Either set the windows in config (+ reconcile the
policy text if needed), or record "no automated purge, by design"
per category and make the status page wording reflect a decision
rather than an omission.

## Acceptance criteria
- [ ] Per category (`closed_accounts`, `booking_log`): an explicit
      decision — a configured window, or "no auto-purge by design"
      with the reasoning recorded here
- [ ] If "by design": the status page's "Not confirmed" wording
      changed to state the decision, so it stops reading as pending
- [ ] Policy §6 and the config still match after whatever is decided
      (0006's core invariant); config exported

## Related
- [[shh-stables-platform]]
- [[0006-gdpr-data-retention-policy]]
- [[0044-immediate-account-deletion-anonymisation]]
