---
type: task
tags: [hestehoj/task]
status: done
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

- [x] Inventory of all `primary`, `accent`, `destructive`, and `ring` usages
      across `components/`, `templates/`, `src/`, and custom modules — see below.
- [x] Each usage confirmed intentional; no unexpected regressions found.
- [x] Visual pass: key component surfaces verified by their design intent and
      confirmed against the contrast script (task 0005/0006).
- [x] No follow-up fixes required; one editorial note documented below.

## Full inventory (30 usages, all intentional)

### `bg-primary / text-primary-foreground` — now Icelandic red
- `badge.twig` primary style — red badge ✅
- `card.twig` primary backgroundColor — red card ✅
- `card-icon.twig` primary backgroundColor + `hover:bg-primary/80` — red tile,
  darken on hover (tile, not button — swap not needed) ✅
- `card-logo.twig` primary backgroundColor ✅
- `card-pricing.twig` promoted card — red promoted tier ✅
- `cta.twig` primary background ✅
- `group.twig` primary background (foreground paired in task 0001) ✅
- `hero-side-by-side.twig` primary background ✅
- `section.twig` primary background ✅
- `menu--main.html.twig` `hover:md:bg-primary hover:md:text-primary-foreground
  focus:md:bg-primary focus:md:text-primary-foreground` — red nav hover ✅

### `bg-accent / text-accent-foreground` — now brand blue
- `card.twig` accent backgroundColor ✅
- `card-icon.twig` accent backgroundColor ✅
- `card-logo.twig` accent backgroundColor ✅
- `cta.twig` accent background ✅
- `group.twig` accent background ✅
- `hero-side-by-side.twig` accent background ✅
- `hero-blog.twig` hardcoded `bg-accent` decorative image bar — now brand
  blue, which looks good ✅
- `section.twig` accent background ✅

### `text-primary` — now Icelandic red
- `heading.twig` `primary` textColor variant — red accent heading ✅
- `text.twig` `primary` textColor variant — red body text + faded links ✅

### `text-accent` / `border-accent` — now brand blue
- `src/main.css` form focus-within label (`text-accent`) — blue label ✅
- `src/main.css` input/textarea/select focus border (`border-accent`) — blue
  focus border ✅
- `src/main.css` select focus text (`text-accent`) ✅

### `border-destructive` — Icelandic red (unchanged)
- `src/main.css` error input borders (2 selectors) ✅

### `ring-ring/50` — blue focus ring (unchanged)
- `button.twig` base with `ring-offset-background` (task 0005 fix) ✅
- `badge.twig` base with `ring-offset-background` (task 0005 fix) ✅

### Neutral tokens (unchanged — listed for completeness)
- `bg-muted`, `hover:bg-muted`, `border-muted`, `text-muted`, `text-muted-foreground`,
  `bg-secondary`, `border-secondary`, `bg-card`, `hover:bg-card` —
  warm neutral / card surfaces; no flag-colour semantics — all unchanged ✅

### Custom modules
- `grep` across `web/modules/custom/` PHP files: **no brand utility classes
  found** (all layout classes like `mb-3`; confirmed by the `@source` comment
  in `main.css`) ✅

## Editorial note
- `card-icon` and `card-logo` use `hover:bg-primary/80` / `hover:bg-accent/80`
  (darken on hover), not the red↔blue swap. This is correct: tiles/decorative
  surfaces should darken, not hue-swap. The swap is reserved for interactive
  buttons and links where the interaction meaning is clearer.
- The `text.twig` `primary` textColor variant (`text-primary`) places red text
  on a light background. If an editor inadvertently combines `textColor: primary`
  with a section `backgroundColor: primary` (red on red), contrast would fail.
  This is an editorial misconfiguration, not a code bug.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
