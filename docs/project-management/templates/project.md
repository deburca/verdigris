---
type: project
tags: [cms2/project]
status: planning       # planning | active | paused | done | dropped
site: shared           # vdg | kbg | shh | shared
created: YYYY-MM-DD
updated: YYYY-MM-DD
target:                # target release/date, optional
---
# Project: <title>

## Goal
One paragraph: the outcome and who benefits.

## Scope
- In scope:
- Out of scope:

## Entity / architecture model
Link out to a separate `-model.md` note if substantial, e.g. [[shh-stables-platform-model]].

## Tasks
```dataview
TABLE status, priority
FROM #cms2/task
WHERE contains(string(project), this.file.name)
SORT status asc, priority asc
```

## Open questions
- 

## Related decisions
- [[NNNN-decision-name]]
