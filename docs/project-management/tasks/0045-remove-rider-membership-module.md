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
# Task: Remove rider membership module

## Description

Client clarification (2026-07-11): facility use is covered by a paper
contract signed off-site. Staff activate a rider's account **only after
the paper contract has been signed**. Account activation = liability
document signed — the two are the same event.

This makes `shh_rider_membership` entirely redundant:

- The module was built to gate booking on a separately signed digital
  waiver. The liability instrument is now the paper contract, not the
  webform submission.
- The existing account-approval flow (`shh_rider_registration`,
  `register: visitors_admin_approval`) already implements the correct
  model: rider self-registers → blocked pending → staff activate (after
  paper contract signed) → rider gets "approved" email → can book.
- There is no second gate. An active, authenticated account = paper
  contract signed = can book.

The `shh_rider_waiver` webform is likewise redundant. Emergency contact
information is collected on the paper contract; the site will not hold it.

## What is removed

| Item | Module | Reason |
|------|--------|--------|
| `shh_rider_membership` entity type | shh_rider_membership | No memberships to track |
| `MembershipManager` service | shh_rider_membership | Replaced by account status |
| `shh_rider_waiver` webform | shh_rider_membership (hook_install) | Paper contract replaces it |
| Booking-form validate hook (membership gate) | shh_rider_membership | Gate is now account status |
| Dashboard membership status section | shh_rider_dashboard | No membership to show |
| `waiver_submissions` retention category | shh_data_retention | No waiver data on platform |
| `membership_records` retention category | shh_data_retention | No membership data on platform |
| Privacy policy waiver/membership bullets | PRIVACY POLICY.md | Paper contract, off-site |

## What stays

- `shh_rider_registration` — the self-registration + admin-approval flow
  is exactly right for the new model. No changes.
- All facility booking, horse sales, and credit-pack modules — unaffected.
- `shh_account_deletion` — no memberships to delete means that step
  becomes a no-op; the method is removed rather than left as dead code.
- `shh_data_retention` — the two moot categories are pruned; the module
  itself stays for the remaining categories (contact_messages, confirmed
  at 365 days; closed_accounts, booking_log, and waiver windows once the
  insurer question is resolved).

## Acceptance criteria

- [x] `shh_rider_membership` module uninstalled and removed
- [x] `shh_rider_waiver` webform deleted
- [x] `shh_rider_dashboard` no longer injects or displays membership status
- [x] `shh_account_deletion` no longer references membership deletion
- [x] `shh_data_retention` membership_records + waiver_submissions pruned
- [x] Privacy policy updated: waiver bullet removed; account records
      bullet reflects paper-contract model
- [x] phpcs clean; config exported

## Related

- [[shh-stables-platform]]
- [[0006-gdpr-data-retention-policy]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0026-rider-account-access-policy]]
