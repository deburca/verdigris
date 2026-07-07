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
# Task: Site footer with a "Contact us" link

## Description
Found via [[shh-account-access-gap-analysis]]: no footer renders
anywhere on the site. A `footer` menu already exists in config
("Privacy policy", "My privacy settings") but — the same gap
`shh_main_navigation` found and fixed for the `main` menu in
[[0019-horse-catalog-page]] — nothing has ever placed a block to
display it. This also means the pre-existing `/form/contact` webform
(decision 0009) is never linked from anywhere on the site at all,
leaving a visitor with no way to ask staff a question, including —
critically — a prospective rider turned away at the account/login wall
found in [[0026-rider-account-access-policy]].

## Acceptance criteria
- [x] Place a "Footer" system menu block (or equivalent) in the
      hestehoj theme's `footer` region, mirroring
      `shh_main_navigation`'s approach for the `header` region
- [x] Add a "Contact us" link (to `/form/contact`) to the `footer` menu
- [x] Verify over real HTTP that the footer — and the contact link —
      actually render on a real page, not just exist in menu config

## Resolution (2026-07-07)

New custom module `web/modules/custom/shh_site_footer`, deliberately a
near-exact mirror of `shh_main_navigation` (same two-file
info.yml + hook_install() shape, same idempotency guards):

- **"Contact us" menu link** added to the existing `footer` menu
  (`internal:/form/contact`, weight 10 so it sits after "Privacy
  policy"'s weight 0), guarded by a `loadByProperties()` check so
  reinstalling can't duplicate it. Confirmed before wiring the link
  that `/form/contact` really is the `contact` webform's URL and
  returns `200` anonymously.
- **Block `hestehoj_footer_menu`** (`system_menu_block:footer`, label
  hidden) placed in the hestehoj theme's `footer` region. The theme
  was already fully prepared on its side — `page.html.twig` wraps
  `page.footer` in a styled `<footer role="contentinfo">`, and the
  generic `templates/navigation/menu.html.twig` renders a clean flat
  link list — the region had just never had a block placed in it,
  exactly the `main`-menu gap 0019 found. As with 0019's decision to
  skip the theme's slotted `navbar` SDC, the theme's more elaborate
  `footer` SDC component (logo/columns slots, presumably meant for
  Canvas page-region composition, not set up for this theme) was
  deliberately not taken on here.

**Verified over real HTTP**: before enabling, the front page had no
`<footer role="contentinfo">` at all (grep count 0 — the exact "before"
state the gap analysis described). After `drush en shh_site_footer` on
the shh site: the footer with the working "Contact us" link renders
anonymously on `/` (front), `/facilities`, `/horses`, `/pricing`,
`/oval-track`, and `/form/contact` itself, and as the genuine non-admin
`shh_test_rider` account (0025's uid 3, logged in over HTTP) on
`/facilities` and the `/user/3/bookings` dashboard. (One transient
false negative during verification: a grep against a just-logged-in
session's first page load showed 0 — BigPipe streams authenticated
pages in chunks — re-fetching showed the footer present; noted so it
isn't mistaken for a real gap later.)

Two pre-existing findings noted, deliberately not "fixed" here:

1. **The footer menu's "Privacy policy" link doesn't render for
   visitors** — not a block/menu bug: its target (node 1, the stock
   Drupal CMS privacy-policy page) is **unpublished**, so core
   correctly access-filters the link out for anonymous users. It
   appears automatically the moment the client publishes a real
   privacy policy; publishing the unreviewed placeholder text was
   judged a content/business call, not this task's. (Relevant to
   [[0006-gdpr-data-retention-policy]], which will need a published
   privacy policy anyway.)
2. **`/user/login` shows no footer** — that page is rendered by the
   **Gin admin theme** via `gin_login` (a Drupal CMS platform default,
   all three sites), so no hestehoj-region block can ever appear
   there. This matters for [[0026-rider-account-access-policy]]: the
   login wall a turned-away rider actually lands on still has no
   contact path on the page itself — the rider must navigate back to
   any site page to find the footer. Worth folding into 0026's
   implementation (whichever branch the client picks) rather than
   fighting gin_login's theming here.

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0019-horse-catalog-page]]
- [[0026-rider-account-access-policy]]
- [[0009-webform-for-forms]]
</content>
