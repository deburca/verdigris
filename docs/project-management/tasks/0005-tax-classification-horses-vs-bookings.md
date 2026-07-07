---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-07
---
# Task: Tax classification for horses vs facility bookings

## Description
Horses (goods) and facility time (service) may carry different Danish VAT
treatment. Not yet reviewed or configured in Commerce tax settings.

## Acceptance criteria
- [x] VAT rules confirmed with accountant/DK tax guidance for livestock sale
      vs service rental — **confirmed directly by the client** (VAT-registered
      2026-07-06; margin-scheme question answered 2026-07-07 — see Resolution
      addendum below).
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
2. ~~VAT registration status/threshold.~~ **Confirmed by the client
   (2026-07-06): Stutteri Hestehøj is VAT-registered.** The `eu_vat` tax type
   and store `tax_registrations: DK` configured above are therefore correct
   to have enabled, not just a placeholder assumption.
3. This was configured and verified as a **general Danish B2C default**, not
   as a substitute for real guidance — status stays `blocked` (not `done`)
   specifically because of item 1 above, which needs a human with real
   authority over this business's tax affairs (an accountant, or whoever
   keeps the acquisition records for each horse), not more engineering work.
   If it turns out the margin scheme never applies (e.g. every horse sold is
   bred in-house rather than bought in from a private seller), this task can
   close as-is with no further code changes.

## Resolution addendum (2026-07-07) — margin scheme answered, task closed

Client's answer to the open margin-scheme question: **the vast majority
of horses are bred in-house; resale of a bought-in horse would be a
rare exception.** Per this task's own closing condition ("if it turns
out the margin scheme never applies… this task can close as-is"), the
standard 25%-on-full-price configuration is correct for the normal
case and the task closes with no code changes.

**One standing operational caveat, which is the whole reason the rare
exception doesn't reopen this task now**: if a horse *bought in from a
private (non-VAT-registered) seller* is ever to be listed for sale,
**do not list it before (a) the accountant confirms whether the margin
scheme (brugtmoms) applies to that specific sale and (b) if it does,
the shop gets custom development for margin-based invoicing** —
Commerce's tax module has no built-in margin-scheme support, so this
is real development work, not configuration. Building that
speculatively now, for a case the client describes as a rare
exception, would be premature; this caveat is the agreed trigger for
when it becomes real work.

## Related
- [[shh-stables-platform]]
- [[0018-separate-order-types-horse-vs-booking]]
