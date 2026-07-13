---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-13
updated: 2026-07-13
---
# Task: .gitignore silently excluded every theme's icon pack

## Description
Found while vendoring a filled heart SVG for
[[0049-footer-builder-attribution]]: `git add` refused the new file
because **the entire `icons/` directory of every custom theme was
gitignored** — and had always been.

**Root cause — a macOS gitignore trap.** `.gitignore` line 300 carried
the stock macOS-template pattern:

```
Icon?
```

intended for Finder's custom-folder-icon file (literally named
`Icon\r`). But `?` is a single-character wildcard, and this repo runs
with `core.ignorecase=true` (the macOS default), so the pattern also
matches the directory **`icons`** — `Icon` + one character, case
insensitively.

**Impact — this would have broken production.** The theme's icon pack
is a *required runtime asset*: `hestehoj.icons.yml` resolves icons from
`icons/phosphor/{icon_id}.svg`, and nothing fetches them (no npm
dependency, no build step — they are vendored files). Yet:

| Theme | SVGs on disk | Tracked in git |
|---|---|---|
| `hestehoj` (shh) | 1,513 | **0** |
| `quick_silver` | 1,512 | **0** |
| `zwarte_piet` | 1,512 | **0** |

A fresh clone — or the production deploy, which is a `git pull` per
[[shh-deployment-procedure]] — would have shipped with **no icons at
all**. Every icon-bearing component (badge, button, navbar, the 0049
footer attribution) would have rendered iconless. It only worked
locally because the files exist on the dev machine.

This is the same class of failure as the config-export trap
([[0033-durable-config-strategy-shh]]): something essential lived only
on one machine, invisible to git, and would have surfaced on the day of
go-live.

## Acceptance criteria
- [x] The gitignore pattern no longer matches `icons/` directories
- [x] Finder-cruft ignores that actually matter (`.DS_Store`, `._*`)
      still work
- [x] The `hestehoj` icon pack is committed, so shh renders icons from
      a clean checkout
- [x] The other two affected themes are surfaced for their owners

## Resolution (2026-07-13)

**The pattern is removed, not "fixed".** `Icon\r` cannot be expressed
safely: git strips a trailing CR when parsing `.gitignore`, so the
CR-suffixed filename is unmatchable, and every wildcard form (`Icon?`,
`Icon[\r]`) either fails or re-swallows `icons/`. The line is replaced
with a comment recording the whole trap so nobody re-adds it from a
template. `.DS_Store` and `._*` — the macOS cruft that actually turns
up — remain.

**`hestehoj`'s 1,513 icons are now committed** (5.9 MB). The full pack
is kept rather than pruned to the handful of icons currently used:
Canvas/SDC lets an editor pick *any* Phosphor icon by id from the pack,
so a pruned pack would break icon selection in the editor rather than
just the page.

**`quick_silver` and `zwarte_piet` are now visible as untracked** (1,512
files each, ~12 MB combined) — deliberately **not** committed here, as
they belong to other sites; their owners should commit them the same
way. They will show up in `git status` until someone does, which is the
correct nag.

**Also added to the go-live checks** in [[shh-deployment-procedure]]:
verify the icon pack exists in the deployed checkout, since its absence
degrades silently (no error — icons simply don't render).

## Related
- [[shh-stables-platform]]
- [[0049-footer-builder-attribution]] (surfaced it)
- [[0033-durable-config-strategy-shh]] (same class: essential state
  living only on one machine)
