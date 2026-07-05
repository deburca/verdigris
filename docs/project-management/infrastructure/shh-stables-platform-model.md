---
tags: [cms2/project]
site: shh
created: 2026-07-05
updated: 2026-07-05
---

# shh — Entity / Content-Type Model

Part of [[shh-stables-platform]]. Drupal 11 site combining horse sales, hourly
riding-area reservations, and an hourly riding-hall reservation. Stack: **Drupal
Commerce** (sales) + **BAT** (booking framework) + **BEE** (Bookable Entities
Everywhere — bridges BAT into Commerce). See [[0013-bat-bee-booking-framework]] for
the decision record.

## Upfront modeling decisions

**1. Model horses as Commerce products, not nodes.**
Commerce products are fully fieldable and have canonical pages, so a *Horse* product *is*
the catalog/detail page. A parallel node + product-reference only buys a sync problem.
Each horse = one product, one variation, quantity 1. Only use node-references-product if
horses must appear in non-commerce content flows.

**1a. This is a single-breed catalog: Icelandic horses only.**
Stutteri Hestehøj does not sell any other breed. `breed` is effectively a constant
("Icelandic Horse"), not a field buyers need to filter on — the field that actually
matters for this breed is **gaits**. Icelandic horses are the classic five-gaited
breed: walk, trot, and canter/gallop are the baseline three; whether a horse
additionally has **tölt** and/or **flying pace** (skeið) determines whether it's
"four-gaited" or "five-gaited" (or something in between), and this is one of the
first things a serious buyer looks for. Model this as its own multi-value field
(`field_gaits`), not folded into `discipline`. See
[[0014-icelandic-horse-gaits-field]].

**2. One bookable content type, not two.**
Arenas and the hall are mechanically identical (hourly, single resource, priced per hour).
Use one *Bookable Facility* type with a `facility_kind` field (arena / hall) and per-node
config. Split into two types only if pricing rules or booking constraints genuinely diverge.

## The layers

### Commerce — sales side
- **Store** (1)
- **Product type: Horse** → **Variation type: Horse**
  - SKU = horse ID, price, stock = 1
  - Fields: breed (constant: Icelandic Horse), sex, age/DOB, height, **gaits**
    (walk/trot/canter/tölt/flying pace — multi-value, the key buyer-facing spec
    for this breed), discipline, pedigree, vetting/health status, media
    (photos/video), `sale_state` workflow (`available → reserved → sold`)
  - Do not rely on stock decrement alone — use the explicit `sale_state` workflow
- **Order item type: Horse** — the purchase line, on its own **`horse_sale`
  order type** (own checkout flow, own `cart_expiration`, own number
  pattern — see [[0018-separate-order-types-horse-vs-booking]])

