---
tags: [hestehoj/decision]
status: accepted
created: 2026-07-12
updated: 2026-07-12
decided: 2026-07-12
site: shh
deciders: [paddy]
---

# 0001: Icelandic Flag Colour System

## Status

accepted

## Context

The Hestehoj theme serves an Icelandic-horse stud website. We want a colour
identity that reads unmistakably as "Icelandic" by exploiting the flag's red and
blue. The requested signature behaviour is a **colour swap** on interactive
elements:

- **Primary buttons**: Icelandic red at rest → Icelandic blue on hover/focus.
- **Accent buttons**: Icelandic blue at rest → Icelandic red on hover/focus.

The theme already gestures at this but is not built for it:

- `src/theme.css:23` sets `--primary` to Icelandic **blue**.
- `src/theme.css:39` sets `--destructive` to Icelandic **red** (semantically
  "destructive" — wrong semantics for a general brand accent).
- `components/button/button.twig:22-25` **darkens** on hover
  (`hover:bg-primary/85`) instead of swapping hue, and exposes variants
  `primary`, `secondary`, `primary-inverted`, `secondary-inverted`.

So adopting the proposal is not purely additive: making `primary` red conflicts
with the existing blue `primary` token that other components consume.

## Decision

Adopt Icelandic red and blue as **first-class, named brand tokens** and
implement the hover/focus colour swap on buttons. The precise token values,
semantic mapping, and swap-modelling approach are the sub-decisions below and
must be resolved before implementation (tracked in
[[icelandic-flag-color-scheme]]).

Final direction (accepted 2026-07-12):

1. Introduce explicit brand tokens `--iceland-red` and `--iceland-blue` in
   `src/theme.css` as the single source of truth, plus accessible on-colour
   foreground tokens. **(Done — task 0001.)**
2. Model the swap with **paired semantic tokens** (`--btn-primary-bg` /
   `--btn-primary-bg-hover`, and accent equivalents) rather than hard-coded
   utility classes, so light/dark modes and future re-skins stay centralised.
   **(Done — task 0001.)**
3. Redefine the brand semantics so **`primary` = Icelandic red** and
   **`accent` = Icelandic blue**, with the hover/focus swap as the default
   interaction for both.

## Resolved decisions

- **D1 — Exact colours: ACCEPTED as implemented.** Reuse the theme's existing
  well-tuned oklch values (`--iceland-red: oklch(0.573 0.216 21.1)`,
  `--iceland-blue: oklch(0.428 0.146 256.1)`) with near-white foregrounds.
  Exact contrast ratios still to be measured in
  [[0005-accessibility-and-contrast-verification]].
- **D2 — Semantic mapping: option (a).** Redefine `--primary` to red and add a
  brand-blue `--accent`, then update **every** current `bg-primary` / `--accent`
  consumer. NOTE: `--accent` currently denotes a muted light-blue _surface_
  (form focus, subtle panels); repointing it to vivid brand blue changes those
  usages, so the regression pass
  ([[0007-regression-audit-primary-accent-usage]]) must cover
  `--accent` / `--accent-foreground` as well as `--primary`. The flip and its
  consumer updates land together (tasks 0002 + 0007), **not** in the token
  commit, to avoid a broken intermediate state.
- **D3 — Swap scope: broad.** Apply the swap/palette beyond buttons to links,
  badges, and other interactive accents
  ([[0004-apply-flag-accents-to-links-badges-and-forms]]). _(This is the
  decision the sign-off labelled "D4".)_
- **D4 — Swap modelling: paired semantic tokens.** ACCEPTED as implemented in
  task 0001; the button CVA consumes `--btn-*` tokens per `AGENTS.md`.
- **D5 — Inverted variants: redefine.** Rework `primary-inverted` and
  `secondary-inverted` under the new system rather than keeping or dropping them
  (detailed in [[0002-update-button-variants-flag-color-swap]]).
- **D6 — Dark mode: lightness-adjusted variants.** Keep the hue but tune
  lightness on dark surfaces (as already implemented in the `.dark` block);
  revisit in [[0006-dark-mode-reconciliation]].
- **D7 — Focus indicator: keep `--ring` (blue).** Retain the existing blue focus
  ring across variants; verify it stays visible against the red-primary rest
  state in [[0005-accessibility-and-contrast-verification]].

## Consequences

### Positive

- Strong, memorable, culturally coherent brand identity.
- Clearer interaction feedback (hue change is more noticeable than a darken).
- A documented token system makes future colour work centralised and safe.

### Negative

- Redefining `primary` (if D2a) touches many components and needs a regression
  pass ([[0007-regression-audit-primary-accent-usage]]).
- A hue swap on hover is less conventional than darkening and must be verified
  for contrast in **both** states.
- Red + blue at full saturation can vibrate visually; needs tasteful tuning.

### Neutral

- Naming choice (`--iceland-red` brand token vs. reusing `--destructive`) is a
  maintainability trade-off, not a user-visible one.

## Alternatives Considered

### Alternative 1: Keep darken-on-hover, just retune the two hues

Lowest effort — only adjust token values. Rejected as the primary direction
because it loses the distinctive, requested swap interaction.

### Alternative 2: Red primary everywhere, blue reserved for links/accents only

Simpler mental model (one dominant brand colour). Retained as a fallback if the
symmetric swap proves too busy in testing.

## Implementation Notes

- Source of truth: `src/theme.css` (`:root` and `.dark`).
- Button variants: `components/button/button.twig` CVA `variant` block; keep
  CVA `yes`/`no` conventions and multi-line arrays per `AGENTS.md`.
- Update `components/button/button.component.yml` if variant names change, and
  refresh Storybook stories.
- Always run `npm run format && npm run build` from the theme dir after changes.

## References

- Project: [[icelandic-flag-color-scheme]]
- Files: `src/theme.css`, `components/button/button.twig`,
  `components/button/button.component.yml`
- WCAG 2.1 contrast (SC 1.4.3 / 1.4.11)
