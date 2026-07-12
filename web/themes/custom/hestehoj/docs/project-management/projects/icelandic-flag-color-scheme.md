---
type: project
tags: [hestehoj/project]
status: active # planning | active | paused | done | dropped
site: shh # vdg | kbg | shh | shared
created: 2026-07-12
updated: 2026-07-12
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

## Current state

The theme **already** leans on the flag colours, but not as an explicit,
swap-based system:

- `src/theme.css` defines `--primary` as Icelandic **blue** and `--destructive`
  as Icelandic **red** (semantically "destructive", not a general accent).
- `components/button/button.twig` uses `bg-primary` / `bg-accent` and **darkens**
  on hover (`hover:bg-primary/85`) rather than swapping colours.
- There is no dedicated Icelandic-red accent token for non-destructive use.

The proposal therefore changes the semantic mapping (primary would become red),
which is the central decision to resolve — see the ADR below.

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
