---
type: task
tags: [cms2/task]
status: blocked
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
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
- [ ] Confirm with the client: should riders be able to self-register
      (with or without approval), or is staff manually creating every
      account the intended, permanent model?
- [ ] If self-registration should be enabled: open `register` to
      `visitors` (with or without `register_pending_approval`), add a
      visible "Log in / Register" link to the main navigation, and
      decide whether new self-registered riders start with no
      membership (must still submit the waiver per 0003) or whether
      registration and the waiver should be combined into one step
  - [ ] Verify end to end over real HTTP as a genuinely new,
        never-seen account: register → submit waiver → get approved →
        book
- [ ] If staff-only account creation is confirmed as the permanent
      model: add a visible "Log in" link to the main navigation
      regardless (existing account holders still have no way to find
      it), and make sure [[0027-site-footer-and-contact-link]]'s
      "Contact us" path is the documented way a prospective rider
      requests an account

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0017-anonymous-vs-authenticated-booking-access]]
- [[0027-site-footer-and-contact-link]]
</content>
