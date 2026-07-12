---
type: task
tags: [hestehoj/task]
status: done
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

- [x] Label text on red and on blue meets WCAG AA (≥ 4.5:1) in rest **and**
      hover/focus states, light and dark mode (see results below).
- [x] Focus indicator is always visible and distinguishable from hover (D7):
      `ring-offset-2 ring-offset-background` separates ring from button surface;
      ring vs page background = 8.32:1 (AA); gap vs button surface ≥ 4.05:1.
- [x] No meaning conveyed by colour alone: error states have text message +
      required `*` marker; button state uses shape/position, not colour alone.
- [x] `prefers-reduced-motion` respected: `motion-reduce:transition-none` on
      button base and icon; icon translate reset.
- [x] Results recorded below.

## Measured contrast ratios

Source: `scripts/wcag-contrast-check.mjs` (oklch → oklab → linear sRGB → WCAG
relative luminance). Thresholds: AA text ≥ 4.5:1 | AA UI ≥ 3.0:1 | AAA ≥ 7.0:1.

### Light mode
| Pair | Ratio | Grade |
| White on primary-btn (red) | 4.71:1 | AA |
| White on accent-btn (blue) | 7.95:1 | AAA |
| Blue link on page bg | 8.32:1 | AAA |
| Red link-hover on page bg | 4.93:1 | AA |
| Focus ring (blue) vs page bg | 8.32:1 | AA (UI) |
| Ring-offset (bg) vs red btn | 4.93:1 | AA (UI) |

### Dark mode
| Pair | Ratio | Grade |
| White on primary-btn (red) | 4.71:1 | AA |
| White on accent-btn (blue) | 4.58:1 | AA |
| Focus ring vs dark page bg | 5.71:1 | AA (UI) |
| Ring-offset (bg) vs red btn | 4.05:1 | AA (UI) |
| Red btn vs dark page bg | 4.05:1 | AA (UI) |
| Blue btn vs dark page bg | 4.17:1 | AA (UI) |

## Fixes applied during this task

- **Dark mode `--iceland-red`** reverted from `oklch(0.635 0.188 15.6)` (which
  gave only 3.60:1 with white text) to `oklch(0.573 0.216 21.1)` — same as
  light mode. It still passes the UI threshold (4.05:1) against the near-black
  dark background.
- **Focus ring** changed from `focus-visible:border-ring` (blue border *on* red
  surface = 1.69:1 FAIL) to `ring-offset-2 ring-offset-background` (ring floats
  outside a background-colour gap; ring vs gap and gap vs button both pass).
  Applied to both `components/button/button.twig` and `components/badge/badge.twig`.

## Implementation notes

- Contrast script: `scripts/wcag-contrast-check.mjs` (keep for regression runs).
- Disabled buttons: `disabled:opacity-50` reduces to ~50% visibility; perceivable
  but clearly inactive. Opacity-based approaches are standard for disabled UI.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
