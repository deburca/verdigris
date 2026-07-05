---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Build the shh entity/content-type model (Store, Horse, Bookable Facility)

## Description
Once modules are enabled ([[0010-enable-shh-commerce-bat-bee-modules]]), build the
base entity model documented in [[shh-stables-platform-model]]: no Commerce store,
product types, or bookable content type exist yet on shh.

## Acceptance criteria
- [x] Commerce Store created (1) — "Stutteri Hestehøj", type `online`, DKK,
      Europe/Copenhagen, set as default store
- [x] Product type **Horse** + Variation type **Horse**: SKU = horse ID, price,
      stock = 1; fields for breed, sex, age/DOB, height, discipline, pedigree,
      vetting/health status, media (photos/video); `sale_state` workflow field
      (`available → reserved → sold` baseline — see
      [[0001-horse-deposit-reservation-flow]] for the extended states)
- [x] Order item type **Horse** (purchase line)
- [x] Content type **Bookable Facility**: BEE-enabled, hourly granularity,
      Commerce payment on, price-per-hour; fields for `facility_kind`
      (arena/hall), surface/dimensions, indoor flag, capacity, peak-pricing config
- [x] BEE configured on Bookable Facility so it provisions the BAT Unit/Type and
      the **Booking** order item type automatically (do not hand-wire BAT to
      Commerce) — BEE's generated order item type is literally named `bee`
      (not `booking`); this is the "Booking order item type" from the
      architecture doc
- [x] One BAT unit confirmed per facility node (not using BEE's multi-unit feature
      — facilities are specific, not interchangeable inventory)
- [x] Sample horse product and sample facility node created and verified end to
      end (view page, add to cart)

## Resolution (2026-07-05)

Built via `drush php:script` (programmatic entity/config API calls), not the
admin UI, for repeatability. Prerequisite not listed in
[[0010-enable-shh-commerce-bat-bee-modules]]'s module list: **`commerce_payment`**
also had to be enabled — BEE's node-type-edit form explicitly requires it (and
an existing Commerce Store) before its "enable payment for bookings" checkbox
is even selectable.

**Built:**
- Commerce Store "Stutteri Hestehøj" (DKK — imported via
  `commerce_price.currency_importer` since programmatic store creation doesn't
  auto-import currencies the way the UI wizard does).
- Product type `horse` / variation type `horse` / order item type `horse`, with
  10 custom fields on the variation bundle (`field_breed`, `field_sex`,
  `field_date_of_birth`, `field_height_hh`, `field_discipline`,
  `field_pedigree`, `field_vetting_status`, `field_health_notes`,
  `field_media`, `field_sale_state`), plus form/view display components so
  they're actually usable in the UI (not just present in config).
- Content type `bookable_facility` with `field_facility_kind`, `field_surface`,
  `field_dimensions`, `field_indoor`, `field_capacity`,
  `field_peak_pricing_notes` (peak-pricing is a placeholder field pending
  [[0014-pricing-rule-entity]]).
- BEE enabled on `bookable_facility` via `bee_set_bee_to_node()` (the same
  function the admin form's submit handler calls) with `bookable_type: hourly`,
  `payment: 1`. This auto-provisioned: `bat_unit_type` "Bookable Facility"
  (bundle `hourly`), order item type `bee`, product variation type `bee`,
  product type `bee_bookable_facility`, and `field_availability_hourly` /
  `field_open_hours` / `field_use_open_hours` / `field_product` /
  `field_price` / `field_price_frequency` on the node type.
- Sample content: horse product "Freja — Danish Warmblood mare" (variation
  SKU `HORSE-0001`, 45,000 DKK) and facility node "Outdoor Arena 1" (150
  DKK/hour). Confirmed a BAT Unit was auto-created and correctly linked on
  node save (`field_availability_hourly` → Unit 1 → `unit_type_id` → the new
  "Bookable Facility"/hourly UnitType).
- End-to-end verified over real HTTP: product page renders with all fields
  (200), facility node page renders with all fields (200), and a full
  add-to-cart POST against the product's add-to-cart form succeeded — the
  item appeared on `/cart`.

**Bugs hit and fixed (same root cause as task 0010, recurring — this is
systemic to this BAT/BEE RC release, not a one-off):**
- The stale-entity-field-manager-cache bug from 0010 recurred generically:
  creating a `FieldStorageConfig` and immediately creating its `FieldConfig`
  in the *same PHP request* throws "field storage does not exist" even though
  it was just saved. Fix used throughout this task: call
  `\Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();`
  immediately after every `$storage->save()`, before creating the field
  instance — this fully prevents the bug without needing a separate `drush
  cr` between every field (a cleaner fix than 0010's workaround). One `drush
  cr` was still needed once, to clear a *persistent* cache left over from an
  earlier run that had already crashed before this fix was in place.
- `bee_set_bee_to_node()` hit the identical bug internally when it created the
  `bee` `bat_booking` bundle (5 fields created back-to-back in
  `bee_create_bee_booking_type()`, same as `bat_booking`'s own "standard"
  bundle in 0010). One field (`booking_event_series_reference`) was left
  missing; completed manually with
  `bee_create_booking_event_series_reference_field()` after a `drush cr`,
  then re-ran `bee_set_bee_to_node()` (safe/idempotent — internal `if
  (!isset(...))`/`if (ConfigEntity::load() === NULL)` guards skip anything
  already created) to finish provisioning the product/order item types and
  node fields.
- **Pre-existing upstream bee.module gap (not caused by this session):**
  `bee_add_node_field()` always creates `commerce_order_item.bee.field_node`
  with `handler_settings.target_bundles: {}` (empty), which Drupal's entity
  reference validation flags as "no longer has any valid bundle it can
  reference" (logged as a Critical watchdog entry on every cache rebuild).
  This isn't blocking (the field isn't part of the actual cart/booking flow —
  no other code in `bee.module` writes to it), but was fixed anyway by
  setting `target_bundles: ['bookable_facility' => 'bookable_facility']`.

## Known follow-up correction
Stutteri Hestehøj sells **only Icelandic horses** — this wasn't captured when
the fields above were designed. The `horse` variation type is missing a
gaits field (tölt / flying pace are essential buyer-facing info for this
breed), and the sample product created here ("Freja — Danish Warmblood
mare") is factually wrong and needs replacing. Tracked in
[[0014-icelandic-horse-gaits-field]] — doesn't change this task's completion
status (the base model this task scoped is built and working), but should be
done before this content is shown to anyone.

## Amendment: order type reassignment
[[0018-separate-order-types-horse-vs-booking]] moved
`commerce_order_item_type.horse` from `orderType: default` to
`orderType: horse_sale` (a new order type, its own checkout flow, number
pattern, and cart expiration). Built here, changed there — noted for anyone
tracing why the "horse" order item type's config doesn't match what this
task originally set up.

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0010-enable-shh-commerce-bat-bee-modules]]
- [[0012-cart-hold-concurrency-prototype]]
- [[0013-mixed-order-checkout-prototype]]
- [[0014-icelandic-horse-gaits-field]]
- [[0018-separate-order-types-horse-vs-booking]]
</content>
