---
type: task
tags: [cms2/task]
status: done
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
- [x] Straw and wrap purchasable end to end over real HTTP as a
      non-admin account, including quantity > 1 in one order —
      order 45 below (straw ×3 + wrap ×1, one order)
- [x] Modelling and order-type decisions recorded with reasoning —
      see the Resolution
- [x] New variation types carry no `field_sale_state`; verified the
      horse lifecycle machinery ignores the new items, and a sold
      horse's purchase blocking still holds unchanged
- [x] Correct 25%-inclusive DK VAT on a real cart (`physical_goods`
      tax class, same as horses —
      [[0005-tax-classification-horses-vs-bookings]]) — 99,00 DKK
      included VAT on a 495,00 DKK cart
- [x] Discoverable: a listing page reachable from the main
      navigation (`shh_common_ensure_menu_link`), one card per item
      with price — new `/feed` page; `/horses`
      ([[0019-horse-catalog-page]]) stays horses-only
- [x] Client questions (units/prices, stock, pickup/delivery)
      answered or recorded as explicit open items — prices and
      pickup-only answered same day (see "Client answers" in the
      Resolution); stock tracking still open
- [x] Config exported (`make shh-export`) and committed in the same
      change (decision [[0020-shh-config-export-strategy]])

## Resolution (2026-07-11)

**Modelling — one shared `feed` bundle family, not one per item.**
Product type `feed` ("Feed & bedding") → variation type `feed` →
order item type `feed`; straw and wrap are two *products*, not two
product types. They share every commerce behaviour (quantity goods,
same VAT class, same checkout), so per-item types would be duplicate
config with nothing varying. Unlike `horse`, the product type has
`multipleVariations: TRUE` — unit-size variations (small/large bale)
are plausible later and cost nothing now; `generateTitle: TRUE` so
variation titles follow the product.

**Order type — reused `horse_sale`, relabelled "Product sale".**
[[0018-separate-order-types-horse-vs-booking]]'s split was driven by
cart TTL and login policy; feed matches the horse side of both
exactly (physical goods, 3-day cart, guest checkout allowed, manual
payment), so a third order type would add a third parallel
cart/checkout with zero behavioural difference — and would prevent a
horse and feed ever sharing one order. Only the label changed
(machine name, checkout flow, workflow, number pattern all
untouched); the `HS-` number prefix stays — renumbering mid-history
buys nothing, and it reads as "Hestehøj sale" well enough.

**Horse machinery ignores feed by construction, now verified.** The
feed variation type has no `field_sale_state` — 0024's
`HorseAvailabilityChecker::applies()` keys on that field's presence,
and `HorseSaleCompletionSubscriber` on the `horse` order item
bundle. The feed purchase produced zero `shh_*` watchdog entries.

**What was built (config, exported per decision 0020):**
- `commerce_order.commerce_order_item_type.feed`
  (`orderType: horse_sale`, `taxable_type: physical_goods`),
  `commerce_product.commerce_product_variation_type.feed`,
  `commerce_product.commerce_product_type.feed`, plus a `body`
  ("Description") field on the feed product type reusing the
  platform-wide `commerce_product.body` storage.
- Explicit displays: product form + view displays (title, body,
  add-to-cart), and — the point of the bundle — a feed-specific
  `add_to_cart` form display with **quantity visible**
  (`commerce_quantity` widget) and **`unit_price` hidden**:
  deliberately not copying the horse add-to-cart form, whose
  unit-price-override leak to anonymous visitors (0031 finding) is
  a separate, still-open follow-up.
- Config was built by a one-shot dev script and exported to
  `config/shh/sync` — not `hook_install()` config creation; since
  [[0033-durable-config-strategy-shh]] the export is the durable
  definition. `cex` diff verified by grep (taxable_type, label,
  quantity widget) per the 0035 "don't trust the output" rule.

**New module `shh_feed_catalog`** (mirrors `shh_horse_catalog`):
`/feed` ("Feed & bedding", `view commerce_product` permission),
one `hestehoj:card` per published feed product (plain-text body
teaser + default-variation price), main-menu link at weight 15
between "Horses for sale" (10) and "Book a facility" (20). One
deliberate improvement over the horse controller: the render array
carries the `commerce_product_list` cache tag so a newly added
product invalidates the page (the horse catalog lacks it —
pre-existing, noted, not fixed here).

**Sample content, superseded same day** (content, not config):
initially product 6 "Barley straw (small bale)" at a placeholder
40 DKK and product 7 "Wrap (haylage bale)" at a placeholder
375 DKK, single variation each — replaced by the client-confirmed
year variations below.

