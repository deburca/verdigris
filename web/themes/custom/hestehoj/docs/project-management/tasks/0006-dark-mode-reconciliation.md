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

# Task: Dark mode reconciliation

## Context

`src/theme.css` ships a `.dark` block that already lightens `--primary` for
contrast. The flag colours and the swap must be tuned for dark surfaces per D6.

## Acceptance criteria

- [x] Brand token values defined and contrast-verified in `.dark` (task 0005
      fixed dark-mode red; blue kept at `oklch(0.559 0.196 256.8)` = 4.58:1).
- [x] Button swap verified on dark backgrounds: red/blue buttons both pass AA
      vs near-white text; both visible vs near-black bg (>3:1 UI).
- [x] All dark-mode secondary element contrast checks pass — see results below.

## Dark-mode secondary element results

(From `scripts/wcag-contrast-check.mjs`; all pass AA or better.)

| Surface | Ratio | Grade |
| Link colour (blue tint) on dark page bg | 6.91:1 | AA |
| Link hover (red tint) on dark page bg | 6.47:1 | AA |
| Link colour on dark card bg | 6.20:1 | AA |
| Error text (--error-text) on dark page bg | 6.47:1 | AA |
| Form focus border (blue/accent) vs dark page bg | 4.17:1 | AA UI |
| Muted foreground on dark page bg | 7.77:1 | AAA |

## Tokens added in this task

### `src/theme.css`
- **`--link-color` / `--link-color-hover`** — light mode aliases the button
  hues directly; dark mode uses lightened tints (`oklch 0.680`) that pass AA
  on near-black backgrounds. Consumed by `text.twig` `default` textColor.
- **`--error-text`** — light mode aliases `--iceland-red`; dark mode uses the
  same lightened red (`oklch(0.680 0.150 21.1)`) → 6.47:1. Consumed by
  `.form-required::after` and `.form-item--error-message` in `main.css`.

### `src/main.css`
- Added `--color-link`, `--color-link-hover`, `--color-error-text` to
  `@theme inline` so Tailwind generates the utility classes.
- Updated `.form-required::after` and `.form-item--error-message` to use
  `text-error-text` instead of `text-destructive`.
  (Error *borders* still use `--destructive` — a UI component needing only 3:1.)

### `components/text/text.twig`
- `default` textColor now uses `[&_a]:text-link [&_a]:hover:text-link-hover`
  so dark-mode rich-text links automatically get the lightened tints.

## Implementation notes

- Contrast script: `scripts/wcag-contrast-check.mjs` — run for regressions.
- `--destructive` kept at `--iceland-red` for button/border tokens; only the
  text-specific contexts use the lighter `--error-text`.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
