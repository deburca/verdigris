---
type: task
tags: [hestehoj/task]
status: backlog
priority: medium
project: "[[icelandic-flag-color-scheme]]"
area: tests
created: 2026-07-12
branch:
release:
depends-on: ["[[0002-update-button-variants-flag-color-swap]]"]
blocked-by:
---

# Task: Regression audit of primary/accent usage

## Context

`--primary` is redefined from blue to red and `--accent` from a muted light-blue
surface to vivid brand blue (D2, option a), so every existing `bg-primary` /
`text-primary` / `bg-accent` / `text-accent` / `border-accent` consumer changes
appearance. Audit the whole theme so nothing regresses unexpectedly. Note
`src/main.css` uses `border-accent` / `text-accent` for form focus and
`bg-primary` for submit buttons.

## Acceptance criteria

- [ ] Inventory of all `primary`, `accent`, `destructive`, and `ring` colour
      usages across `components/` and `templates/`.
- [ ] Each usage confirmed intentional under the new mapping or corrected.
- [ ] Visual pass across key pages/components (hero, cards, cta, navbar,
      footer, forms) in light and dark mode.
- [ ] Findings summarised; follow-up fixes filed as needed.

## Implementation notes

- Search: `grep -rn "primary\|accent\|destructive\|ring" components/ templates/`.
- Coordinate with [[0003-reconcile-button-variant-schema-and-storybook]] for
  any renamed variants.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
