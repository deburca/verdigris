---
tags:
  - cms2/decision
status: accepted
created: 2026-07-05
updated: 2026-07-05
decided: 2026-07-05
site: shh
deciders:
  - Architecture Team
---

# 0013: BAT/BEE for hourly facility booking

## Status

accepted — **amended by [[0018-separate-order-types-horse-vs-booking]]**:
the "Single cart/checkout/order pipeline covers both horse sales and
facility bookings" consequence below no longer holds. BAT/BEE as the booking
framework itself is unaffected; only the "one shared order" aspect changed.

## Context

stutteri-hestehoj.dk (shh) requires hourly-slot reservations for riding areas and a
riding hall, alongside horse-for-sale listings that need to go through checkout.
Drupal Commerce was already chosen platform-wide (see 0002) but has no native concept
of time-slot availability or resource booking.

## Decision

Use **BAT** (Booking & Availability Management Tools) as the availability/calendar
framework, and **BEE** (Bookable Entities Everywhere) as the bridge that makes any
content type bookable and pushes bookings into the Commerce cart as order items.

Horses are modeled as Commerce products (not nodes). Riding areas and the riding hall
are modeled as a single "Bookable Facility" content type (BEE-enabled, hourly
granularity), each node backed by its own BAT unit.

## Consequences

### Positive
- ~~Single cart/checkout/order pipeline covers both horse sales and facility
  bookings~~ — superseded, see [[0018-separate-order-types-horse-vs-booking]]
- BEE avoids hand-rolling the Commerce↔availability integration
- One content type serves both riding areas and the hall — no duplicated logic

### Negative
- BAT is in maintenance-fixes-only mode; no active feature development upstream
- BAT's UI/DX is developer-oriented — expect custom theming work for the booking flow
- Adds a non-trivial dependency chain (BAT, BEE, FullCalendar libraries) on top of Commerce

### Neutral
- Concurrency handling (double-booking prevention) is not provided out of the box and
  must be built as a cart-hold mechanism using BAT event states (`available` / `on-hold` /
  `booked`) — see project note for detail

## Alternatives Considered

### Alternative 1: Custom booking entity + Commerce
Full custom-built availability model directly on Commerce. Rejected: reinvents what
BAT already provides (unit/event model, calendar rendering) for no clear benefit.

### Alternative 2: Room Reservations / other booking contrib modules
Evaluated but lack the hourly-granularity + Commerce cart integration BEE provides
out of the box.

## Implementation Notes

- Verify BAT/BEE release compatibility against Drupal 11 / Commerce version in use on a
  throwaway DDEV build before committing (composer dependency resolution is the likely
  friction point)
- Cart-hold TTL should be tied to Commerce cart expiration configuration
- See [[shh-stables-platform-model]] for full entity model and event-state detail

## References

- Related decisions: [[0002-drupal-cms-as-base-platform]]
- Project: [[shh-stables-platform]]
