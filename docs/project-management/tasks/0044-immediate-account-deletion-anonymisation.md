---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-11
updated: 2026-07-11
---
# Task: Immediate account deletion and PII anonymisation

## Description

Client policy decision (2026-07-11): when a rider account is deleted, all
account-type records belonging to that user must be **immediately and
permanently deleted**, and any other records that reference the deleted
account must be **anonymised in place** — retaining the operational
record but replacing all personally identifiable information with a
"Deleted user" placeholder.

This resolves two of the six open questions from
[[0006-gdpr-data-retention-policy]]:
- **`closed_accounts` grace period** → 0: no grace period. Deletion is
  immediate and complete.
- **Orders/5-year Bookkeeping Act** → N/A: all invoicing and financial
  reporting are handled by a dedicated external accounting system. Commerce
  orders on this platform are workflow records, not accounting records;
  no platform-side retention category is needed for them.

The remaining 0006 open items (waiver window from insurer, contact-message
window, membership/log windows, minimum rider age, provider names) are
unchanged.

## What "account records" means

| Entity | Action | Rationale |
|--------|--------|-----------|
| `shh_rider_membership` | Hard-delete all for that uid | Personal eligibility records |
| `shh_facility_credit` + `shh_facility_credit_transaction` | Hard-delete all for that uid | Personal balance + transaction history |
| `webform_submission` (all forms) | Hard-delete all for that uid | Waiver PII; contact messages |
| `shh_booking_log` entries | Anonymise: null `actor`, keep slot/state data | Operational audit trail retained |
| `commerce_order` | Anonymise: `uid → 0`, `mail → ''`, billing profile deleted | Workflow record retained; PII removed |
| `commerce_profile` (billing) | Hard-delete all linked to those orders | Name/address PII |

## What "anonymised" means in practice

Booking log entries: the facility, slot times, and state transition (the
operational fact) are kept; `actor.target_id` is set to NULL. The list
builder renders NULL actors as "Deleted user" — the actor kind (customer/
staff/system) still shows, so the trail remains meaningful without
identifying the person.

Commerce orders: `uid` is set to 0 (anonymous) and `mail` is blanked, so
no name or email remains on the order. The order number, items, and
timestamps are retained for operational purposes (BAT booking references,
booking log order_id references).

## Implementation approach

New module `web/modules/custom/shh_account_deletion`. A single
`hook_user_delete()` implementation calls `AccountDeletionManager::purgeForUser()`,
which runs all the delete/anonymise operations synchronously in that hook.

`hook_user_delete()` fires whenever a user entity is deleted, regardless
of how: admin deleting a user via `/admin/people`, a user cancelling their
own account with the "delete" method (`user_cancel_delete`), or
`shh_data_retention`'s `purgeClosedAccounts()` deleting a blocked
expired-applicant account. The `shh_data_retention` periodic purge
therefore now also triggers full cleanup as a side-effect — the retention
module's per-category `closed_accounts` comment that said "records governed
by other rules keep their own clocks" is no longer accurate for this site.

Self-service deletion note: Drupal's built-in `/user/{user}/cancel` route
exists and supports a "delete the account" method. Configuring that method
site-wide is a separate decision (and potentially a separate task). This
module implements the correct behaviour whenever deletion is triggered;
offering it as a self-service flow is an admin configuration choice.

## Acceptance criteria

- [x] Deleting any rider account (admin-initiated or self-service)
      immediately deletes memberships, credits + transactions, and
      webform submissions for that uid
- [x] Commerce orders for that uid are anonymised: uid → 0, mail → '',
      billing profile deleted; order number/items/timestamps intact
- [x] Booking log entries for that uid are anonymised: `actor` nulled,
      operational fields intact
- [x] The booking log admin screen renders "Deleted user" for anonymised
      entries rather than a blank or a reference to the anonymous account
- [x] Module installed and phpcs clean
- [x] Config exported

## Resolution

See implementation section in project doc.

## Related

- [[shh-stables-platform]]
- [[0006-gdpr-data-retention-policy]]
- [[0002-booking-lifecycle-notifications-audit]]
- [[0018-facility-credit-packs]]
- [[0003-rider-membership-eligibility-workflow]]
