---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-07
---
# Task: Booking lifecycle notifications and audit trail

## Description
No record exists of booking state transitions (held, confirmed, cancelled, expired)
or the emails/notifications tied to them. Needed for both rider communication and
support/dispute resolution.

## Acceptance criteria
- [x] Log entity or watchdog channel records BAT event state transitions with actor
  (customer/admin/system-expiry) and timestamp
- [x] Email sent on: booking confirmed, booking cancelled, hold expired
- [x] Admin-created events (0016) logged with same trail

## Resolution (2026-07-07)

New custom module `web/modules/custom/shh_booking_log`: an append-only
content entity `shh_booking_log` (plain base fields, the 0018/0003
house pattern) written by a `BookingLifecycleLogger` service from
`hook_ENTITY_TYPE_insert/update/delete` on **bat_event**, plus
`hook_mail()` with three rider notifications. Admin trail at
`/admin/reports/booking-log` (newest-first table, no row operations),
gated by a new restricted `access booking log` permission. This task's
diff also includes one file in `shh_booking_hold`
(`CheckoutCompletionSubscriber.php`, see bug 2 below).

### Design decisions

1. **Hooked at the bat_event storage level**, not order/checkout
   events: every booking path on this platform ends in a bat_event
   save — 0012's holds/promotes/releases, 0015's cancellations,
   Commerce Cart cron expiry, and critically 0016's staff-created
   availability events which have **no order at all**, ruling out
   order-level hooks. That satisfies the "admin-created events logged
   with same trail" AC structurally.
2. **Logging can never break the booking operation**: everything is
   wrapped in try/catch; a failure only watchdogs (a trail gap is
   better than an aborted checkout, and the gap itself is logged).
3. **Entries must outlive their event**: `event_id` is a plain int,
   not an entity reference; event deletion logs a final
   `to='deleted'` row.
4. **Actor classification** (customer/staff/system): uid 0 → system
   (anonymous sessions can't reach any booking-mutating path per
   decision 0017, so uid 0 means cron/CLI). Otherwise, if an order
   resolves: order's customer → customer, anyone else → staff. If
   **no** order resolves, classify by bee's own staff permission
   (`manage availability for all bookable_facility nodes`) — see
   bug 1 below for why the original "no order → staff" fallthrough
   was wrong.
5. **Order resolution walks backwards from the event**: bat_booking
   (`booking_event_reference`) → order item (`field_booking`) →
   order. Works during predelete cascades (cart expiry) because the
   item/order still exist at predelete time. Facility: unit id →
   node with `field_availability_hourly` (fallback `_daily`).
6. **Emails** (to `$order->getEmail()`, recorded in the entry's
   `notification` field; routed through mailsystem →
   easy_email_override's default template, same as Commerce receipts):
   - held→booked ⇒ `booking_confirmed`, deliberately in ADDITION to
     Commerce's order receipt — the receipt line is just
     "1 x Oval track 50,00 DKK" with **no slot date/time anywhere**
     (checked a real receipt in Mailpit), so this email is the
     rider's only written record of when their booking is.
   - booked→available ⇒ `booking_cancelled` (customer or staff).
   - held→available AND actor system ⇒ `hold_expired` (cart-expiry
     cron). A rider removing their own cart item gets a log row but
     no email (at predelete the order still resolves → actor
     customer → no match).

### Verification (all four scenarios over real HTTP, non-admin where applicable)

1. **Held + confirmed** — as `soren_holm` (uid 5): booked Oval Track
   30-min slots for 2026-07-10 (orders 32/33/34/35 = order numbers
   7/8/9/10, manual gateway, DK billing). Final clean run: insert row
   `(new)→bee_hourly_on_hold` actor customer; update row
   `on_hold→booked` actor customer / order id / notification
   `booking_confirmed`; Mailpit email with facility, slot, and the
   real order number matching the receipt.
2. **Cancelled** — captured order 35's manual payment
   (`receivePayment()`; checkout leaves it pending and the refund
   path needs a completed payment), then self-service cancelled via
   the dashboard link (`/booking/45/cancel`). Log row
   `booked→available` actor customer / notification
   `booking_cancelled`; event released to available; payment
   `refunded` 50 DKK — logging did not interfere with 0015's refund;
   email in Mailpit.
3. **Hold expired** — added a slot to a fresh cart (order 36), did
   not check out, aged the cart 4h via direct SQL on
   `commerce_order.changed` (`setChangedTime()` + `save()` does NOT
   work — EntityChangedTrait bumps it back), ran `drush cron`.
   commerce_cart deleted the order; log row `on_hold→available`
   actor uid 0 / **system** / order 36 (chain resolves fine during
   predelete) / notification `hold_expired`; email in Mailpit. The
   event survives (released by state flip, not deleted).
4. **Admin-created event (0016)** — as admin, blocked 15:00–15:30 via
   bee's staff screen (`/node/3/availability`, form
   `bee_update_availability_form`). Log row
   `(new)→bee_hourly_not_available` actor uid 1 / **staff**, no
   order, no notification, and no email sent.

Side-checks: the update hook's `getOriginal()` comparison fills
`state_from` correctly; `/admin/reports/booking-log` renders for
admin and 403s for a rider (`restrict access: true`); exactly two
rows per normal booking — bee's checkout finalization produces **no
duplicate rows** (its `finalizeCart` is guarded on
`booking_event_reference` being empty, so it never re-saves held
events).

### Two real bugs found and fixed during verification

1. **The cart-add hold row misclassified the rider as `staff`** (and
   carries no order id): `shh_booking_hold::placeHold()` saves the
   event *before* wiring `booking_event_reference`, so at insert time
   the event→booking→order chain resolves nothing and the original
   "authenticated but not the order's customer → staff" fallthrough
   fired. Fixed in `classifyActor()` with the permission-based
   fallback (design decision 4). The insert row's order column
   deliberately stays empty — the chain isn't saved yet at that
   instant; the trail joins on event id.
2. **The confirmation email cited the internal order id, not the
   order number** ("Order number: 33" while the receipt said
   "Order #8"). Commerce's `OrderNumberSubscriber`
   (place.pre_transition, −30) sets the number only on the in-flight
   order object; a storage load still sees NULL until the placed
   order is saved — confirmed empirically with a temporary probe
   (same `spl_object_id`, number NULL). Fixed by moving
   `shh_booking_hold`'s `promoteHolds` from `place.pre_transition`
   to `place.post_transition` (priority 0, still ahead of the
   receipt subscriber at −100), so the logger's independently
   resolved order already carries the real number.

### Notes

- Test data left in place (deliberately, as with the test accounts):
  soren_holm holds confirmed bookings 10:00/11:00/12:00 on
  2026-07-10 (orders 32/33/34, numbers 7/8/9, payments pending);
  order 35 (number 10) is cancelled + refunded; the 13:00/14:00
  slots are released; 15:00–15:30 is staff-blocked (event 32).
- The cancel form still redirects to the homepage — that's
  [[0029-cancel-flow-dashboard-redirect]], pre-existing, not this
  task.

## Related
- [[shh-stables-platform]]
- [[0016-booking-granularity-admin-events]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0015-implement-cancellation-refund-policy]]
- [[0026-rider-account-access-policy]]
- [[0029-cancel-flow-dashboard-redirect]]
