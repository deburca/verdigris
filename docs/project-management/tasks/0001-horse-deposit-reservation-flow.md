---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Horse deposit/reserve flow and withdrawn state

## Description
Extend `sale_state` workflow: add `withdrawn` (owner pulls horse from sale) and a
`reserved-deposit` sub-state distinct from `reserved` (buyer paid deposit vs full
price pending). Deposit is a separate order item type from outright purchase.

## Acceptance criteria
- `sale_state` supports: available, reserved-deposit, reserved, sold, withdrawn
- Deposit order item type exists with its own refund path (see 0015)
- Admin can transition to `withdrawn` without a linked order

## Related
- [[shh-stables-platform]]
- [[0015-cancellation-refund-policy]]
