---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-06
---
# Task: Rider membership/eligibility workflow

## Description
Rider currently has a vague "eligibility" field. Needs a real workflow: waiver
submission → staff approval → active membership → expiry. Booking add-to-cart
must check this state (ties into 0017).

## Acceptance criteria
- [x] Membership content/entity with states: pending, active, expired, revoked
- [x] Waiver capture (Webform, per 0009) linked to membership record
- [x] Add-to-cart access check blocks non-active riders with clear messaging

## Resolution (2026-07-06)

**Correction to the task's own framing**: there was no existing "vague
eligibility field" to extend — a direct config search found no
rider/membership/eligibility field anywhere on the site (no custom user
fields at all). This was built from scratch.

New custom module `web/modules/custom/shh_rider_membership`:

- **`Membership` entity** — plain content entity, base fields only (same
  established pattern as `shh_facility_credits`' `FacilityCredit`, per
  that task's own recommendation to default to this for future
  ledger/record-style data): `uid`, `status`
  (pending/active/expired/revoked), `waiver_submission` (reference to
  the webform submission), `approved`, `expires`, `notes`. A rider gets
  a **new** entity per waiver submission rather than editing one record
  back to "pending" — full history of every waiver ever submitted, not
  just current status.
- **`shh_rider_waiver` webform** — created programmatically in
  `hook_install()` (not a config/install YAML export: a full webform
  config has a large, easy-to-get-wrong schema, so building it via the
  entity API and letting Webform's own defaults fill in the rest avoids
  that, the same caution this project has applied to other config-heavy
  entities). Submission restricted to `authenticated` (matches decision
  0017), auto-fills the rider's display name.
- **`hook_webform_submission_insert()`** creates a pending `Membership`
  the moment the waiver is submitted — unless the rider already has a
  pending/active one, preventing duplicate records from a resubmission.
- **Staff approval**: `MembershipForm` (a plain status dropdown is
  enough UI here) + `MembershipListBuilder` at
  `/admin/people/rider-memberships`, gated by a new `administer rider
  memberships` permission. Setting status to Active runs it through
  `MembershipManager::approve()`, which stamps the approval time and
  computes the expiry date (approval + `shh_rider_membership.settings:
  validity_days`, default 365) automatically — **only** if not already
  set, and **only** computed in code, never as an editable form field
  (see "Bug found" below for why).
- **`hook_cron()`** sweeps active memberships past their expiry date to
  "expired" (`MembershipManager::autoExpireStale()`) — flips the stored
  status rather than only computing eligibility dynamically, so staff
  see an accurate status without cross-referencing the expiry date
  themselves.
- **The actual booking gate**: `hook_form_bee_add_reservation_form_alter()`
  on bee's `AddReservationForm`. Two layers: a `#validate` handler is
  the real hard block (the same technique `shh_facility_slots` already
  uses on this exact form — a plain Form API callback, since bee's form
  is a plain `FormBase`, not a `ContentEntityForm`, so the
  `commerce_order.availability_checker` service pattern used in
  `shh_horse_sale_state` for horse purchases doesn't apply here — that
  pipeline is never invoked for this form); hiding the submit button and
  showing a specific reason inline (different wording for "never
  submitted" / "pending" / "expired" / "revoked", with a
  self-service resubmit link offered for every case **except**
  revoked — that one is a staff decision to reverse, not something a
  new waiver should route around) is a UX nicety on top, not the
  security boundary.

**Bug found and fixed during verification**: the first version made
`approved`/`expires` editable base fields on the staff form
(`datetime_timestamp` widget), intending `MembershipManager::approve()`
to fill them in automatically only if empty. An **empty
`datetime_timestamp` widget does not submit NULL** — it silently
defaulted to the current request time for both fields independently,
so `approve()`'s "only set if empty" check found them already
"non-empty" and left both equal to the save timestamp instead of
`expires` being `approved + validity_days`. Fixed by removing them from
the form display entirely (view-only) — they are now genuinely
system-computed, matching the original intent.

**Verified end to end over real HTTP** with the same non-admin test
rider account used in 0025 (`shh_test_rider`): blocked from booking with
no membership record (forged POST rejected, not just a hidden button);
submitted the waiver and got a pending-review message; staff-approved
via the admin form and confirmed `expires = approved + 365 days`
exactly; booking succeeded immediately after approval (real order
placed). Also verified the expired path (cron correctly flips status
and the rider sees a renew-with-link message) and the revoked path
(rider sees a "contact us" message with **no** resubmit link, and a
forged POST is still rejected).

## Related
- [[shh-stables-platform]]
- [[0017-anonymous-vs-authenticated-booking-access]]
- [[0018-facility-credit-packs]]
- [[0025-facility-booking-cta]]
