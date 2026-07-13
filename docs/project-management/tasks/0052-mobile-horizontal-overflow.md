---
type: task
tags: [cms2/task]
status: dropped
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-13
updated: 2026-07-13
---
# Task: Site-wide horizontal overflow on mobile — NOT A BUG (measurement artefact)

> **DROPPED 2026-07-13 — the bug does not exist.** It was an artefact of
> how I was measuring, not a fault in the site. Kept, rather than
> deleted, so the false alarm and the technique that produced it are on
> the record.

## What I originally claimed
That every page overflowed horizontally below ~480 px, clipping content
and hiding the navbar's hamburger — "arguably a launch blocker". The
evidence was a series of headless-browser screenshots taken with
`--window-size=390,…`, in which body copy visibly ran off the right
edge, even on plain-text pages.

## Why it was wrong

**Headless Chromium/Brave clamps its window to a 500 px minimum.** Ask
for 390, and it renders the page at a **500 px viewport** — then writes
a 390-px-wide screenshot, which is simply the left-hand 390 px of a
500 px render. Everything past 390 px looks "clipped" because the image
was cropped, not because the page overflowed. Every mobile screenshot I
took was lying in exactly this way, and consistently enough across pages
to look like a real, systemic bug.

## What the site actually does

Measured in-browser (a throwaway `tmp_overflow_probe` module that
attached a script reading real geometry, then removed):

| Page | Viewport | `scrollWidth` | Elements wider than viewport |
|---|---|---|---|
| `/privacy-policy` | 500 | 500 | **0** |
| `/horses` | 500 | 500 | **0** |
| `/` (with the new section 2) | 500 | 500 | **0** |

And with the layout **squeezed to a 390 px box** — which is the decisive
test, because **Tailwind's smallest breakpoint is 640 px, so the CSS at
390 px and at 500 px is identical**; the only way the site could break
narrower is an element with a hard minimum width:

| Page | `body.scrollWidth` when squeezed to 390 | Elements wider than 390 |
|---|---|---|
| `/privacy-policy` | 390 | **0** |
| `/horses` | 390 | **0** |
| `/` | 390 | **0** |

Nothing overflows. Nothing has a minimum width that would break a phone
layout.

## The client's Safari observation, explained

> "In Safari I cannot reduce the width of the page below 540 px."

That is **Safari's own minimum window width** — a limit of the browser
window, not of the page. It corroborated my false finding by coincidence
(a browser that won't go narrow, and a headless browser that silently
refuses to go narrow, look identical from the outside).

**To actually test phone widths**, use Safari's **Develop → Enter
Responsive Design Mode** (or Chrome's device toolbar), which emulates a
true device viewport instead of resizing the window — or open the site
on a real phone.

## Lessons worth keeping

1. **Verify the tool before trusting its output.** The screenshot said
   "clipped"; the browser never rendered the width I asked for. A single
   check of `document.documentElement.clientWidth` would have caught it
   immediately — and eventually did.
2. **Screenshots prove rendering, not geometry.** For layout questions,
   measure (`scrollWidth`, `getBoundingClientRect`), don't eyeball.
3. The instinct that *did* pay off in the same session: I removed
   `tile_size: 4:3` from the section 2 cards because a fixed aspect
   ratio makes a grid item derive its minimum width from its content
   height. That reasoning stands on its own and the change is correct —
   it just wasn't fixing an overflow, because there wasn't one.

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] (where the false alarm was raised)
