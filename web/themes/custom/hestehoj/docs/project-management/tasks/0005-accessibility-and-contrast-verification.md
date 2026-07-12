---
type: task
tags: [hestehoj/task]
status: backlog
priority: high
project: "[[icelandic-flag-color-scheme]]"
area: tests
created: 2026-07-12
branch:
release:
depends-on: ["[[0002-update-button-variants-flag-color-swap]]", "[[0004-apply-flag-accents-to-links-badges-and-forms]]"]
blocked-by:
---

# Task: Accessibility & contrast verification

## Context

Saturated red and blue with white text are contrast-risky, and a hue swap must
pass in both rest and hover/focus states. Verify WCAG AA before sign-off.

## Acceptance criteria

- [ ] Label text on red and on blue meets WCAG AA (≥ 4.5:1) in rest **and**
      hover/focus states, light and dark mode.
- [ ] Focus indicator is always visible and distinguishable from hover (D7).
- [ ] No meaning conveyed by colour alone (icons/text back up state).
- [ ] `prefers-reduced-motion` respected for the swap transition.
- [ ] Results recorded (measured ratios per variant/state).

## Implementation notes

- Tools: browser DevTools contrast checker; axe / Lighthouse.
- Check disabled state remains perceivable but clearly inactive.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
