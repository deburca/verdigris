---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Horse sales catalog / browse page

## Description
No public page lists the horses currently for sale — only direct links to
individual `/product/{id}` pages exist. `/admin/commerce/products` is
staff-only. A buyer with no direct link cannot find any horse.

## Acceptance criteria
- A public page/view listing all `horse` products with `field_sale_state:
  available` (exclude reserved/reserved-deposit/sold/withdrawn), showing
  at minimum: title, price, breed (constant), gaits, thumbnail
- Linked from primary navigation
- Sold/withdrawn horses not publicly listed (confirm with client whether
  "sold" horses should still show, e.g. as a sold-horses gallery for
  marketing — default assumption here is no)

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0011-shh-entity-content-type-modeling]]
- [[0014-icelandic-horse-gaits-field]]
</content>
