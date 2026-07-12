---
type: task
tags: [hestehoj/task]
status: done
priority: high
project: "[[icelandic-flag-color-scheme]]"
area: theme
created: 2026-07-12
branch:
release:
depends-on: ["[[0001-define-icelandic-flag-color-tokens]]"]
blocked-by:
---

# Task: Implement the button colour swap

## Context

Wire the signature interaction into the button component: primary = red → blue
on hover/focus, accent = blue → red on hover/focus. Replaces the current
darken-on-hover behaviour.

## Acceptance criteria

- [x] `primary` variant renders Icelandic red at rest, Icelandic blue on
      `hover` and `focus-visible`.
- [x] Accent/`secondary` variant renders Icelandic blue at rest, Icelandic red
      on `hover` and `focus-visible`.
- [x] Swap driven by the `--btn-*` tokens from
      [[0001-define-icelandic-flag-color-tokens]], not one-off hex values.
- [x] Transition remains smooth and honours `prefers-reduced-motion`
      (`motion-reduce:transition-none` on base + icon; icon translate reset).
- [x] Disabled state stays visually distinct (desaturated) and non-interactive
      (base `disabled:opacity-50 disabled:pointer-events-none`; disabled always
      renders as `<button disabled>`).
- [x] `primary-inverted` / `secondary-inverted` **redefined** (D5): light
      surface, brand hue swaps red↔blue on hover/focus-visible.
- [x] Focus ring keeps the blue `--ring` across variants (D7); only the fill /
      label hue swaps.

## Implementation notes

- Implemented in `components/button/button.twig` CVA `variant` block using the
  `--btn-*` swap tokens; both `hover` and `focus-visible` apply the swap.
- Variant names unchanged (`primary`, `secondary`, `primary-inverted`,
  `secondary-inverted`), so `button.component.yml` needs no change — Storybook /
  schema reconciliation remains [[0003-reconcile-button-variant-schema-and-storybook]].
- The global `--primary`→red / `--accent`→brand-blue flip and CSS consumer
  updates were already landed in the prior commit; this task builds on them.
- Rebuilt `build/main.min.css` (new arbitrary `bg-[var(--btn-*)]` /
  `focus-visible:*` utilities compiled).
- Follows `AGENTS.md`: CVA multi-line arrays, no inline conditionals.
- Storybook visual check still recommended (no local browser tooling here);
  contrast verification tracked in [[0005-accessibility-and-contrast-verification]].

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
