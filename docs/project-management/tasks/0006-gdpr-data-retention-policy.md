---
type: task
tags: [cms2/task]
status: in-progress
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-11
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
- [ ] Retention period defined per data category (order PII, waiver
      documents, membership records) — **categories defined and built;
      periods still owed by the client/adviser** (see below)
- [x] Automated or documented manual purge process past retention
      window — automated, per category, safely disabled until each
      window is confirmed (see Implementation)
- [ ] Privacy policy page updated to reflect actual practice —
      blocked on the same answers; node 1 stays unpublished
- [ ] The published privacy policy's retention section and this task's
      implemented practice are verified to match (same categories, same
      periods) — every `[TO CONFIRM]` in
      `docs/project-management/PRIVACY POLICY.md` resolved, none
      published as placeholder text

## Implementation (2026-07-11) — machinery built, windows awaited

New module `web/modules/custom/shh_data_retention`. Everything except
the actual numbers is done: the purge engine is complete, tested, and
**does nothing until a category's window is confirmed** — every window
in `shh_data_retention.settings` ships `null` ("not confirmed, purge
disabled"), so the site cannot implement a practice the unpublished
policy doesn't state. When the client confirms a number, it's set in
config and exported (decision 0020) together with the matching policy
text — deliberately no settings UI, so practice and policy move in one
reviewed change.

**Categories** (mirroring the privacy draft's section 6) and their
anchors:
- `waiver_submissions` — days after the **rider's last visit**
  (latest booked slot end from 0002's log → latest placed facility
  order → the waiver's own date), not the signature date: claims
  exposure runs from the last ride, which is exactly the insurer
  question.
- `contact_messages` — days after submission (`contact` webform).
- `membership_records` — days after expiry/revocation (anchor:
  `expires`, falling back to creation); pending/active never touched.
- `closed_accounts` — days after a **blocked** account was last
  seen/created; uid 1 and anyone with a role beyond "authenticated"
  (staff) are never eligible. Deletes the account only — records
  governed by other rules (orders, waivers) keep their own clocks.
- `booking_log` — days after the booked slot ended.
- **Orders/invoices are deliberately NOT a category**: the Danish
  Bookkeeping Act's 5-year retention is a keep-rule owned by the
  accountant; automated deletion of accounting records is exactly
  the kind of surprise this module exists to prevent.

Cron runs the configured purges at most daily; a read-only status
report at `/admin/reports/data-retention` (`access site reports`)
shows each category's window ("Not confirmed — purge disabled" until
then), what the anchor is, how many records are currently eligible,
and the last run with its deletion count.

**Verified**: 403 anonymous/rider, 200 admin on the status page; a
synthetic two-year-old record in *every* category (aged rider +
waiver + contact message + expired membership + log entry) purged by
a 365-day window while everything real survived — the 2 real waiver
submissions, the deliberately-blocked-but-recent freya_jensen (uid
4), all real memberships and log rows; the config path proven by
enabling one category (3650 days → ran, 0 eligible) and disabling it
again (runAll no-op); drush cron clean. One flow interaction noted:
programmatically saving the synthetic waiver auto-created a pending
membership (0003's hook — correct behaviour), removed with the rest
of the test data; on production, an account purge can likewise leave
an orphaned *pending* membership behind — acceptable (pending/active
are live workflow states retention must not touch), worth remembering
when reading the membership list. phpcs clean; config exported
(`core.extension` + the all-null settings object).

**Resolved (2026-07-11, task 0044):**
- `closed_accounts` grace period → **0** (immediate deletion on account
  delete, no cron window). Privacy policy account-closure line updated.
- Orders/5-year Bookkeeping Act → **N/A** (external accounting system;
  no retention category needed on this platform).

**Resolved (2026-07-11, client decision):**
- `contact_messages` → **365 days** (12 months). Config set;
  privacy policy contact-messages line updated; a 12-month retention
  notice added to the contact webform as a `webform_markup` element at
  weight 100 ("notice and choice" transparency — if the user sends a
  message they accept the practice; if not, they can use the phone);
  config exported.

**Still open — one conversation closes all three:**
1. `waiver_submissions`: how long after the last visit (insurer's
   claims exposure window)?
2. Draft §2: minimum rider age; how is parental consent recorded for
   under-18 riders (if allowed)?
3. Draft §4: hosting and email provider names + EU/EEA location
   (for the data-transfers statement).

When answered: set `waiver_submissions` in `shh_data_retention.settings`,
resolve the three remaining `[TO CONFIRM]`s in the privacy draft,
publish node 1 (which also makes 0027's footer link appear),
`make shh-export`, and close this task with the policy⇄practice
match check.

## Related
- [[shh-stables-platform]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0027-site-footer-and-contact-link]]
- [[0033-durable-config-strategy-shh]]
- `docs/project-management/PRIVACY POLICY.md` (customer-facing draft)