### BEE / BAT — booking side
- **Content type: Bookable Facility** (BEE-enabled, **hourly** granularity, Commerce payment on, price-per-hour)
  - Fields: `facility_kind`, surface/dimensions, indoor flag, capacity, peak-pricing config
  - **One node = one BAT unit** (facilities are specific, not interchangeable inventory —
    do **not** use BEE's multi-unit feature here)
- **BAT Type** (1, hourly) — BEE links the content type to this
- **BAT Unit** (one per facility node) — owns its availability calendar
- **BAT Event** + **Event States**: `available` / `on-hold` / `booked`
  - `on-hold` is the cart-hold added for the concurrency problem
- **Order item type: Booking** (`bee`, BEE-provided) — references the BAT
  event; the reservation line. Stays on the **`default` order type**
  (relabeled "Facility booking" in the admin UI) because `bee.module`'s
  `AddReservationForm` hardcodes that order type id — see
  [[0018-separate-order-types-horse-vs-booking]]

### Shared
- **Order** — **two separate order types**, not one: `horse_sale` for Horse
  order items, `default`/"Facility booking" for Booking order items. Combined
  single-checkout purchases were confirmed not to be a required scenario;
  see [[0018-separate-order-types-horse-vs-booking]] for the full rationale
  (this reverses the original single-mixed-order design below)
- **Rider** — Drupal user + role/profile field gating booking eligibility (membership, waiver),
  plus the Commerce customer (billing) profile

## Entity relationship diagram

```mermaid
graph TD
    HP["Horse product<br/><i>1 variation, qty 1</i>"] --> HOI["Horse order item"]
    BF["Bookable facility<br/><i>BEE node, hourly</i>"] --> BU["BAT unit<br/><i>+ availability events</i>"]
    BU --> BOI["Booking order item<br/><i>bee</i>"]
    HOI --> ORD1["Order type: horse_sale"]
    BOI --> ORD2["Order type: default<br/><i>'Facility booking'</i>"]
    ORD1 --> RIDER["Rider<br/><i>User + eligibility</i>"]
    ORD2 --> RIDER

    classDef sales fill:#E1F5EE,stroke:#0F6E56,color:#04342C;
    classDef booking fill:#EEEDFE,stroke:#534AB7,color:#26215C;
    classDef shared fill:#F1EFE8,stroke:#5F5E5A,color:#2C2C2A;
    class HP,HOI,ORD1 sales;
    class BF,BU,BOI,ORD2 booking;
    class RIDER shared;
```

Legend: teal = Sales (Commerce), purple = Booking (BEE/BAT), gray = Shared.
Two separate orders, not one — see [[0018-separate-order-types-horse-vs-booking]].

## Implementation notes

- **BEE is the glue.** Configure the Bookable Facility content type with BEE; BEE
  provisions the BAT unit and pushes the Booking order item into the cart automatically.
  You do not manually wire BAT to Commerce.

- **Concurrency / cart-hold.** The `on-hold` event state lives on the BAT unit. A cart-add
  transitions the relevant hourly events to `on-hold` with a TTL tied to Commerce cart
  expiration; checkout completion promotes them to `booked`; cart expiry reverts them to
  `available`. This is the hardest problem in the build — prototype it first.

- **Separate order types, not a single mixed Order.** Originally designed as one shared
  order carrying both item types; reversed by
  [[0018-separate-order-types-horse-vs-booking]] once combined single-checkout
  purchases were confirmed unnecessary. Each order type is now homogeneous, so
  checkout completion, refund, and tax logic never need to branch on item type
  within an order.

- **Rider eligibility gate.** Membership / waiver eligibility is not a Commerce or BAT concept
  — it is a custom layer enforced *before* the Booking order item is allowed into the cart.
  Implement as a route access check, a cart constraint (CartProcessor / availability check),
  or both.

- **Timezone / DST.** Hourly bookings make this real. Store in UTC, render in Europe/Copenhagen,
  and test spring-forward / fall-back boundaries explicitly.

- **Prototype order:** (1) the concurrency / cart-hold mechanism — done, see
  [[0012-cart-hold-concurrency-prototype]]; (2) ~~the mixed-order checkout~~ —
  no longer applicable, see [[0018-separate-order-types-horse-vs-booking]] and
  [[0013-mixed-order-checkout-prototype]]'s revised scope.

## Module reference (confirmed on shared platform, 2026-07-05)

Added to the shared `composer.json` (single codebase, per [[0001-multisite-architecture]])
but only enabled on the shh site's active config — vdg and kbg never install this schema.

- **BAT** (`drupal/bat` ^11.1@RC, resolved 11.1.0-rc11) — Booking & Availability
  Management Tools; D11-compatible, maintenance-only. Pinned explicitly at the top
  level (previously only pulled in transitively via BEE's requirement).
  Submodules on disk: `bat_unit`, `bat_event`, `bat_event_series`, `bat_event_ui`,
  `bat_booking`, `bat_calendar_reference`, `bat_facets`, `bat_group`, `bat_options`,
  `bat_fullcalendar`.
- **BEE** (`drupal/bee` ^11.1@RC, resolved 11.1.0-rc3) — Bookable Entities Everywhere;
  hourly granularity + Commerce integration. Ships a `bee_webform` submodule — useful
  tie-in for the waiver capture in [[0003-rider-membership-eligibility-workflow]] since
  Webform is already installed platform-wide ([[0009-webform-for-forms]]).
- **Drupal Commerce** (`drupal/commerce` ^3.3, resolved 3.3.6) — products, cart,
  checkout, orders. Submodules required: `commerce_cart`, `commerce_checkout`,
  `commerce_order`, `commerce_product`, `commerce_tax` (tax needed for
  [[0005-tax-classification-horses-vs-bookings]]).

**Transitive dependencies pulled in** (no action needed, just noted for the record):
`drupal/entity`, `drupal/inline_entity_form`, `drupal/address`, `drupal/profile`,
`drupal/state_machine` (Commerce), `drupal/fullcalendar_library` (BAT's calendar UI).

**Outstanding gap:** `fullcalendar_library` expects local assets at
`web/libraries/fullcalendar` and `web/libraries/fullcalendar-scheduler`; neither is
vendored yet, so it currently falls back to loading FullCalendar from a CDN
(non-blocking `REQUIREMENT_INFO`, not an install blocker). Also flagging a likely
FullCalendar Scheduler commercial-license requirement. See [[0009-vendor-fullcalendar-library]].

**Enable only on shh:**
```bash
drush --uri=stutteri-hestehoj.dk pm:enable commerce commerce_cart commerce_checkout \
  commerce_order commerce_product commerce_tax bat bat_unit bat_event bat_booking \
  bat_fullcalendar bee bee_webform -y
```
