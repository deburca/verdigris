---
type: task
tags: [hestehoj/task]
status: backlog
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

- [ ] Brand red/blue values (or lightness-adjusted variants) defined in `.dark`.
- [ ] Button swap and secondary accents verified on dark backgrounds.
- [ ] Contrast checks pass in dark mode (feeds
      [[0005-accessibility-and-contrast-verification]]).

## Implementation notes

- Key file: `src/theme.css` `.dark` block (L77-126); note existing
  lighter `--primary` (L88) and `--ring` (L102).
- Run `npm run format && npm run build`.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
