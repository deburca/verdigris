---
type: task
tags: [cms2/task]
status: done
priority: high
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
branch: site/hvg/landing-and-demo-accounts
---
# Task: Build and launch hivelog.eu (hvg)

## Description
Stand up the hvg multisite as a public demo for the HiveLog module and the
Viculum iOS app, with a Canvas landing page, self-service demo accounts and
an interest form for hosting and sensors. Design rationale and the
alternatives that were rejected are in
[[0022-hivelog-eu-demo-site-architecture]]; this note records what shipped.

## Acceptance criteria
- [x] hvg registered as a multisite (`sites.php`, DDEV hostname, `hvg-*`
      Makefile targets; deliberately not in `all-push`/`all-pull`)
- [x] Beeswax as the default theme (release 1.0.4 fixed two bugs found on
      this site: link colour overriding Canvas buttons; font preload 404s)
- [x] `hvg_landing`: Canvas landing page at `/home` (hero, feature cards,
      screenshot carousel, Web and iOS, Coming next, interest form), seeded
      on first import, plus a `carousel` Canvas component
- [x] `hvg_demo`: `/demo/start` signup, `demo` + `hivelog_user` roles,
      cron deletion of expired accounts and everything they own
- [x] `authenticated` role stripped of Drupal CMS admin defaults
- [x] `interest` webform with inline confirmation and a notification email
- [x] First deploy rehearsed on an empty database
      (`site:install --existing-config`) before production
- [x] Live on hivelog.eu (2026-10-09); landing page and demo signup
      verified in production

## Implementation notes
- Branch `site/hvg/landing-and-demo-accounts`, merged as PR #6 (verdigris
  repo); follow-up commits pushed straight to `main`.
- Production problems met on the way, all resolved: geofield module
  directory incomplete (fixed with `composer reinstall`); interest form
  showing a render error when submitted inside the Canvas block (fixed
  with inline confirmation); notifications delivered to Junk (see
  [[0071-hvg-site-mail-delivery]]).
- Not yet done and tracked separately: see the other tasks in
  [[hivelog-eu-demo-site]].

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
