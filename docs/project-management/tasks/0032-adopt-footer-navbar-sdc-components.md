---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-08
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
- [x] Decide the rendering mechanism first — **chose custom code**
      (a block plugin per region rendering `#type: component`), the
      same direct-SDC pattern the discovery pages 0019–0023 and
      [[0031-sdc-based-commerce-product-display]] use. Reasoning
      recorded below and in [[0019-canvas-content-templates-for-structured-content]].
- [x] Get the client content the slots need — answered 2026-07-08
      (see Resolution): site-name wordmark (no logo file yet), navbar
      shows site structure relative to the logged-in profile, footer
      carries user-agnostic info (policies, social, contact, address),
      CTAs belong in **page content** not furniture, copyright
      "© {year} Stutteri Hestehøj".
- [x] Replace `shh_site_footer`'s `hestehoj_footer_menu` block with the
      composed `hestehoj:footer`, feeding the `footer` menu (incl.
      0027's "Contact us" link) into the utility-links slot
- [x] Replace `shh_main_navigation`'s `hestehoj_main_navigation` block
      with the composed `hestehoj:navbar` — same session, same
      mechanism, so the site doesn't end up with two different
      page-furniture approaches
- [x] Verify over real HTTP (anonymous + non-admin rider) that nothing
      regresses: main-menu links, footer "Contact us" link (Privacy
      policy link still hidden only because node 1 ships unpublished —
      unchanged, tracked under 0006/0027)

## Resolution (2026-07-08)

Done in the same session as [[0031-sdc-based-commerce-product-display]].

**Mechanism decided: custom code, not Canvas page regions.** Each
region is a block plugin rendering the theme's slotted SDC via
`#type: component` — `ShhNavbarBlock` (`hestehoj:navbar`) and
`ShhFooterBlock` (`hestehoj:footer`). This is the pattern 0031 and the
0019–0023 discovery pages already use, so the site keeps *one*
SDC-composition approach rather than introducing Canvas page-region
composition as a second one (which 0017's Canvas/`commerce_promotion`
collision flagged as carrying real setup risk, and which nothing on
this theme is wired for). Menus render through the theme's own
`menu--main.html.twig` / `menu.html.twig`, so per-user access
filtering is preserved — verified the "Log in / Register" link still
hides itself for an authenticated rider.

**Client slot direction (2026-07-08)** shaped the composition:
- **Branding:** site-name wordmark (the theme's own branding markup;
  no logo file exists yet — drop one in later with no code change).
- **Navbar:** shows site structure relative to the logged-in profile
  (= the access-filtered `main` menu). **CTA slot deliberately
  empty** — the client's direction is that calls-to-action live in
  page *content*, not page furniture; leaving the slot unset would
  also have rendered the component's example.com placeholder buttons.
- **Footer:** user-agnostic info only — wordmark + the stable's
  address & email (read **live from the default Commerce store**, the
  single existing source, rather than a hand-copied duplicate) +
  social links; the `footer` menu (Privacy policy + 0027's Contact
  us) in the utility-links slot; computed "© {year} Stutteri
  Hestehøj"; CTA slot empty.
- **Social:** a new `social` menu created in `shh_site_footer`'s
  install hook with **placeholder** Facebook/Instagram URLs
  (`facebook.com/stutterihestehoj`, `instagram.com/stutterihestehoj`)
  — **client to confirm the real profile URLs** (open item; they're
  content, so staff can edit without a deploy). The theme's
  `menu--social.html.twig` renders them.

**Install-hook changes** (both idempotent, safe over the pre-0032
state): `shh_main_navigation` deletes the legacy
`hestehoj_main_navigation` system-menu block if present and places
`hestehoj_navbar` (plugin `shh_navbar`); `shh_site_footer` likewise
swaps `hestehoj_footer_menu` → `hestehoj_footer_sdc` (plugin
`shh_footer`) and ensures the `social` menu + links. Verified with a
real uninstall/reinstall of both modules: old blocks gone, new blocks
in `header`/`footer`, social menu present.

Verification over real HTTP:
- **Anonymous** homepage: navbar renders (`class="navbar"`), all three
  main links + "Log in / Register" present; footer shows wordmark,
  address (Tobjergvej 27B, 4300 Holbæk), `info@stutteri-hestehoj.dk`,
  Facebook/Instagram, Contact us, "© 2026 Stutteri Hestehøj"; **no**
  example.com placeholder buttons leaked.
- **Authenticated non-admin** (`shh_test_rider`): navbar hides "Log in
  / Register" (access filtering intact) while keeping the nav menu;
  footer renders identically.
- All discovery pages + both product pages 200; phpcs clean; no new
  watchdog errors.

## Addendum (2026-07-13): builder attribution in the footer

Client request: a "Made with ♥ by verdigris.nu with a sprinkle of ✦"
line. Added to `ShhFooterBlock`'s `footer_utility_last` slot, as a
second line under the copyright (right-aligned on desktop, stacked on
mobile), linking to `https://verdigris.nu/`. The copyright line above
it carries `md:text-right` for the same treatment (client nitpick,
2026-07-13) — `md:`-prefixed on both, so mobile keeps its natural
left alignment.

Decisions worth knowing:

- **Phosphor icons, not emoji** (`heart` + `sparkle` — the client
  chose sparkle over robot for the AI mark). The theme already vendors
  the Phosphor pack (MIT, local — no CDN, per task 0009), and icons
  inherit `currentColor`, so they take the footer's own text colour in
  light and dark alike. Emoji glyphs render differently on every OS and
  cannot be recoloured.
- **Core's `#type => 'icon'` render element, not the theme's
  `hestehoj:icon` SDC.** The SDC wraps its SVG in a `<div
  class="min-w:…">` — block-level, which shatters an inline sentence.
  The core element emits the bare `<svg>`, so the icons sit between
  words as intended. (Two layout bugs were caught by screenshotting
  rather than trusting the markup: the first attempt made the
  attribution a flex container, which turned every phrase into its own
  wrapping column, and put it *beside* the copyright rather than under
  it, because the slot's own wrapper is `md:flex md:justify-end` and
  therefore lays its children in a row. Fixed by giving that slot a
  single `flex-col` child.)
- **Accessibility**: the SVGs are decorative (`aria-hidden`), each
  followed by a `visually-hidden` word, so a screen reader hears "Made
  with **love** by verdigris.nu with a sprinkle of **AI**" rather than
  silence or "heart sparkle".
- **SHH only, by construction**: it lives in the `shh_site_footer`
  block plugin, not in the theme's footer SDC — the other sites compose
  their footers through SDC configuration and are untouched.

Verified over real HTTP and in a real browser at desktop (1280px) and
mobile (390px) widths. phpcs clean; no config change (the block plugin
is code).

**Deliberately not done** (recorded for follow-up, not blocking):
- Real social URLs — pending client (placeholders live meanwhile).
- The leaflet/OpenStreetMap map embed for the address the task
  description floats as an eventual enhancement — not built; the
  address renders as a plain `<address>` block, a clean insertion
  point later.
- The footer "Privacy policy" link is still invisible only because
  node 1 ships **unpublished** (a content decision under 0006/0027),
  unchanged here.

**Config:** both new blocks, the `social` menu, and the three
enabled modules exported to `config/shh/sync`; Canvas auto-derived
component entries for the new block plugins (expected, harmless). The
social menu *links* are content (created by the install hook), like
every other menu link on this site.

## Related
- [[shh-stables-platform]]
- [[0019-horse-catalog-page]]
- [[0027-site-footer-and-contact-link]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0031-sdc-based-commerce-product-display]]
- [[0003-canvas-for-page-building]]
- [[0004-sdc-component-architecture]]
