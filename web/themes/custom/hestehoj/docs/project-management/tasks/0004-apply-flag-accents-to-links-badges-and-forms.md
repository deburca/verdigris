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

# Task: Extend flag palette to secondary elements

## Context

Take the palette beyond buttons for a cohesive identity: links, badges, form
focus/error states, card accents, section dividers, and navigation active
states. Scope depends on D3 in [[0001-icelandic-flag-color-system]].

## Acceptance criteria

- [ ] Text links use a defined brand hue with a clear hover treatment.
- [ ] Form inputs show a brand focus ring; error state uses the red hue plus a
      non-colour affordance (icon/message).
- [ ] `badge`, `card`, `cta`, `navbar` accents reference brand tokens.
- [ ] No component relies on colour alone to convey meaning (see
      [[0005-accessibility-and-contrast-verification]]).

## Implementation notes

- Candidate components: `components/anchor`, `components/badge`,
  `components/card`, `components/cta`, `components/navbar`, `components/section`.
- Reuse tokens from [[0001-define-icelandic-flag-color-tokens]]; CVA per `AGENTS.md`.
- Run `npm run format && npm run build`.

## Related

- Project:: [[icelandic-flag-color-scheme]]
- Decisions:: [[0001-icelandic-flag-color-system]]
- Commits::
