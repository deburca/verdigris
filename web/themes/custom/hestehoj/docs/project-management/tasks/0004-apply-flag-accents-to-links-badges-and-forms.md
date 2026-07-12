---
type: task
tags: [hestehoj/task]
status: done
priority: medium
project: "[[icelandic-flag-color-scheme]]"
area: theme
created: 2026-07-12
branch:
release:
depends-on: ["[[0001-define-icelandic-flag-color-tokens]]"]
blocked-by:
---

# Task: Extend flag palette to secondary elements

## Context

Take the palette beyond buttons for a cohesive identity: links, badges, form
focus/error states, card accents, section dividers, and navigation active
states. Scope depends on D3 in [[0001-icelandic-flag-color-system]].

## Acceptance criteria

- [x] Text links use brand blue at rest (`[&_a]:text-accent`) → brand red on
      hover (`[&_a]:hover:text-primary`) in `components/text/text.twig` default
      variant. Inverted and primary variants unchanged (links inherit context).
- [x] Form inputs already show brand-blue focus border (`border-accent`) and
      label (`text-accent`) from the semantic flip in task 0001 — no further
      change needed.
- [x] Error state uses Icelandic red via `--destructive` brand token:
      `border-destructive` on errored inputs, `text-destructive` on error
      messages and required markers; replaces hard-coded `red-500` values.
      Non-colour affordances already present (error message text, required `*`).
- [x] `badge`, `card`, `cta`, `navbar`, `section`, `hero-side-by-side`,
      `card-icon`, `card-logo`, `group` all reference brand tokens via
      `bg-primary` / `bg-accent` (already correct after task 0001 semantic flip).
- [x] No component relies on colour alone to convey meaning; contrast
      verification remains [[0005-accessibility-and-contrast-verification]].

## Implementation notes

- `components/text/text.twig`: added `[&_a]:text-accent [&_a]:hover:text-primary`
  to the `default` textColor variant.
- `src/main.css`: replaced `text-red-500` / `border-red-500` (3 places) with
  `text-destructive` / `border-destructive` so error states use the brand token.
- Badge, card, CTA, navbar, section, group, hero components needed no change—
  they already used `bg-primary` / `bg-accent` which point to the brand colours
  after the semantic flip.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
