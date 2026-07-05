---
type: task
tags: [cms2/task]
status: blocked
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Tax classification for horses vs facility bookings

## Description
Horses (goods) and facility time (service) may carry different Danish VAT
treatment. Not yet reviewed or configured in Commerce tax settings.

## Acceptance criteria
- [~] VAT rules confirmed with accountant/DK tax guidance for livestock sale
      vs service rental — **cannot be completed by engineering work**; see
      Resolution. Technical config below reflects the standard-case
      assumption pending that confirmation.
- [x] Commerce tax type/rate configured per order item type accordingly
- [x] Documented in this task (no divergent treatment found between the two
      order item types under Denmark's actual VAT schedule — see Resolution)

## Resolution (2026-07-05)

### What's configured
- **Tax type** `eu_vat`: Commerce's built-in "European Union VAT" plugin,
  enabled, `display_inclusive: true` (prices are entered/shown VAT-inclusive
  — standard EU B2C requirement; customers must see the price they'll
  actually pay)
- **Store**: `tax_registrations` set to `DK`; `prices_include_tax` enabled
- **Order item type `horse`**: `commerce_tax.taxable_type` = `physical_goods`
- **Order item type `bee`**: `commerce_tax.taxable_type` = `services`

### Why horses and bookings ended up with the *same* rate anyway
Denmark is unusual among EU member states: it applies a single flat
**25% standard VAT rate** to nearly everything, with no reduced-rate tier at
all (most EU countries have 2–3 rate tiers; Denmark has had just
standard/zero since 1992, per Commerce's own maintained EU VAT rate table —
`EuropeanUnionVat.php`'s `dk` zone defines only `standard` (25%) and `zero`
(0%), no `reduced`). So classifying `horse` as `physical_goods` and `bee` as
`services` is still the semantically correct thing to do (and matters if
this store ever sells to other EU countries whose schedules *do* vary by
category), but it doesn't currently produce a different number for either
item type — both are taxed at 25%, VAT-inclusive.

**Verified end to end** (real add-to-cart + checkout, both order types,
cleaned up after): a 45,000 DKK horse produces a 9,000 DKK VAT adjustment
(`included: true`, order total stays 45,000 DKK); a 150 DKK facility booking
produces a 30 DKK VAT adjustment. Both exactly 25% of the net price, order
totals unaffected by the tax breakdown becoming visible — correct
VAT-inclusive behavior.

### What genuinely cannot be resolved here — needs a real accountant
This task's first acceptance criterion literally cannot be satisfied by
engineering work, and I'm not going to pretend otherwise. Specific open
items for a real accountant/Skattestyrelsen guidance, not just "confirm the
25% number":
1. **VAT margin scheme (brugtmoms) for horse sales.** Denmark, like the rest
   of the EU, has a used-goods margin scheme that can apply to certain
   livestock sales, where VAT is charged only on the *margin* (sale price
   minus acquisition cost) rather than the full sale price — but only under
   specific conditions (e.g. the horse was acquired from a non-VAT-registered
   private seller). Whether this applies to any of Stutteri Hestehøj's horses
   depends on how each individual horse was acquired (bred in-house vs.
   bought in), which is a business-records question, not a technical one.
   Commerce's tax module has **no built-in support for the margin scheme** —
   if it turns out to apply, that needs custom development, not just
   configuration.
2. **VAT registration status/threshold.** This configuration assumes the
   business is VAT-registered (mandatory in Denmark above ~50,000 DKK annual
   turnover; a small stable might legitimately be below that and not
   VAT-registered at all, in which case none of this should be enabled).
   Confirm actual registration status before this goes live.
3. This was configured and verified as a **general Danish B2C default**, not
   as a substitute for real guidance — status is `blocked` (not `done`)
   specifically because of item 1 and 2 above, both of which need a human
   with real authority over this business's tax affairs, not more
   engineering work.

## Related
- [[shh-stables-platform]]
- [[0018-separate-order-types-horse-vs-booking]]
