---
type: task
tags: [cms2/task]
status: backlog
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
- [ ] Decide scope: a dedicated `sold → available` action (mirroring
      0036's form + permission pattern), or a general staff
      sale-state transition surface covering `reserved`/`withdrawn`
      too — record the reasoning either way
- [ ] Staff-gated (restricted permission, own or shared with 0036's
      `release horse deposit reservations` — decide), reachable from
      the product page like 0036
- [ ] Relisting is decoupled from refunding: the action returns the
      horse to `available` and points staff at the originating order
      and its payments (Commerce admin links) rather than attempting
      an automated refund of a full purchase price
- [ ] The originating "sold" order is identifiable from the action
      (so staff can see *what* sale they are undoing), and the action
      is logged/audited — consider whether [[0002-booking-lifecycle-notifications-audit]]'s
      append-only log pattern (booking-scoped today) should get a
      horse-sale sibling rather than bare watchdog entries
- [ ] 0024's enforcement continues to hold: after un-selling, the
      horse is purchasable again; a horse merely *being looked at* by
      this form is not
- [ ] Verified over real HTTP: complete a real purchase (horse flips
      `sold` automatically), un-sell it via the new action as staff,
      then complete a second real purchase of the same horse
- [ ] Update the 0001 workflow notes if `reserved`/`withdrawn`
      handling is deliberately left out (so the gap stays visible)

## Related
- [[shh-stables-platform]]
- [[0036-staff-release-deposit-reservation]]
- [[0024-horse-sale-state-enforcement]]
- [[0001-horse-deposit-reservation-flow]]
- [[0002-booking-lifecycle-notifications-audit]]
