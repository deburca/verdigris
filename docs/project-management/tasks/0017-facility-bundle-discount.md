---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Same-timeframe multi-facility package discount

## Description
Client request: booking the Oval Track, Manège, and Lunge Ring all for the
same 30-minute time slot should cost 80 DKK total rather than the sum of
their individual prices (50 + 30 + 20 = 100 DKK). Confirmed with the client:
the discount applies **only** when all three share the exact same
start/end time in one order — booking them at different times, or booking
additional facilities alongside them, doesn't change or extend the
discount.

## Prerequisite blocker found and fixed: BEE's cart couldn't hold more than one booking
`AddReservationForm::submitForm()` unconditionally **empties the cart**
before adding a new reservation:
```php
$cart = $this->cartProvider->getCart('default', $store);
if (!$cart) { $cart = $this->cartProvider->createCart('default', $store); }
else { $this->cartManager->emptyCart($cart); }
```
This meant a rider could never have more than one booking in the same order
— booking the Manège after already booking the Oval Track would silently
discard the Oval Track booking. Confirmed with the client this should be
fixed generally (bookings should accumulate like a normal cart), not just
special-cased for these three facilities. Fixed via a composer patch
(`patches/bee-accumulate-cart-instead-of-emptying.patch`, registered in
`composer.json`), verified against a real diff round-trip before applying.
Verified end to end: booking Oval Track then Manège in the same session now
correctly produces one order with both order items.

## A significant, well-diagnosed blocker: commerce_promotion cannot currently be enabled on this site
The natural way to build "if the cart contains X, Y, Z, apply a discount"
in Commerce is the `commerce_promotion` module. Attempting to enable it hit
a **real, 100% reproducible bug** — not a stale-cache issue like the
recurring ones in earlier tasks, and not fixable by retrying, module
ordering, or a container restart:

- `commerce_promotion` adds a new multi-value base field (`coupons`) to the
  existing `commerce_order` entity type, plus two new entity types
  (`commerce_promotion`, `commerce_promotion_coupon`).
- Installing that field requires Drupal core's
  `EntityDefinitionUpdateManager::installFieldStorageDefinition()`, which —
  as part of its own normal, correct behaviour — calls
  `clearCachedFieldDefinitions()` immediately after creating the field's
  table.
- That cache-tag invalidation cascades into
  `layout_builder`'s `ExtraFieldBlockCacheTagInvalidator`, which calls
  Canvas's `BlockManagerDecorator::clearCachedDefinitions()`, which
  **eagerly and synchronously regenerates every Views-block plugin
  derivative** as a side effect.
- Building that requires full Views data for every entity type, including
  `commerce_order` — which, at that exact instant, is *mid-installation* of
  the very `coupons` field being installed. Views' relationship-building
  code (`CommerceEntityViewsData::addReverseRelationships()`) throws
  `SqlContentEntityStorageException: Table information not available for
  the 'coupons' field.`
- Confirmed this is unavoidable through *any* ordering: installing the
  field before the entity types, after, in a single request, across
  separate requests, after a full `ddev restart` — every path hits the
  identical failure, because the trigger is Drupal core's own
  "install schema, then invalidate cache" sequence inside
  `installFieldStorageDefinition()` itself, not something this project's
  scripts do wrong.
- This is a genuine Canvas ↔ Layout Builder ↔ core interaction bug: Canvas's
  eager, synchronous block-derivative rebuild on *any* cache-tag
  invalidation is not standard Drupal core behaviour, and it doesn't
  tolerate being invoked mid-way through an unrelated module's entity
  schema installation. Worth reporting upstream; not something to solve
  here.
- Recovery required manually removing `commerce_promotion` from
  `core.extension`, clearing its last-installed-schema records, and
  dropping a leftover `commerce_promotion_usage` table it manages to create
  before failing — documented in case this needs doing again.

**Decision: don't use commerce_promotion for this feature.** It was never
actually needed — we don't need its admin UI, coupons, or multi-promotion
stacking, just one fixed business rule.

## Resolution: custom Commerce order processor instead
New custom module `web/modules/custom/shh_facility_bundle_discount`:
- A plain config object (`product_ids`, `discount_amount`) — no config
  entity, no admin UI, since there's only one fixed rule to express.
- `FacilityBundleDiscountOrderProcessor` (tagged `commerce_order.order_processor`,
  runs on every order refresh): finds every distinct (start, end) timeframe
  among the order's `bee` items where *all* configured `product_ids` have a
  booking. Each complete match is a "bundle" — a single order can contain
  more than one (e.g. the same three facilities booked again for a
  different slot), each discounted independently. Clears its own prior
  adjustments first (idempotent across repeated refreshes) then reapplies,
  splitting the discount evenly across the bundle's order items (remainder
  to the last one, so tax bases aren't skewed by piling it onto one item).

**Bug hit and fixed**: `ProductVariation::getProductId()` returns a
**string**; the config's `product_ids` are integers; the group-finder's
`in_array($product_id, $product_ids, TRUE)` (strict comparison) silently
matched nothing until both sides were cast to `int`. Caught by adding
temporary debug output rather than assuming the logic was right because it
looked right.

## Acceptance criteria
- [x] Cart accumulates bookings across facilities (prerequisite fix)
- [x] Oval Track + Manège + Lunge Ring booked for the *same* 30-minute slot
      totals 80 DKK (verified live on the cart page: 100.00 subtotal, 20.00
      discount, 80.00 total)
- [x] The same three facilities booked at *different* times do **not**
      receive the discount (verified: two of three matching, one at a
      different time → no discount line, 100 DKK total)
- [x] Discount splits across the three order items (not piled onto one),
      rounding remainder absorbed cleanly (6.67 + 6.67 + 6.66 = 20.00 exactly)

## Related
- [[shh-stables-platform]]
- [[0016-facility-fixed-length-slots]]
- [[0012-cart-hold-concurrency-prototype]]
</content>
