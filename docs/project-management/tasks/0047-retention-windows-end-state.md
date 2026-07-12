---
type: task
tags: [cms2/task]
status: done
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
- [x] Per category (`closed_accounts`, `booking_log`): an explicit
      decision — a configured window, or "no auto-purge by design"
      with the reasoning recorded here
- [x] If "by design": the status page's "Not confirmed" wording
      changed to state the decision, so it stops reading as pending
- [x] Policy §6 and the config still match after whatever is decided
      (0006's core invariant); config exported

## Resolution (2026-07-12)

Deciding these two required looking at what the categories actually
reach on **this** site, and that turned up a latent bug plus a void
premise — so the outcome is a small rework, not just two config
values.

### booking_log → **retained by design, no purge**

0044 anonymises log entries in place when an account is deleted
(actor nulled, entry renders "Deleted user"), so surviving entries
hold **no personal data**. A time-based purge would therefore delete
nothing personal and destroy the audit trail 0002 exists to provide.
Removed from the purge engine entirely and moved to a new
"retained by design" section (below).

### closed_accounts → **premise void; replaced by `stale_registrations`**

Two findings:

1. **The category's premise no longer exists.** 0044 made account
   closure *immediate and complete* — no closed account survives to
   age out — so a "grace period after closure" purge has no possible
   targets. Keeping a category that can never fire is worse than
   useless: it reads as a pending policy gap forever.
2. **What the code actually reached was a latent bug.** It selected
   *any blocked, non-staff* account. On this site "blocked" is the
   state [[0026-rider-account-access-policy]] puts every new
   applicant in until staff approve them — and it is also the state
   of a **suspended** rider. Left as written, the purge would
   eventually have deleted suspended riders, which is wrong twice
   over: it frees their e-mail to re-register (defeating the
   suspension) and anonymises their orders. A staff block is a "keep
   out" record, not a retention subject.

The genuine PII exposure hiding under that name is the **application
nobody ever approved**: a name and e-mail held forever. So the
category was reworked and renamed to `stale_registrations`, with two
structural guards — **never logged in** (`access = 0 AND login = 0`,
which excludes suspended riders by construction) and never staff —
anchored on the application date, window **365 days** (consistent
with the contact-message window the client already accepted). A
`hook_update_N` migrates the config keys and drops the stale run
state.

Deleting such an account runs 0044's `hook_user_delete`, so the
cleanup cascades correctly (submissions deleted, orders/log entries
anonymised) rather than orphaning records — verified below.

### Status page

Now two tables: **"Purged automatically (daily cron)"**
(contact messages, unapproved registrations) and **"Kept with no
automatic purge — deliberate decisions (task 0047), not pending
items"** (booking log, Commerce orders, active accounts), each with
its reasoning. The old "Not confirmed — purge disabled" wording is
gone; a category with no window now reads "No window set — purge
disabled" and no category is left in that state.

### Policy ⇄ practice reconciled (0006's invariant)

The published policy (node 1, live at `/privacy-policy`) and the
source draft both gained:
> **Registration applications we never approve**: if you apply for an
> account and it is never approved or used, we delete the application
> — including your name and e-mail address — 12 months after you
> applied.

And **lost a stale claim**: §6 promised retention of "membership
records", a concept [[0045-remove-rider-membership-module]] deleted
outright. Now "Your account and bookings". (Node 1 saved as a new
revision with a log message.)

### Verified

- Guard: a synthetic **suspended** rider (blocked, *has* logged in,
  2 years old) is **not** eligible; a synthetic **unapproved
  application** (blocked, never logged in, 2 years old) **is** — the
  exact discrimination the rework exists for.
- Cascade: purging the stale application deleted the account **and**
  its webform submission via 0044's hook (no orphans), leaving the
  suspended rider untouched.
- Config migration ran (`updb`): keys are now
  `contact_messages: 365`, `stale_registrations: 365`; exported,
  `config:status` clean; phpcs clean.
- Status page renders both tables; the live policy page shows the new
  clause and no longer mentions membership records.
- All synthetic accounts removed afterwards.

## Related
- [[shh-stables-platform]]
- [[0006-gdpr-data-retention-policy]]
- [[0044-immediate-account-deletion-anonymisation]]
- [[0045-remove-rider-membership-module]]
- [[0026-rider-account-access-policy]]
