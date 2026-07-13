---
type: task
tags: [hestehoj/task]
status: done
priority: low
project: "[[icelandic-flag-color-scheme]]"
area: theme
created: 2026-07-13
branch:
release:
depends-on: ["[[0002-update-button-variants-flag-color-swap]]"]
blocked-by:
---
# Task: Exclude docs from Tailwind's source scan (invalid `var(--btn-*)` rule)

## Context
`npm run build` warns on every rebuild:

```
Found 1 warning while optimizing generated CSS:
│   .bg-\[var\(--btn-\*\)\] {
│     background-color: var(--btn-*);
┆                                 ^-- Unexpected token Delim('*')
```

**Cause — a scanner artefact, not a code bug.** Tailwind v4's automatic
content detection treats *every* file under the theme as a source of
class candidates, **Markdown included**, and this theme's
project-management vault lives inside the theme directory. Task
[[0002-update-button-variants-flag-color-swap]]'s own Resolution quotes
the arbitrary utility `bg-[var(--btn-*)]` in prose — where the `*` is a
human wildcard meaning "any of the `--btn-…` tokens". Tailwind can't
tell documentation from code, so it compiled that literally into
`background-color: var(--btn-*)`, which is not valid CSS.

So the warning is self-inflicted by the very doc that records the
button-token work — and it emits a junk rule into `build/main.min.css`.

## Acceptance criteria
- [x] `npm run build` completes with no warnings
- [x] The invalid `var(--btn-*)` rule is gone from `build/main.min.css`
- [x] The real `--btn-*` token utilities (e.g. `bg-[var(--btn-accent-bg)]`)
      still compile
- [x] Utilities used from PHP in `modules/custom` (the existing
      `@source` directive) still compile — the fix must not narrow that
- [x] Fixed structurally, so a *future* doc that names a class can't
      reintroduce junk utilities

## Resolution (2026-07-13)

One directive in `src/main.css`, next to the existing `@source` that
widens scanning into `modules/custom`:

```css
@source not "../docs";
```

Fixed at the root — excluding documentation from the scan — rather than
contorting the prose to dodge the scanner (renaming the token in the
doc would have "fixed" this instance and left the trap armed for the
next doc). Commented in place with the warning it resolves, so the
directive doesn't look arbitrary later.

**Verified**: rebuild is clean (no warnings); `var(--btn-*)` occurrences
in `build/main.min.css` → 0; `var(--btn-accent-bg)` still present; the
utilities the shh modules rely on (`md:text-right`, `inline-block`,
`align-text-bottom`, `md:grid-cols-3`, `aspect-1/1`,
`underline-offset-2`) all still compile; site renders 200.

**Incidental**: the committed `build/main.min.css` is now Tailwind
**4.1.18**, matching the local toolchain. The mismatch flagged during
the 2026-07-12 composer update (committed build made with 4.2.1 while
the local install was 4.1.18, so a rebuild would churn the whole file)
has therefore resolved itself — rebuilding the theme CSS is safe again,
and the earlier "do not rebuild" caution is retired.

## Related
- Project:: [[icelandic-flag-color-scheme]]
- Tasks:: [[0002-update-button-variants-flag-color-swap]] (the doc whose
  prose triggered it)
