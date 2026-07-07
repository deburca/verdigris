---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-07
---
# Task: Keep Commerce guest-checkout account creation aligned with the rider approval policy

## Description
Code-review finding on [[0026-rider-account-access-policy]]'s
implementation: the "mandatory admin approval" policy only governs
the `/user/register` form. Commerce's guest-checkout registration
path (`GuestCheckoutCompletionSubscriber`) creates **active** accounts
directly — `status => TRUE`, the `user.settings: register` value is
never consulted — so it would bypass account approval entirely.

**Currently latent, not live**: verified in live config that both shh
checkout flows (`horse_sale` and `default`) have
`allow_registration: false` on the login pane and the
completion-register pane disabled, so no account is created via
checkout today. The trap is that enabling "create an account after
guest checkout" on the horse_sale flow is an innocuous-looking
checkbox a staff member could tick — and every anonymous horse buyer
would then get a fully active, never-approved account, inconsistent
with the gate every self-registered rider passes. (Booking is still
separately gated by 0003's membership requirement, so the blast
radius is account existence, not booking access — but two
account-creation paths with different approval rules is still wrong.)

## Acceptance criteria
- [x] Decide: is an auto-created account for a horse *buyer*
      acceptable without approval (they can't book anyway without a
      membership), or must every account go through approval?
- [x] If approval must be universal: either keep checkout-registration
      permanently disabled (add a comment/config note where a staff
      member would enable it, or a form_alter hiding the option), or
      subscribe to the account-creation event and set new
      checkout-created accounts to blocked/pending like self-registered
      ones
- [x] Verify over real HTTP whichever path is chosen (including that
      an anonymous horse purchase still completes normally)

## Related
- [[shh-stables-platform]]
- [[0026-rider-account-access-policy]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0018-separate-order-types-horse-vs-booking]]

## Resolution (2026-07-07)

**Decision (AC 1): approval is universal.** Derived from 0026's client
decision, not a new question: the point of mandatory admin approval is
that staff vet every account holder, and an auto-created ACTIVE account
(name = email, generated password — a full login credential via
password reset) contradicts that regardless of what was purchased.
Investigation showed THREE Commerce paths bypass `user.settings.register`,
not one: `GuestCheckoutCompletionSubscriber` (`guest_new_account` flow
setting), the Login pane's register option, and the CompletionRegister
pane — the two panes even call `user_login_finalize()` right after save.

**Implementation (AC 2):** runtime enforcement in `shh_rider_registration`
(new `.module` file — the module that owns the policy), at the one
chokepoint all paths share instead of form-altering three checkboxes:

- `hook_user_presave()`: any NEW account being created ACTIVE while
  `register == visitors_admin_approval` gets blocked — exempting CLI
  (drush) and sessions with `administer users` (operator paths), and
  naturally skipping core's RegisterForm (already creates blocked).
  Reads the policy live, so it disarms itself if the policy is relaxed
  and is inert on non-shh sites.
- `hook_user_insert()`: sends core's `register_pending_approval`
  notifications (rider + admin copy) for accounts the guard blocked —
  otherwise staff would never learn the account exists.
- `hook_user_login()`: backstop for the two panes' save-then-login —
  ends the just-created session for a blocked account (no core path
  logs blocked users in). Known nit: the messenger warning added there
  dies with the destroyed session, so the pane buyer sees core's
  generic cookie notice — but receives the pending-approval email
  seconds later.

**Verified over real HTTP, three full anonymous horse purchases**
(variation 3 flipped to available per test; 0024's subscriber flipped
it back to sold on each completed order — self-restoring):

1. **guest_new_account on** (order 37/HS-4): checkout completed
   normally; account uid 6 created **blocked** and order assigned to
   it; watchdog notice; pending-approval emails to buyer + admin; login
   with a known (drush-set) password refused with core's "has not been
   activated or is blocked".
2. **completion_register pane on** (order 38/HS-5): pane registration
   produced a **blocked** uid 7, order assigned; the pane's automatic
   login was immediately ended (watchdog warning; `/user` redirects
   anonymous); pending-approval emails sent.
3. **Control, both off — the live config, restored** (order 39):
   anonymous purchase completes normally, NO account created, order
   stays uid 0. The guard adds nothing to the default path.

Flow config restored to `guest_new_account: false`,
`completion_register: _disabled` afterwards. Test artifacts left in
place: blocked accounts uid 6/7, orders 37–39 (HS-4/5/6-ish), Þór sold.
