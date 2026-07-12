---
type: task
tags: [hestehoj/task]
status: backlog
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

- [ ] `components/button/button.component.yml` `variant` enum + `meta:enum`
      labels match the final variant set.
- [ ] Storybook stories cover rest, hover, focus, and disabled states for both
      brand variants.
- [ ] Any renamed variants migrated wherever buttons are referenced (templates,
      Canvas content) — cross-check with
      [[0007-regression-audit-primary-accent-usage]].

## Implementation notes

- Key files: `components/button/button.component.yml` (variant enum L14-23),
  button Storybook story.
- Verify in Storybook (https://drupal-cms2.ddev.site:6006).

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
