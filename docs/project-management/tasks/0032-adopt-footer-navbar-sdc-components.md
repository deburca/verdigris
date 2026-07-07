---
type: task
tags: [cms2/task]
status: backlog
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-07
---
# Task: Adopt the theme's slotted `footer` (and `navbar`) SDC components

## Description
The hestehoj theme ships elaborate slotted SDC components for both
page furniture regions — `hestehoj:navbar` (logo/navigation/CTA slots)
and `hestehoj:footer` (four slots: "Branding & Social", "Call to
action", "Utility links", "Copyright text") — evidently intended to be
composed via Canvas's page-region UI, which has never been set up for
this theme. Both [[0019-horse-catalog-page]] (`shh_main_navigation`)
and [[0027-site-footer-and-contact-link]] (`shh_site_footer`)
deliberately skipped these components and placed plain system menu
blocks instead: the minimal standard-Drupal fix for the actual gap
(no block had ever been placed in either region), avoiding two things
neither task could answer — what content fills the slots (logo, social
links, CTA, copyright wording: client/content decisions), and what
mechanism renders the composition on every page (Canvas page regions:
a platform setup task with known Canvas risk, see 0017's
`commerce_promotion` collision).

Nothing is lost while this waits: the `main`/`footer` menus are
content that carries over into the SDCs' navigation/utility-links
slots whichever way this lands, and the two placeholder blocks delete
cleanly.

## Acceptance criteria
- [ ] Decide the rendering mechanism first: Canvas page-region
      composition (investigate what enabling it for hestehoj actually
      requires, and whether it plays well with the per-page Canvas
      content composition already in use) vs. custom code (a block
      plugin or preprocess rendering `#type: component`, the same
      direct-SDC pattern the discovery pages 0019–0023 and
      [[0031-sdc-based-commerce-product-display]] use) — record the
      choice and reasoning
- [ ] Get the client content the slots need: logo/branding usage,
      social links (if any), footer CTA (if any), copyright line
- [ ] Replace `shh_site_footer`'s `hestehoj_footer_menu` block with the
      composed `hestehoj:footer`, feeding the `footer` menu (incl.
      0027's "Contact us" link) into the utility-links slot
- [ ] Replace `shh_main_navigation`'s `hestehoj_main_navigation` block
      with the composed `hestehoj:navbar` — same session, same
      mechanism, so the site doesn't end up with two different
      page-furniture approaches
- [ ] Verify over real HTTP (anonymous + non-admin rider) that nothing
      regresses: main-menu links, footer "Contact us" link, and — once
      published — the "Privacy policy" link

## Related
- [[shh-stables-platform]]
- [[0019-horse-catalog-page]]
- [[0027-site-footer-and-contact-link]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0031-sdc-based-commerce-product-display]]
- [[0003-canvas-for-page-building]]
- [[0004-sdc-component-architecture]]
