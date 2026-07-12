---
type: task
tags: [hestehoj/task]
status: backlog
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

- [ ] `primary` variant renders Icelandic red at rest, Icelandic blue on
      `hover` and `focus-visible`.
- [ ] Accent/`secondary` variant renders Icelandic blue at rest, Icelandic red
      on `hover` and `focus-visible`.
- [ ] Swap driven by the tokens from [[0001-define-icelandic-flag-color-tokens]],
      not one-off hex values.
- [ ] Transition remains smooth and honours `prefers-reduced-motion`.
- [ ] Disabled state stays visually distinct (desaturated) and non-interactive.
- [ ] `primary-inverted` / `secondary-inverted` **redefined** under the new
      system (D5), not merely kept/dropped.
- [ ] Focus ring keeps the blue `--ring` across variants (D7).

## Implementation notes

- Per D2 (option a), this task also flips the global `--primary` token to red
  and adds a brand-blue `--accent` in `src/theme.css`, landing **together** with
  the consumer updates in [[0007-regression-audit-primary-accent-usage]] to
  avoid a broken intermediate state.
- Key file: `components/button/button.twig` — CVA `variant` block (L21-26).
- Follow `AGENTS.md`: CVA with multi-line arrays, `yes`/`no` variant keys, no
  inline conditionals in `class`.
- Existing base already sets `focus-visible:*` ring (L12) and
  `disabled:opacity-50` (L13).
- Run `npm run format && npm run build`; verify in Storybook.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