### Client answers (2026-07-11, same day)

The client answered most of the open items before this task was
committed, so the changes are folded in here:

- **Variations are per harvest year**, for both products. Built as
  a proper Commerce attribute: new `year` product attribute
  (select element; values 2025/2026 are content entities), attached
  to the `feed` variation type via
  `commerce_product.attribute_field_manager` (so `attribute_year`
  field storage/field and the variation form display came out
  exactly as the admin UI would make them). The add-to-cart form's
  existing `commerce_product_variation_attributes` widget renders
  the year selector with no further work, and `generateTitle`
  yields cart-line titles like "Wrap (haylage bale) - 2026".
- **Confirmed prices, VAT-inclusive** (no longer placeholders):
  straw 2025 **250 DKK** (variation 6, `FEED-STRAW-2025`); wrap
  2025 **350 DKK** (variation 7, `FEED-WRAP-2025`); wrap 2026
  **300 DKK** now (new variation 8, `FEED-WRAP-2026`).
  ⚠ **Operational reminder: wrap 2026 rises to 350 DKK "next
  year"** (i.e. during 2027) — a manual price edit on variation 8;
  nothing automates it and nothing should.
- **Pickup only.** Delivery is not offered through the shop and is
  only discussed directly with the stable — both product bodies now
  say "Collected at the stable; delivery is not available through
  the web shop", and no shipping work happens on this platform.
- Straw was retitled unit-neutral "Barley straw" (the placeholder
  "(small bale)" title claimed a size the client never stated).

The catalog card now shows the **cheapest** active variation price,
prefixed "From" when variations differ (wrap: "From 300,00 DKK"),
instead of the default variation's price.

**Year-variation verification over real HTTP** (same bar as the
original pass): anonymous `/feed` showed "Barley straw · 250,00
DKK" and "Wrap (haylage bale) · From 300,00 DKK"; `/product/7`
rendered the year select (2025 preselected at 350,00). As
`test_rider`: wrap **2026 ×2** (the attribute POST correctly
resolved variation 8) + straw ×1 → cart 600,00 + 250,00 = 850,00
DKK with 170,00 DKK included VAT (exact 25%) → completed order
`HS-10`, pending manual payment 850 DKK, line items "Wrap (haylage
bale) - 2026 ×2" and "Barley straw - 2025 ×1". Test order and
payment deleted afterwards.

**Verified over real HTTP:**
1. Anonymous: `/feed` 200 with both cards + prices; "Feed &
   bedding" in the main navigation; `/product/6` shows description,
   quantity field and Add to cart — and no unit-price override.
2. Real purchase as non-admin `test_rider` (BigPipe no-JS cookie
   for form markup): straw qty **3** + wrap qty 1 → cart showed
   120,00 + 375,00 = 495,00 DKK with 99,00 DKK included VAT (exact
   25%-inclusive) → checkout (stored billing profile) → order 45
   `completed`, number `HS-9`, type `horse_sale`, manual payment
   pending 495 DKK — the same flow shape as 0037's horse orders.
3. Enforcement both directions: with horse 1 flipped `sold`, a
   forged anonymous add-to-cart POST on it was rejected ("no longer
   available for purchase") while an anonymous guest cart took wrap
   ×2 (750 DKK) normally. (The `sold` flip was set/reset directly —
   the automatic flip-on-purchase path is 0037-verified and this
   task doesn't touch it.)
4. Cleanup: horse 1 back to `available`, test orders 45 + the
   anonymous draft 46 and payment 21 deleted. Products 6/7 stay as
   the sample feed catalog.

**Remaining open client items** (prices and pickup/delivery were
answered same day, see "Client answers" above):
1. Stock tracking — currently none: items are always listed while
   published; unpublishing is the only "out of stock" lever.
2. Unit confirmation — prices are assumed per bale; the client
   stated prices without units.
3. During 2027: raise wrap 2026 (variation 8) to 350 DKK — the
   operational reminder from the client answers.

Images for feed (and horses) remain
[[0039-product-images-featured-and-gallery]] — feed product pages
render via standard field formatters (explicit view display), not
0031's SDC module; 0039 is where display work happens.

## Related
- [[shh-stables-platform]]
- [[0039-product-images-featured-and-gallery]]
- [[0018-separate-order-types-horse-vs-booking]]
- [[0024-horse-sale-state-enforcement]]
- [[0005-tax-classification-horses-vs-bookings]]
- [[0020-shh-config-export-strategy]]
