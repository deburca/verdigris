---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-07
---
# Task: Rider account access policy (self-registration vs. staff-only)

## Description
Found via [[shh-account-access-gap-analysis]]: `user.settings` has
`register: admin_only` site-wide (confirmed on all three sites — a
Drupal CMS default, not an shh-specific misconfiguration), and there is
no "Create new account" option anywhere. Since every facility-booking
and membership feature built this session (0003, 0017, 0024's sibling
0025) requires being authenticated first, a brand-new rider currently
hits a hard dead end: `/facilities` → a facility page → "Book now" →
`403`, redirected to the login form, with no way to create an account
and no indication of what to do instead. Horse sales are unaffected
(anonymous-purchasable per decision 0017).

**This is a business decision, not an engineering one** — matches how
[[0005-tax-classification-horses-vs-bookings]] needed a real client
answer rather than an assumption. It's entirely plausible the client
*wants* to vet every rider personally before creating an account
(`register_admin_created` is already `true`, suggesting deliberate
manual account creation may be the intended model) — but if so, there
currently isn't even a way for a prospective rider to *ask* for one
(see [[0027-site-footer-and-contact-link]]).

## Acceptance criteria
- [x] Confirm with the client: should riders be able to self-register
      (with or without approval), or is staff manually creating every
      account the intended, permanent model? — **Answered 2026-07-07:
      self-registration, with mandatory admin approval.**
- [x] If self-registration should be enabled: open `register` to
      `visitors` (with or without `register_pending_approval`), add a
      visible "Log in / Register" link to the main navigation, and
      decide whether new self-registered riders start with no
      membership (must still submit the waiver per 0003) or whether
      registration and the waiver should be combined into one step
  - [x] Verify end to end over real HTTP as a genuinely new,
        never-seen account: register → submit waiver → get approved →
        book
- [~] ~~If staff-only account creation is confirmed as the permanent
      model~~ — n/a, client chose self-registration

## Resolution (2026-07-07)

Client decision: **self-registration, with mandatory admin approval**
(`register: visitors_admin_approval`). Implemented as new custom
module `web/modules/custom/shh_rider_registration` (the standard
two-file hook_install() pattern): sets the config on shh only (the
other two sites keep the platform's `admin_only` default) and adds a
"Log in / Register" link to the `main` menu pointing at `/user/login`
— core's user.login route requires an anonymous session and menu
links are access-filtered per user, so the link hides itself for
logged-in riders with no custom visibility code. With registration
open, core/gin_login's login page shows the "Create new account" path
onward (verified).

**Waiver deliberately NOT combined with registration** (the AC's open
sub-decision): a self-registered rider starts with no membership and
still goes through 0003's waiver → pending → staff-approval flow.
That means two separate staff checkpoints (account approval, then
membership approval) — chosen because collapsing them would mean
rebuilding 0003's working webform flow inside the registration form
for marginal benefit; revisit only if staff find double approval too
heavy in practice.

**A real Drupal CMS platform bug was found and patched during
verification** — the first test registration produced the *wrong
email*: the applicant received `register_admin_created` ("An
administrator created an account for you… you may now log in", with a
one-time login link that is useless on a blocked, pending-approval
account), and the admin's `register_pending_approval_admin`
notification never went out at all — i.e. staff would never learn
anyone had applied. Root cause (traced through easy_email's send log
after ruling out easy_email_override's mapping, the mail templates,
and core's own branch logic): **`drupal_cms_helper`'s
`form_user_register_form_alter`** (a workaround for upstream
drupal.org/i/3481627, aimed at the *admin-create* form) sets the
admin-only `notify` checkbox's `#default_value` to TRUE
unconditionally; on anonymous self-registration that element is
`#access = FALSE`, and Form API resolves access-denied elements to
their default value — so `RegisterForm::save()` sees `notify=TRUE`
and takes the admin-created branch. Latent in every Drupal CMS site
that opens self-registration; invisible under the shipped
`admin_only` default. Fixed per decision 0006 with a composer patch
(`patches/drupal_cms_helper-scope-register-form-alter-to-admin-create.patch`,
registered under `drupal/drupal_cms_helper`): the whole alter now
early-returns unless the form is the admin-create variant
(`administer_users` form value). Worth reporting upstream to the
Drupal CMS project. After the patch: applicant gets the correct
"pending admin approval" mail, admin gets the notification, on-screen
message is core's correct "pending approval by the site
administrator" text (all three re-verified over real HTTP).

**Verified end to end over real HTTP as a genuinely new account**
(`soren_holm`, uid 5 — never existed before): anonymous visitor sees
"Log in / Register" in the nav → registers via `/user/register` (with
Honeypot's `url`/`honeypot_time` protection active on the form) →
account created blocked, correct pending-approval emails both sent →
staff approval (drush `user:unblock`, standing in for the
`/admin/people` UI) → rider follows the **real one-time login link
from the approval email** in Mailpit → sets a password through the
`/user/{uid}/edit` form → hits `/node/3/add-reservation` and is
correctly gated by 0003's waiver requirement → submits the waiver →
membership 2 auto-created `pending` → staff approves via the real
`/admin/people/rider-memberships/2/edit` form (approved/expires
auto-computed: 2026-07-07/2027-07-07) → books the Oval Track
2026-07-08 09:00–09:30 → completes checkout → **order 6, state
`completed`, 50 DKK** (0020's corrected pricing), with watchdog
confirming `shh_booking_hold` placed and promoted the BAT event.
Every step of the account→waiver→approval→booking pipeline built
across 0003/0012/0016/0020/0025 now works for a rider who starts from
nothing but the public site.

Housekeeping note: `freya_jensen` (uid 4) is the pre-patch test
registration — left in place, permanently blocked/pending, as the
account whose wrong-template email (Mailpit, 15:07) documents the bug;
harmless, delete freely if it clutters `/admin/people`.

**Post-review hardening (2026-07-07, same day)**: a high-effort code
review of this implementation confirmed the composer patch and the
menu-link access mechanism as sound, and surfaced lifecycle gaps in
the module itself — both fixed: `hook_uninstall()` now restores
`register: admin_only` and deletes the "Log in / Register" link
(verified with a real uninstall → state-restored → reinstall cycle),
and both hooks are guarded by a `site.path === 'sites/shh'` check so
enabling the module on another site in this multisite is an explicit
logged no-op (this module has no theme-scoped inertness the way
shh_main_navigation does). Remaining review findings tracked as
[[0033-durable-config-strategy-shh]] (fire-once config vs. any future
config import), [[0034-guest-checkout-approval-policy-alignment]]
(Commerce guest-checkout registration would bypass approval —
currently disabled on both flows), and
[[0035-shh-install-hook-cleanup]] (shared menu-link helper, patch
comment size, duplicated prose, upstream bug report).

Related follow-ups: 0027's finding stands — the gin_login-rendered
`/user/login` and `/user/register` pages are admin-themed, so they
show no site footer/nav; acceptable now that the nav link and the
register path exist, revisit only if riders get lost in practice.
The gap analysis's minor "no proactive 'you'll need an account'
messaging on facility pages" item also remains open (fold into any
future front-end polish pass).

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0017-anonymous-vs-authenticated-booking-access]]
- [[0027-site-footer-and-contact-link]]
</content>
