---
tags: [cms2/infrastructure, cms2/notes]
site: shh
created: 2026-07-06
updated: 2026-07-06
priority: high
---

# shh — Account Access & Site Housekeeping Gap Analysis

Walking the site again as a real visitor now that the discovery-page
backlog (0019–0023) is done, the same way
[[shh-customer-facing-pages]] and [[shh-rider-journey-gap-analysis]]
did for the previous two rounds. This time the core booking/sales
*mechanics* all work correctly (verified repeatedly across 0001–0025);
what's missing now is almost entirely about **getting a real visitor
into an account in the first place**, plus a handful of smaller
consistency gaps the last two rounds of work surfaced or left behind.

## 🔴 Critical: a brand-new rider cannot create an account at all

Confirmed directly:
- `user.settings: register: admin_only` — site-wide, on **all three
  sites** (verdigris, kragebaekgaard, shh checked for comparison), so
  this is a Drupal CMS default nobody has revisited yet, not an
  shh-specific misconfiguration.
- `/user/register` → real `403`.
- `/user/login` has no "Create new account" tab at all (core only
  shows it when registration is open to visitors).

Combined with the fact that **every facility-booking and membership
feature built this session (0003, 0017, 0024's sibling 0025, etc.)
requires being authenticated first**, walking the actual journey end
to end as a brand-new visitor goes:

`/facilities` → a facility page → **"Book now"** → `403`, redirected
to the login form → **dead end** — there is no way to get an account
from here, and no indication of what to do instead.

Horse sales are unaffected — decision 0017 already made those
anonymous-purchasable, and that path was verified working end to end
in 0019/0024.

This is a **business decision, not an engineering one** — it's
entirely plausible a real stables business *wants* to vet every rider
personally before creating an account (matches "admin_created" already
being enabled), the same way [[0005-tax-classification-horses-vs-bookings]]'s
VAT margin-scheme question needed a real client answer rather than an
assumption. Tracked as
[[0026-rider-account-access-policy]].

## 🔴 Critical (a direct consequence of the above): no way to ask for help either

There is genuinely no path forward for a blocked visitor, because:
- **No login link anywhere in the UI** at all — not in the (newly
  added) main navigation, not anywhere else. Even an *existing*
  account holder has no discoverable way to find `/user/login` short
  of already knowing the URL or hitting the booking-form 403 redirect.
- **No footer renders anywhere on the site.** A `footer` menu already
  exists in config (2 links: "Privacy policy", "My privacy settings"),
  same as `main` before [[0019-horse-catalog-page]]'s
  `shh_main_navigation` fix — but nothing has ever placed a block for
  it either. This is also the natural home for a "Contact us" link.
- **No visible "Contact us" path anywhere.** The pre-existing
  `/form/contact` webform (decision 0009) is never linked from any
  page, nav, or footer — a visitor turned away at the login wall has
  no way to ask staff for an account.

Tracked as [[0027-site-footer-and-contact-link]] — small, clearly
actionable, no business decision needed (mirrors the exact pattern
already used for `shh_main_navigation`).

## 🟡 Rider dashboard doesn't surface membership status

`/user/{user}/bookings` (0022) shows bookings, deposits, and credits,
but not the rider's own membership state (none / pending / active /
expired / revoked) — exactly the information a blocked rider would
want to see in one place, rather than only discovering it by attempting
a booking and reading the message on the reservation form itself
(0003). Tracked as
[[0028-rider-dashboard-membership-status]].

## 🟡 Cancel flows return to the homepage, not the rider dashboard

`CancelBookingForm` (0015) and `CancelDepositForm` (0001) both
redirect to `<front>` after a successful cancellation — both predate
0022's dashboard, which didn't exist yet when they were built. Now
that `/user/{user}/bookings` is the natural "my stuff" hub, returning
there after a cancellation (rather than the homepage) would close the
loop properly. Tracked as
[[0029-cancel-flow-dashboard-redirect]].

## 🟢 Minor: breadcrumb inconsistency on the new pages

Node pages (e.g. `/oval-track`) show a "Home" icon crumb before the
current page title. The five new custom-controller pages (`/horses`,
`/facilities`, `/pricing`, `/user/{id}/bookings`) show only the bare
page title, no parent crumb — a small, cosmetic inconsistency, not
tracked as its own task (low enough priority to fold into whichever
future front-end polish pass touches these pages next).

## 🟢 Minor: no proactive "you'll need an account" messaging

Neither `/facilities` nor an individual facility page mentions that
booking requires an account (and, once 0026 is resolved, whatever the
actual sign-up path turns out to be) — a rider only finds out by
clicking "Book now" and hitting the wall. Worth revisiting once 0026
is resolved (the right message depends on what the actual sign-up path
turns out to be), not a separate task on its own.

## 🟢 Housekeeping (found incidentally, unrelated to shh business logic)

- **Sample catalog exhausted**: both sample horses (task 0014) are now
  marked unavailable — Freja is `reserved-deposit` (from 0022's real
  test deposit purchase) and Þór is `sold` (from 0024's real test
  purchase) — so `/horses` currently shows its empty state. Not a bug
  (the catalog is correctly reflecting real, deliberately-created test
  transactions from earlier verification work), but worth knowing
  before demoing the site, since there's currently nothing to show.
- **Node 2 ("Test Page", the stock `page` content type sample) has a
  data-integrity bug unrelated to anything built this session**: its
  `node_revision` table has *two* rows both flagged
  `revision_default = 1` for the same node, which makes entity storage
  fail to load it at all (`\Drupal::entityTypeManager()->getStorage('node')->load(2)`
  returns `NULL`), producing a `404` on both `/node/2` and its alias
  `/test-page`. Found only because generic `page`-type content was
  checked for regressions from the `templates/content/{field,node}.html.twig`
  overrides added while fixing the shh display-styling issue — this
  content type isn't shh-specific and this node isn't linked from
  anywhere on the site, so it's noted here rather than given its own
  task, but it's a genuine data corruption worth a look if anyone
  touches that node.

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[shh-rider-journey-gap-analysis]]
- [[0003-rider-membership-eligibility-workflow]]
- [[0017-anonymous-vs-authenticated-booking-access]]
- [[0019-horse-catalog-page]]
- [[0022-rider-dashboard]]
- [[0026-rider-account-access-policy]]
- [[0027-site-footer-and-contact-link]]
- [[0028-rider-dashboard-membership-status]]
- [[0029-cancel-flow-dashboard-redirect]]
</content>
