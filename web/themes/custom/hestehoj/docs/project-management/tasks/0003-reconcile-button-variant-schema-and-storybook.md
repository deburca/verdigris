---
type: task
tags: [hestehoj/task]
status: done
priority: medium
project: "[[icelandic-flag-color-scheme]]"
area: docs
created: 2026-07-12
branch:
release:
depends-on: ["[[0002-update-button-variants-flag-color-swap]]"]
blocked-by:
---

# Task: Reconcile button schema and Storybook

## Context

If variant names or semantics change, the SDC schema and Storybook stories must
stay in sync so the Canvas UI and component previews reflect reality.

## Acceptance criteria

- [x] `components/button/button.component.yml` `variant` enum unchanged, but
      `meta:enum` labels now describe the brand + swap (e.g. "Primary (red →
      blue on hover)", "Accent (blue → red on hover)", and the two inverted).
- [x] Storybook coverage: this theme has no `.stories.*` files or `.storybook`
      config — stories are server-rendered from the SDC schema. Expanded
      `variant.examples` to enumerate all four variants; `disabled` already has
      true/false examples. Hover/focus are interaction states of the rendered
      previews (the swap fires on `hover` + `focus-visible`).
- [x] No variant was renamed, so no consumer migration was needed. Verified
      references in `components/navbar/navbar.twig` (primary, secondary) and
      `components/card-pricing/card-pricing.twig` (primary, primary-inverted).

## Implementation notes

- Key file: `components/button/button.component.yml` — updated `meta:enum`
  labels and `variant.examples`.
- Validated the SDC definition loads with the new labels via
  `drush php:eval` (plugin.manager.sdc getDefinition) — no schema errors.
- Storybook is the Canvas SDC server-render integration
  (`STORYBOOK_SERVER_RENDER_URL`), so the schema change is the story change.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
