---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-07
---
# Task: GDPR data retention policy for PII and liability waivers

## Description
Horse buyer PII and rider health/liability waiver data have no defined retention
or deletion policy. Required for EU/DK compliance.

**Privacy policy draft exists (2026-07-07)** — a reviewed, simplified
customer-facing draft at `docs/project-management/PRIVACY POLICY.md`,
destined for the site's still-unpublished "Privacy policy" page
(node 1; publishing it also makes the footer link from
[[0027-site-footer-and-contact-link]] appear). The draft and this
task are two halves of the same commitment and **must be finalized
together**: this task decides the actual retention practice, and the
draft's section 6 ("How long we keep it") states it publicly. The
draft's `[TO CONFIRM]` placeholders are this task's inputs — whoever
resolves either side must reconcile the other before anything is
published, so the site never promises a practice it doesn't implement
(or implements one it doesn't disclose).

Placeholders in the draft this task must answer (section 6, plus two
adjacent ones the same conversation should settle):
- Signed **waivers**: how long after a rider's last visit, per the
  insurer/adviser (claims exposure) — the draft deliberately leaves
  this blank rather than guessing
- **Closed accounts** / bookings / membership records: grace period
  after account deletion (draft suggests confirming e.g. 12 months)
- **Contact-form messages**: purge window (draft suggests confirming
  e.g. 12 months)
- Orders/invoices are already stated as **5 years (Danish Bookkeeping
  Act)** in the draft — verify with the accountant and keep the two
  documents in agreement
- Adjacent (section 2/4 of the draft, same sign-off): minimum age
  policy for riders/accounts, and hosting/email provider names +
  EU/EEA location for the transfers statement

## Acceptance criteria
- Retention period defined per data category (order PII, waiver documents,
  membership records)
- Automated or documented manual purge process past retention window
- Privacy policy page updated to reflect actual practice
- [ ] The published privacy policy's retention section and this task's
      implemented practice are verified to match (same categories, same
      periods) — every `[TO CONFIRM]` in
      `docs/project-management/PRIVACY POLICY.md` resolved, none
      published as placeholder text

## Related
- [[shh-stables-platform]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0027-site-footer-and-contact-link]]
- [[0033-durable-config-strategy-shh]]
- `docs/project-management/PRIVACY POLICY.md` (customer-facing draft)
