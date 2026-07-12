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
depends-on:
blocked-by:
---

# Task: Define Icelandic flag colour tokens

## Context

Establish Icelandic red and blue as the single source of truth for the palette,
so buttons and every other surface reference tokens rather than hard-coded
values.

## Acceptance criteria

- [x] `--iceland-red` and `--iceland-blue` defined in `src/theme.css` `:root`,
      in `oklch()` to match existing token format.
- [x] Accessible on-colour foreground tokens defined —
      `--iceland-red-foreground`, `--iceland-blue-foreground`. Exact ratios to
      be measured in [[0005-accessibility-and-contrast-verification]].
- [x] Semantic button tokens added: `--btn-primary-bg` /
      `--btn-primary-bg-hover` and `--btn-accent-bg` / `--btn-accent-bg-hover`
      (+ foregrounds), encoding the red↔blue swap.
- [x] `.dark` block updated with lightness-tuned brand values; swap tokens
      re-derive automatically.
- [x] Relationship resolved: `--primary` and `--destructive` now **alias** the
      brand tokens (`var(--iceland-blue)` / `var(--iceland-red)`), so existing
      behaviour is unchanged and there is a single source of truth.

## Implementation notes

- Implemented in `src/theme.css` `:root` and `.dark` blocks.
- Reused the theme's existing well-tuned oklch values as the canonical brand
  colours (resolves ADR decision **D1**); swap modelled with paired semantic
  tokens (resolves **D4**).
- **Not** done here: the global semantic flip of `--primary` to red (**D2**)
  was deliberately avoided — primary stays blue and the button component will
  consume the new `--btn-*` swap tokens in
  [[0002-update-button-variants-flag-color-swap]].
- `theme.css` is a standalone runtime stylesheet (see its file header), so the
  tokens take effect without a compiled-CSS rebuild. `npm run format` was run;
  the repo `build` currently fails on unrelated pre-existing Twig templates
  (`templates/content/field.html.twig`,
  `templates/content/field--commerce-product--title.html.twig`).

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
