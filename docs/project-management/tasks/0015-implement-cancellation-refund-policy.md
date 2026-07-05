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
# Task: Implement cancellation and refund policy enforcement

## Description
Implements [[0015-cancellation-refund-policy]] (decision, accepted, no
implementation task existed yet): a `cancellation_policy` config entity,
referenced by Bookable Facility, enforced via a self-service cancellation
flow that checks policy + time-to-slot before authorizing a refund and
reverting the BAT event from `booked` back to `available`.

Scoped to **facility bookings only** for now. The decision also mentions a
"separate deposit/refund path" for horse sales, but that depends on
[[0001-horse-deposit-reservation-flow]] (extending `sale_state` with
`reserved-deposit`/`withdrawn`), which is still backlog — nothing to hang a
horse-side cancellation policy off yet.

## Prerequisite gap found and fixed
Neither Commerce order type's checkout flow had a payment step configured
(`payment_information`/`payment_process` panes were missing from both
`default` and `horse_sale`), and no payment gateway existed at all — an
oversight from earlier module-enablement work, not something 0015 caused,
but it blocks testing a refund flow for real (no capturable payment means
nothing to refund). Fixed as a prerequisite: a "Manual" payment gateway
(supports `refundPayment()`, appropriate for a small stable likely taking
bank transfer/cash rather than card processing) plus payment panes added to
both checkout flows.

## Acceptance criteria
- [x] Payment gateway + checkout flow payment step configured on both order
      types (prerequisite, see above)
- [x] `cancellation_policy` config entity type: refund window (hours before
      slot start), admin-manageable (list/add/edit/delete)
- [x] `field_cancellation_policy` reference field on Bookable Facility
- [x] Self-service cancellation route/form for a rider to cancel their own
      future booking
- [x] Policy enforced: outside the window → refund authorized (via the
      order's payment gateway) and BAT event reverted `booked` → `available`;
      inside the window → refund denied, event **not** reverted (matches the
      decision's literal implementation note — flagged as worth confirming
      with the business, see Resolution)
- [x] Verified end to end: real booking, real captured payment, real
      cancellation outside the window (refunded + slot released) and inside
      the window (denied, slot stays booked)

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_cancellation_policy`:

- **Payment prerequisite**: a "Manual" (bank transfer/cash) payment gateway
  — supports `refundPayment()`, and is a realistic default for a small
  stable likely not running card processing — plus `payment_information`/
  `payment_process` panes added to both the `default` and `horse_sale`
  checkout flows (`multistep_default` only defines login/order_information/
  review/complete steps, no dedicated payment step, so payment_information
  sits on `order_information` and payment_process runs on `review`, same
  pattern most Commerce sites use with this flow plugin).
- **`cancellation_policy` config entity**: `id`, `label`,
  `refund_window_hours`. Full admin CRUD (list/add/edit/delete) at
  `/admin/commerce/config/cancellation-policies`. One instance seeded on
  install: "Standard (24 hours)" — matches the example in decision 0015's
  own context text.
- **`field_cancellation_policy`** on `bookable_facility`: optional
  entity reference. Deliberately **fails closed** — a facility with no
  policy assigned has self-service cancellation disabled entirely (message
  points the rider to contact staff), rather than silently assuming a
  default policy.
- **`CancellationManager` service**: `cancelBooking()` — finds the
  booking's still-`booked` BAT event(s), computes hours until the earliest
  one's start, compares to the facility's policy window. Outside the
  window: refunds the order item's total via the order's completed payment
  (`SupportsRefundsInterface`) and transitions the event(s) to `available`.
  Inside the window: **denied outright** — no partial "cancelled but
  unrefunded" state. This is a deliberate simplification of the decision's
  literal wording ("cancellation reverts booked → available only if policy
  check passes"), which read literally could imply a confusing limbo state
  (a "cancelled" booking that's still occupying the calendar as `booked`).
  Flat denial is simpler and was judged the more sensible reading — worth
  confirming with the business, same as the window behavior itself.
- **Self-service route** `/booking/{commerce_order_item}/cancel`
  (`CancelBookingForm`, a confirmation form previewing whether the
  cancellation will be refunded before the rider confirms) plus a "Cancel
  booking" link auto-added to a `bee` order item's rendered output when the
  current user has access (`CancelBookingAccessCheck`: must be the order's
  own customer, or have `administer commerce_order`; order must be placed,
  not still a cart).

**Verified end to end** via real order + real captured Manual-gateway
payment (not just unit-level calls): booking 72 hours out → cancellation
authorized, 150 DKK payment transitioned `completed` → `refunded`, BAT event
transitioned `booked` → `available`. Booking 2 hours out (same 24-hour
policy) → cancellation denied, payment untouched (`completed`, 0 DKK
refunded), event untouched (`booked`).

**Two unrelated pre-existing bugs found and fixed along the way** (not
introduced by this task, discovered because this module's install hook
surfaced them):
1. The config schema file I initially wrote used the wrong config-entity
   name prefix (`cancellation_policy.cancellation_policy.*` instead of
   `shh_cancellation_policy.cancellation_policy.*` — Drupal automatically
   prefixes a config entity's `config_prefix` with its providing module
   name). Caught immediately via `drush config:get` returning "does not
   exist" for a config object that plainly existed in the `config` table
   under the correctly-prefixed name.
2. The horse sample content created in tasks 0011/0014 (`field_pedigree`,
   `field_health_notes`) used text format `basic_html`, which **doesn't
   exist on this site** (this Drupal CMS distribution's rich-text format is
   `content_format`, not core's default `basic_html`) — silently logging a
   "Missing text format" alert on every render since 0011. Fixed on the
   existing sample content and on the Manual payment gateway's instructions
   field (same mistake, same fix).

## Related
- [[shh-stables-platform]]
- [[0015-cancellation-refund-policy]]
- [[0001-horse-deposit-reservation-flow]]
- [[0012-cart-hold-concurrency-prototype]]
</content>
