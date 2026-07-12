---
type: task
tags: [cms2/task]
status: todo
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-12
updated: 2026-07-12
---
# Task: Horse add-to-cart exposes unit-price override to visitors

## Description
Long-noted follow-up, now tracked properly (surfaced during
[[0031-sdc-based-commerce-product-display]], deliberately left
untouched there; visible in 0041's full-page screenshot): the horse
product page's add-to-cart form shows **"Override the unit price"**
(a checkbox plus, when ticked, price + currency inputs) to anonymous
visitors.

Cause: the `horse` order item type has **no `add_to_cart` form
display of its own**, so Commerce falls back to an auto-generated
default that includes every field with a widget — including
`unit_price` with its override checkbox. The `feed` order item type
(task 0038) already shows the correct fix shape:
`core.entity_form_display.commerce_order_item.feed.add_to_cart` hides
`unit_price` explicitly.

Server-side risk is believed low (Commerce's availability/price
resolvers govern the actual charge — verify rather than assume), but
at minimum it's a confusing and unprofessional control on a public
sales page.

## Acceptance criteria
- [ ] A `commerce_order_item.horse.add_to_cart` form display exists
      with `unit_price` (and other internals) hidden — quantity stays
      hidden too (a horse is quantity 1 by nature; note the contrast
      with feed, where quantity is the point)
- [ ] Verify over real HTTP that an anonymous forged POST attempting
      a unit-price override on a horse does NOT change the charged
      price (whether it ever could is the "verify rather than assume"
      part — record the finding either way)
- [ ] Config exported in the same change (decision
      [[0020-shh-config-export-strategy]])

## Related
- [[shh-stables-platform]]
- [[0031-sdc-based-commerce-product-display]]
- [[0038-straw-and-wrap-sale-items]]
