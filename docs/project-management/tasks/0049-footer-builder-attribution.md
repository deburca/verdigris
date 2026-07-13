---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-13
updated: 2026-07-13
---
# Task: Builder attribution in the SHH footer

## Description
Client request (2026-07-13): add a builder credit to the footer of
every SHH page, along the lines of *"Made with ♥ by verdigris.nu with
a sprinkle of 🤖"*, with the heart and the AI mark as emoji or icons.

Refined with the client before building:
- **Sparkle, not robot**, for the AI mark.
- **verdigris.nu** links to `https://verdigris.nu/`.
- **SHH only** — the other sites in this repo compose their footers
  through SDC *configuration*, so the change must not touch the shared
  `hestehoj:footer` component.
- Follow-up nitpick: the copyright line above it should be
  right-aligned to match.

## Acceptance criteria
- [x] Attribution renders in the footer of every SHH page, under the
      copyright, linking to `https://verdigris.nu/`
- [x] Heart + sparkle marks render, and adapt to the footer's text
      colour (light and dark)
- [x] Scoped to SHH; the shared footer SDC and the other sites are
      untouched
- [x] Copyright right-aligned on desktop, matching the attribution
- [x] Accessible: the marks are decorative, and the sentence still
      reads sensibly to a screen reader
- [x] Verified in a real browser at desktop and mobile widths

## Resolution (2026-07-13)

Added to `ShhFooterBlock`'s `footer_utility_last` slot (task 0032's
block plugin) as a second line under the copyright:

> © 2026 Stutteri Hestehøj
> Made with ♡ by **verdigris.nu** with a sprinkle of ✦

**Phosphor icons, not emoji.** The theme already vendors the Phosphor
pack (MIT, local — no CDN, per [[0009-vendor-fullcalendar-library]]),
and icons inherit `currentColor`, so they take the footer's own text
colour in light and dark alike. Emoji glyphs render differently on
every OS and can't be recoloured. Icons: `heart` + `sparkle` (the
client's choice over `robot`).

**Core's `#type => 'icon'` element, not the theme's `hestehoj:icon`
SDC.** The SDC wraps its SVG in a `<div class="min-w:…">` — block
level, which shatters an inline sentence. Core's element emits the
bare `<svg>`, so the marks sit *between the words* as intended.

**SHH-only by construction**: it lives in the `shh_site_footer` block
plugin, not the shared `hestehoj:footer` SDC, so the sites that
compose footers via SDC configuration are unaffected.

**Accessibility**: the SVGs are decorative (`aria-hidden`), each
followed by a `visually-hidden` word, so a screen reader hears "Made
with **love** by verdigris.nu with a sprinkle of **AI**" rather than
silence or "heart sparkle".

**Alignment**: both the attribution and (per the client's nitpick) the
copyright carry `md:text-right` — `md:`-prefixed on both, so the
single-column mobile footer keeps its natural left alignment and only
the two-column desktop layout right-aligns.

**Two layout bugs caught by screenshotting rather than trusting the
markup** — the HTML looked perfectly correct in both cases:
1. Making the attribution a flex container turned *every phrase* into
   its own flex item, so the sentence wrapped into ragged columns
   ("Made with" / ♡ / "by verdigris.nu" / …).
2. It rendered *beside* the copyright rather than under it, because the
   slot's own wrapper is `md:flex md:justify-end` and therefore lays
   its children in a row. Fixed by giving that slot a single
   `flex-col` child containing both lines.

**Verified**: present on all six checked pages (front, `/horses`,
`/feed`, `/product/1`, `/oval-track`, `/privacy-policy`); rendered in
a real headless browser at 1280px (both lines flush right, one line
each) and 390px (left-aligned, no overflow). phpcs clean. No config
change — the block plugin is code.

### Filled red heart (client, 2026-07-13)

The client asked for a **filled heart, in red**. The theme's Phosphor
pack ships **regular weight only** — no `-fill` variants at all — so
`heart-fill.svg` was **vendored** into
`web/themes/custom/hestehoj/icons/phosphor/` from
[phosphor-icons/core](https://github.com/phosphor-icons/core)
(`assets/fill/heart-fill.svg`, MIT — the same pack and licence the
theme's `hestehoj.icons.yml` already declares, so no new licence
obligation and no CDN, per [[0009-vendor-fullcalendar-library]]).
Identical format to the existing icons (`viewBox="0 0 256 256"`,
`fill="currentColor"`, single path), so the pack's `{icon_id}.svg`
source pattern picks it up with no config change.

**Red via `text-primary`, not a hardcoded colour.** The theme's
`--primary` is `--iceland-red` in **both** the light and dark palettes
(the Icelandic-flag colour scheme), so the heart is the site's own red
and stays correct in dark mode. The wrapping span sets the text colour
and the SVG's `fill="currentColor"` does the rest. The sparkle keeps
the footer's text colour deliberately — one focal point, not two.

### Flag-coloured pair (client, 2026-07-13)

Follow-up: since the site is built on the Icelandic flag scheme and the
heart is now red, the AI mark should be **Icelandic blue** to match. So
both marks are now **filled** variants in the **flag's two colours**:

| Mark | Icon | Class | Token |
|---|---|---|---|
| Heart | `heart-fill` | `text-primary` | `--iceland-red` |
| Sparkle | `sparkle-fill` | `text-accent` | `--iceland-blue` |

`sparkle-fill.svg` was vendored alongside `heart-fill.svg` from the same
MIT Phosphor source. Both tokens are defined in the **light and dark**
palettes, so the pair stays correct in dark mode, and the credit is
coloured by the site's own system rather than hardcoded values.

**A Tailwind gotcha worth remembering**: `text-accent` did **not**
work at first — the sparkle rendered near-black. Tailwind v4 only
compiles the utilities it *finds in source*, and no file had ever used
`text-accent`, so the class simply did not exist in
`build/main.min.css` (`.text-accent-foreground` did, which is what an
over-eager `grep -c ".text-accent"` matched — a false positive that
briefly hid the cause). A `npm run build` generated
`.text-accent{color:var(--accent)}` and the sparkle went blue. Lesson:
when a Tailwind class "does nothing", check it was actually *compiled*
before debugging the markup — and grep for the exact rule, not a
substring.

Verified in the browser: solid red heart, solid blue sparkle, outline
paths gone.

## Also in this change (theme-side)

`npm run build` in the hestehoj theme emitted:

```
Found 1 warning while optimizing generated CSS:
  .bg-\[var\(--btn-\*\)\] { background-color: var(--btn-*);
                                                       ^ Unexpected token Delim('*')
```

Not a code bug — a **scanner artefact**. Tailwind v4 treats every file
under the theme as a source of class candidates, Markdown included, and
the theme's own docs live inside the theme directory. A doc quoting the
arbitrary utility `bg-[var(--btn-*)]` in prose (where `*` is a
human wildcard meaning "any `--btn-…` token") was compiled into real,
invalid CSS. Fixed at the root with `@source not "../docs";` in
`src/main.css` — rather than contorting the prose to dodge the scanner
— which also stops any *future* doc that names a class from injecting
junk utilities. Tracked in the theme's own vault as its task 0008; the
rebuilt `build/main.min.css` ships here.

Incidental good news: the committed CSS build is now Tailwind 4.1.18,
matching the local toolchain, so the version mismatch warned about
during the 2026-07-12 composer update (committed 4.2.1 vs local
4.1.18) has resolved itself — rebuilding the theme CSS is safe again.

## Related
- [[shh-stables-platform]]
- [[0032-adopt-footer-navbar-sdc-components]] (the footer block plugin)
- [[0027-site-footer-and-contact-link]]
