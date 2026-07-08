---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-08
updated: 2026-07-08
---
# Task: Staff action — un-sell a sold horse (return `sold` to `available`)

## Description
Companion to [[0036-staff-release-deposit-reservation]], deliberately
split out. That task covers releasing a **deposit reservation**
(`reserved-deposit` → `available`), where the money side is a single
deposit payment with an existing policy-driven refund path. Un-selling
a **sold** horse is a different animal:

- `sold` is set automatically by
  [[0024-horse-sale-state-enforcement]]'s
  `HorseSaleCompletionSubscriber` the moment a full-price order is
  placed. There is currently **no path back at all** — staff needing
  to relist (a returned horse, a failed payment after placement, a
  buyer backing out of a bank transfer, or plain testing) must edit
  `field_sale_state` by hand through the admin variation form or
  drush, leaving no trace and touching none of the bookkeeping.
- The money side is the **full purchase price**, not a policy-scoped
  deposit: whether to refund, partially refund, or not refund at all
  is a per-case staff/accountant decision, not a rule the platform
  can automate (and the Manual gateway means payment state may not
  even reflect reality). A correct implementation should therefore
  probably **decouple relisting from refunding** — release the horse,
  surface the linked order/payments, and leave money handling to
  staff via Commerce's own order/payment UI.
- There are also `reserved` and `withdrawn` states in the 0001
  workflow with no transitions built around them; deciding this
  task's scope should at least glance at whether one staff-facing
  "set sale state, with an audit trail" surface covers all of it
  better than one-off actions per state.

## Acceptance criteria
- [x] Decide scope: a dedicated `sold → available` action (mirroring
      0036's form + permission pattern), or a general staff
      sale-state transition surface covering `reserved`/`withdrawn`
      too — record the reasoning either way — **dedicated action**,
      reasoning in the Resolution
- [x] Staff-gated (restricted permission, own or shared with 0036's
      `release horse deposit reservations` — decide), reachable from
      the product page like 0036 — **own permission**, reasoning in
      the Resolution
- [x] Relisting is decoupled from refunding: the action returns the
      horse to `available` and points staff at the originating order
      and its payments (Commerce admin links) rather than attempting
      an automated refund of a full purchase price
- [x] The originating "sold" order is identifiable from the action
      (so staff can see *what* sale they are undoing), and the action
      is logged/audited — considered a horse-sale sibling of
      [[0002-booking-lifecycle-notifications-audit]]'s log entity and
      **rejected as disproportionate**, reasoning in the Resolution
- [x] 0024's enforcement continues to hold: after un-selling, the
      horse is purchasable again; a horse merely *being looked at* by
      this form is not
- [x] Verified over real HTTP: complete a real purchase (horse flips
      `sold` automatically), un-sell it via the new action as staff,
      then complete a second real purchase of the same horse
- [x] Update the 0001 workflow notes if `reserved`/`withdrawn`
      handling is deliberately left out (so the gap stays visible) —
      note added to 0001's Resolution

## Resolution (2026-07-08)

Implemented in `shh_horse_sale_state` — the module whose
`HorseSaleCompletionSubscriber` *sets* `sold` (0024), so set and
un-set live together, exactly as 0036's release lives in the deposit
module.

**Scope decision — dedicated `sold → available` action, not a
general sale-state switcher.** A generic "set any state" surface
would bypass the lifecycle bookkeeping each transition needs:
`reserved-deposit → available` must run through 0036's deposit-aware
path (refund-window rules), `sold → available` needs originating-sale
identification (this task), while `reserved`/`withdrawn` are manual
administrative states with no automated bookkeeping at all — already
reachable through the variation edit form under the same admin gate,
per 0001's "admin can transition to withdrawn without a linked
order". One action per lifecycle-owning module keeps each
transition's bookkeeping honest; a shared switcher would be a
footgun. The `reserved`/`withdrawn` gap is now noted in 0001.

**What was built:**

- **`RelistManager`** service:
  `findOriginatingSaleOrder($variation)` (newest *placed* order with
  a `horse` order item for the variation — so a horse sold, relisted
  and sold again points at its current sale, verified below) and
  `relistSoldHorse($variation)` (guards on `sold`, flips to
  `available`, touches **no** order or payment). Return shape:
  `{relisted, order_id, reason}`; a `sold` state with no placed
  backing order still relists, logged as a warning (mirrors 0036's
  orphaned-state path).
- **`RelistSoldHorseForm`** (confirm form) at
  `/product/{commerce_product}/relist`, gated by new restricted
  permission **`relist sold horses`** (granted to no role — admins
  via bypass). *Own* permission rather than sharing 0036's: the money
  exposure differs (a policy-bounded deposit vs a full purchase
  price), so a staff role can be granted deposit-release without
  sale-undo. Before confirming, the form shows **the sale being
  undone**: order link, placed date, total, each payment with its
  state, and a link to the order's payments tab — and states
  explicitly that no refund is attempted.
- **Product-page entry point**: staff-only "Relist for sale (staff)"
  button via `hook_commerce_product_view()` when the horse is `sold`
  and the user has the permission (`user.permissions` cache context;
  survives 0031's view-alter by the same sibling-hook mechanism as
  0036's button).

**Audit decision**: a 0002-style append-only log entity sibling was
considered and rejected as disproportionate — 0002's entity is
justified by its many actor paths (cart holds, expiry, staff BAT
edits, checkout) and rider notifications. Here the single staff
action logs a structured watchdog notice (variation id, acting uid,
originating order id), the `sold` flip itself is already logged by
0024, and Commerce keeps the order/payment records permanently — the
action links straight to them. Revisit if more sale-state
transitions grow automation.

**Verified over real HTTP, full cycle** (as test_rider + admin;
BigPipe note: logged-in pages stream forms as placeholder chunks, so
the verification driver sets the `big_pipe_nojs=1` no-JS cookie to
get synchronous form markup):
1. **Real purchase**: add-to-cart → checkout → complete as
   test_rider (order 43, 45.000 DKK, manual payment `pending`) —
   horse 1 flipped `sold` automatically (0024's subscriber).
2. **While sold**: anonymous saw no relist button and the
   "no longer available" notice; `/product/1/relist` was 403 for
   anonymous *and* rider, 200 for admin; admin saw the button; a
   forged add-to-cart POST was rejected ("no longer available") —
   including *after* staff had GET-viewed the relist form twice, so
   looking at the form releases nothing.
3. **Relist**: the admin form showed order 43, the pending 45.000 DKK
   payment and the no-refund text; real confirm POST → redirect to
   the product with "available for sale again", state `available`,
   watchdog notice recorded, order 43 and its payment untouched.
4. **Second real purchase**: order 44 completed over HTTP — the
   relisted horse was genuinely purchasable — and the horse flipped
   `sold` again. The relist form then pointed at **order 44, not 43**
   (newest-placed-first selection verified).
5. **Cleanup**: relisted again via the real form, test orders 43/44
   and payments deleted; horse 1 ends `available` (its true state —
   the sample catalog keeps its listing).

No config changed (route/permission/form are code; the permission is
deliberately assigned to no role) — `drush config:status` clean,
nothing to export.

## Related
- [[shh-stables-platform]]
- [[0036-staff-release-deposit-reservation]]
- [[0024-horse-sale-state-enforcement]]
- [[0001-horse-deposit-reservation-flow]]
- [[0002-booking-lifecycle-notifications-audit]]
