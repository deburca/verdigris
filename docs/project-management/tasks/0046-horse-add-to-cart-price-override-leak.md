---
type: task
tags: [cms2/task]
status: done
priority: high
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

> **The "verify rather than assume" instruction paid off: the risk
> was NOT low. This was a live, exploitable price-manipulation
> vulnerability — see the Resolution.** Priority raised
> medium → high on that finding.

## Acceptance criteria
- [x] A `commerce_order_item.horse.add_to_cart` form display exists
      with `unit_price` (and other internals) hidden — quantity stays
      hidden too (a horse is quantity 1 by nature; note the contrast
      with feed, where quantity is the point)
- [x] Verify over real HTTP that an anonymous forged POST attempting
      a unit-price override on a horse does NOT change the charged
      price (whether it ever could is the "verify rather than assume"
      part — record the finding either way)
- [x] Config exported in the same change (decision
      [[0020-shh-config-export-strategy]])

## Resolution (2026-07-12) — this was a real vulnerability

**Exploit confirmed before the fix.** An anonymous, unauthenticated
forged POST to `/product/1` with
`unit_price[0][override]=1` + `unit_price[0][amount][number]=1`
was **accepted**: cart order 50 held Freja — list price 45.000 DKK —
at a unit price of **1 DKK**, `isUnitPriceOverridden() === TRUE`,
order total 1 DKK. Commerce's price resolvers do *not* second-guess
an explicitly overridden unit price; the override is a legitimate
admin feature that was simply reachable by the public because the
bundle had no form display of its own. Nothing else on the platform
would have caught it — 0024's availability checker validates *sale
state*, not price — so a completed checkout would have produced a
placed, "paid" 1 DKK order for a 45.000 DKK horse. Not merely
cosmetic: **a self-service discount button for anyone who opens
devtools.**

**Fix**: explicit `add_to_cart` form displays for every order item
type that lacked one, each exposing only `purchased_entity` and
hiding `unit_price`, `adjustments`, `created`, `status`,
`total_price`, `uid` (and `quantity` — a horse is a unique animal,
quantity 1 by nature; the deliberate contrast with `feed`, where
quantity is the entire point of the bundle):
- `horse` — the exploited one.
- `horse_deposit`, `facility_credit_pack`, `bee` — these build their
  order items through their own custom forms (`PayDepositForm`,
  `BuyCreditPackForm`, bee's `AddReservationForm`), so they weren't
  rendering the vulnerable fallback today, but they inherited the
  same permissive default and would expose it the moment any path
  rendered the standard form. Hardened rather than left to luck.

Only `default` and `feed` (0038) already had explicit displays —
feed's was the template followed here.

**Verified over real HTTP:**
1. **Pre-fix**: the forged anonymous override POST succeeded (cart at
   1 DKK, override flag set) — the exploit above.
2. **Post-fix**: the *identical* forged POST is now ignored — the
   horse enters the cart at its real **45.000 DKK**,
   `isUnitPriceOverridden() === FALSE`. (The POST still returns 200
   and adds the horse; Form API simply refuses to process input for
   an element that isn't in the form — the same mechanism 0024's
   investigation documented for `#access = FALSE`.)
3. **No regressions**: the horse page's normal anonymous add-to-cart
   still works and prices at 45.000 DKK, and still carries the 0001
   deposit CTA; the horse form shows no quantity or unit-price
   controls; `/product/6` (feed) still shows its quantity field and
   no unit_price.
4. All test carts deleted; zero drafts remain.

**Production note, now in the deployment procedure** ("Go-live data
checks"): before go-live, look for `horse` order items with
`overridden_unit_price = 1` — any hit is a suspect order rather than
a real sale. **Ran it on dev: zero horse items are overridden**, so
no real order was ever exploited (only this task's own test carts,
deleted). Important subtlety recorded there too: the query must be
restricted to `type = 'horse'` — `bee` bookings (10/10), the deposit
(1/1) and the credit pack (1/1) all carry
`overridden_unit_price = 1` **legitimately**, because their custom
forms compute prices in code; an unrestricted query is nothing but
false positives.

Config: four new form displays, exported; `drush config:status`
clean.

## Related
- [[shh-stables-platform]]
- [[0031-sdc-based-commerce-product-display]]
- [[0038-straw-and-wrap-sale-items]]
- [[0024-horse-sale-state-enforcement]]
