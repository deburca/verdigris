---
type: task
tags: [cms2/task]
status: backlog
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
- [ ] Decide: is an auto-created account for a horse *buyer*
      acceptable without approval (they can't book anyway without a
      membership), or must every account go through approval?
- [ ] If approval must be universal: either keep checkout-registration
      permanently disabled (add a comment/config note where a staff
      member would enable it, or a form_alter hiding the option), or
      subscribe to the account-creation event and set new
      checkout-created accounts to blocked/pending like self-registered
      ones
- [ ] Verify over real HTTP whichever path is chosen (including that
      an anonymous horse purchase still completes normally)

## Related
- [[shh-stables-platform]]
- [[0026-rider-account-access-policy]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0018-separate-order-types-horse-vs-booking]]
