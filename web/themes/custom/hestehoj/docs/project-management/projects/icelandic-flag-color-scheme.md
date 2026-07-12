---
type: project
tags: [hestehoj/project]
status: done # planning | active | paused | done | dropped
site: shh # vdg | kbg | shh | shared
created: 2026-07-12
updated: 2026-07-12
completed-phase: 2026-07-12
target:
---

# Project: Icelandic Flag Colour Scheme

## Goal

Give the Hestehoj theme a distinct Icelandic-horse identity by systematically
applying the colours of the Icelandic flag — red and blue — across the site.
The signature interaction is a **colour swap** on interactive elements: primary
buttons are Icelandic red and turn Icelandic blue on hover/focus, while accent
buttons are Icelandic blue and turn Icelandic red on hover/focus. Visitors get a
memorable, culturally coherent brand experience and stronger visual feedback on
interaction.

## Scope

- In scope:
  - Establishing Icelandic red + blue as first-class theme colour tokens.
  - The hover/focus colour-swap behaviour on buttons (primary ↔ accent).
  - Extending the palette to secondary elements: links, badges, form focus/error
    states, card accents, section dividers, navigation active states.
  - Accessibility verification (WCAG AA contrast, non-colour affordances,
    visible focus indicators, reduced-motion).
  - Light and dark mode reconciliation.
- Out of scope:
  - A full brand/logo redesign.
  - Content/photography changes.
  - Changes to other CMS2 sites (`vdg`, `kbg`) — this is `shh`-only.

## Delivered (phase 1 — 2026-07-12)

Core colour system and button swap shipped and verified on
https://hestehoj.ddev.site. Pushed to `main` (`d467382`).

- **`src/theme.css`** — `--iceland-red` / `--iceland-blue` brand tokens;
  `--btn-*` swap tokens; `--primary` → red, `--accent` → brand blue (light +
  dark). All semantic consumers aliased to the brand tokens.
- **`components/button/button.twig`** — CVA variant block wired to `--btn-*`
  tokens; swap fires on `hover` + `focus-visible`; inverted variants redefined
  (D5); `motion-reduce:transition-none` on base and icon.
- **`components/button/button.component.yml`** — `meta:enum` labels describe
  the swap; `variant.examples` enumerates all four variants.
- **`src/main.css`** — `.form-submit` / `input.button` (e.g. Commerce Add to
  Cart) updated with the same swap tokens; darken rule removed.
- **`components/group/group.twig`** — background variants paired with matching
  foreground tokens for legibility.

## Tasks

```dataview
TABLE status, priority
FROM #hestehoj/task
WHERE contains(string(project), this.file.name)
SORT status asc, priority asc
```

Task list (until Dataview resolves):

- [[0001-define-icelandic-flag-color-tokens]]
- [[0002-update-button-variants-flag-color-swap]]
- [[0003-reconcile-button-variant-schema-and-storybook]]
- [[0004-apply-flag-accents-to-links-badges-and-forms]]
- [[0005-accessibility-and-contrast-verification]]
- [[0006-dark-mode-reconciliation]]
- [[0007-regression-audit-primary-accent-usage]]

## Decisions (resolved)

All resolved in [[0001-icelandic-flag-color-system]] (accepted 2026-07-12):

1. **Colours** — reuse the existing oklch brand values (D1).
2. **Semantic mapping** — `primary` = red, `accent` = brand blue; update all
   consumers, including existing `--accent` surface usages (D2, option a).
3. **Scope** — swap/palette applies to buttons, links, badges, and other
   interactive accents (D3).
4. **Token modelling** — paired semantic `--btn-*` tokens (D4).
5. **Inverted variants** — redefined under the new system (D5).
6. **Dark mode** — lightness-adjusted variants (D6).
7. **Focus ring** — keep the blue `--ring` (D7).

## Related decisions

- [[0001-icelandic-flag-color-system]]
