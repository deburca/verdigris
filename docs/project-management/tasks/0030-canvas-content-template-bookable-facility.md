---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Investigate and migrate Bookable Facility pages to Canvas ContentTemplate

## Description
Follow-up to [[0019-canvas-content-templates-for-structured-content]]
(deferred, not decided): `/oval-track`, `/manege`, and `/lunge-ring`
(node bundle `bookable_facility`) currently render through the classic
Drupal entity-view pipeline (`templates/content/{field,node}.html.twig`
overrides added while fixing the shh display-styling issue, plus three
`hook_ENTITY_TYPE_view()` implementations injecting CTA links). Canvas
ships a `ContentTemplate` config entity that lets a `node` bundle's
full view mode be composed from SDC components instead — the same
`hestehoj:card`/`button`/`text` components already used for the
discovery pages (0019–0023) — with field values (title, price,
capacity, etc.) wired in as dynamic props, giving a single consistent
page-building paradigm across the whole site instead of two parallel
ones.

`bookable_facility` is a `node` bundle, so it's eligible for
`ContentTemplate` today (unlike `commerce_product` — see
[[0031-sdc-based-commerce-product-display]]). This task is the
prototype step [[0019-canvas-content-templates-for-structured-content]]'s
Implementation Notes call for, done for real rather than assumed.

## Acceptance criteria
- [ ] **Investigate first, in a disposable/dev context** — before
      committing to a real migration:
  - [ ] Create a real `canvas.content_template.node.bookable_facility.full`
        config entity via the Canvas UI and verify the three existing
        `hook_ENTITY_TYPE_view()` CTA hooks
        (`shh_facility_booking_cta`, `shh_facility_credits`,
        `shh_horse_deposit`'s deposit link doesn't apply here but
        confirm none of the three facility-page hooks silently stop
        firing) still render their links once the template is active
        — `ContentTemplateAwareViewBuilder` documents that it bypasses
        `hook_entity_display_build_alter()` entirely, so this is
        genuinely unverified
  - [ ] Verify `field_availability_hourly`'s `hourly_calendar` FullCalendar
        widget still renders correctly through Canvas's
        prop-source/shape-matching system, or determine it doesn't fit
        and needs a different approach (e.g. a custom SDC component
        wrapping the existing formatter output, or leaving that one
        field non-Canvas-managed if Canvas supports partial adoption)
  - [ ] Record the outcome of both checks — if either hook or the
        calendar widget doesn't survive, decide whether to rebuild them
        as Canvas component instances/a custom component, or to stop
        here and update decision 0019 with the concrete blocker found
- [ ] **If viable**, build the real `ContentTemplate` for
      `bookable_facility`'s `full` view mode, mapping every field
      currently shown (`field_facility_kind`, `field_surface`,
      `field_dimensions`, `field_indoor`, `field_capacity`,
      `field_price`/`field_price_frequency`,
      `field_slot_duration_minutes`, `field_cancellation_policy`,
      `field_open_hours`, `field_availability_hourly`) plus the three
      CTA links
- [ ] Verified end to end over real HTTP: all three facility pages
      render correctly, all existing functionality still works (Book
      now, buy credit pack, availability calendar, and — since
      [[0003-rider-membership-eligibility-workflow]] gates booking via
      a form on a *different* route, not this page's own render —
      confirm that flow is unaffected)
- [ ] Update [[0019-canvas-content-templates-for-structured-content]]
      with the outcome (adopted, or blocked on a specific finding)

## Related
- [[shh-stables-platform]]
- [[0019-canvas-content-templates-for-structured-content]]
- [[0031-sdc-based-commerce-product-display]]
- [[0025-facility-booking-cta]]
- [[0018-facility-credit-packs]]
- [[0021-public-availability-calendar]]
</content>
