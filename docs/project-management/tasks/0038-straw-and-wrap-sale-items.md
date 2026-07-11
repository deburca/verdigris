---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-11
updated: 2026-07-11
---
# Task: Sell straw and wrap alongside horses

## Description
Client request (2026-07-11): SHH sells more than horses. Baled
**straw** and **wrap** (a silage-like feed product wrapped in
multiple layers of plastic, used as horse feed) should be
purchasable on the site too.

These are commodity goods, not unique animals — which is why this
is not "add two more horse products":

- A horse is a one-off: quantity 1, a `field_sale_state` lifecycle
  ([[0024-horse-sale-state-enforcement]]'s purchase blocking and
  auto-`sold` flip, [[0001-horse-deposit-reservation-flow]]'s
  deposits, the 0036/0037 staff transitions). Straw and wrap are
  repeat-purchase quantity goods — a rider buys N bales/rolls, and
  none of that lifecycle applies.
- The horse machinery already ignores them *by construction* as
  long as the new variation types do **not** get `field_sale_state`:
  0024's `HorseAvailabilityChecker::applies()` keys on the presence
  of that field (deliberately, not on bundle), and
  `HorseSaleCompletionSubscriber` is scoped to the `horse` order
  item type. Verify rather than assume.

Decisions to make during implementation (record the reasoning in
the Resolution):

- **Product modelling**: one shared `feed`/`goods` product type
  holding a straw product and a wrap product, vs. a product type
  per item. Variations give unit options (e.g. small/large bale)
  for free if the client wants them.
- **Order type**: horses ride `horse_sale` (own checkout flow,
  3-day cart expiration — decision
  [[0018-separate-order-types-horse-vs-booking]]); facility
  bookings ride `default` (30-minute expiration, wrong for goods).
  Decide: reuse `horse_sale` for all physical-goods sales, or add
  a third order type. Either way the goods order item type needs
  quantity genuinely usable in the add-to-cart form (check what
  the `horse` order item's form display does with quantity before
  copying it).
- **Fields**: since [[0033-durable-config-strategy-shh]] the sane
  path is building bundles/fields via field_ui on dev (the `local`
  config split) and exporting — not programmatic `hook_install()`
  field creation with its 0011-era stale-cache workarounds.

Client input needed (record answers here or as new tasks):
units and prices (per bale? per roll?), whether stock levels must
be tracked or availability is a phone call, and pickup vs.
delivery — no shipping capability exists on the platform today.

Images for these items are deliberately split out:
[[0039-product-images-featured-and-gallery]] covers upload,
featured-image-in-lists and gallery-on-item display for horses AND
the new types, and its new-type half lands on top of this task's
bundles.

## Acceptance criteria
- [ ] Straw and wrap purchasable end to end over real HTTP as a
      non-admin account, including quantity > 1 in one order
- [ ] Modelling and order-type decisions recorded with reasoning
- [ ] New variation types carry no `field_sale_state`; verified the
      horse lifecycle machinery ignores the new items, and a sold
      horse's purchase blocking still holds unchanged
- [ ] Correct 25%-inclusive DK VAT on a real cart (`physical_goods`
      tax class, same as horses —
      [[0005-tax-classification-horses-vs-bookings]])
- [ ] Discoverable: a listing page reachable from the main
      navigation (`shh_common_ensure_menu_link`), one card per item
      with price — `/horses` ([[0019-horse-catalog-page]]) stays
      horses-only unless there's a reason to merge
- [ ] Client questions (units/prices, stock, pickup/delivery)
      answered or recorded as explicit open items
- [ ] Config exported (`make shh-export`) and committed in the same
      change (decision [[0020-shh-config-export-strategy]])

## Related
- [[shh-stables-platform]]
- [[0039-product-images-featured-and-gallery]]
- [[0018-separate-order-types-horse-vs-booking]]
- [[0024-horse-sale-state-enforcement]]
- [[0005-tax-classification-horses-vs-bookings]]
- [[0020-shh-config-export-strategy]]
