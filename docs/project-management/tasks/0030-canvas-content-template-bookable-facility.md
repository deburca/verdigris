---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-08
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
- [x] **Investigate first, in a disposable/dev context** — before
      committing to a real migration:
  - [x] Create a real `canvas.content_template.node.bookable_facility.full`
        config entity ~~via the Canvas UI~~ (created programmatically —
        the open questions are about *render behavior*, which doesn't
        depend on which surface authored the config) and verify the
        existing `hook_ENTITY_TYPE_view()` CTA hooks
        (`shh_facility_booking_cta`, `shh_facility_credits` — the two
        that target this bundle; `shh_horse_deposit`'s is
        product-side) still render their links once the template is
        active — **they do**, see Resolution
  - [x] Verify `field_availability_hourly`'s `hourly_calendar` FullCalendar
        widget still renders correctly through Canvas's
        prop-source/shape-matching system — **it does not fit**, and
        neither does `field_open_hours`'s `office_hours_table`; see
        Resolution for why partial adoption was rejected too
  - [x] Record the outcome of both checks — recorded below and in
        decision 0019: **stop here, do not migrate**
- [ ] ~~**If viable**, build the real `ContentTemplate`~~ — n/a, the
      investigation concluded "not viable as a full replacement";
      see Resolution
- [ ] ~~Verified end to end over real HTTP~~ — n/a (no migration);
      the *trial* template and the restored classic pipeline were both
      verified over real HTTP
- [x] Update [[0019-canvas-content-templates-for-structured-content]]
      with the outcome (adopted, or blocked on a specific finding)

## Resolution (2026-07-08) — investigated, decided: do not migrate

A real, enabled `canvas.content_template.node.bookable_facility.full`
was created on dev (three representative component instances:
`hestehoj:heading` with `heading_text` bound to `title` via an
`entity-field` prop source, a static `hestehoj:text`, and a
`hestehoj:text` bound to `field_surface`), rendered over real HTTP as
anonymous on `/oval-track`, then deleted. Findings:

**Q1 — the CTA hooks survive. Verified, not assumed.** Both "Book
now" (`shh_facility_booking_cta`) and "Buy a 10-session credit pack"
(`shh_facility_credits`) rendered on the Canvas-templated page,
correctly built as `hestehoj:button` components, after the template
content. `ContentTemplateAwareViewBuilder` only overrides
`getBuildDefaults()` (where it unsets `#theme`, so `node.html.twig`
no longer wraps the page) and `buildComponents()` (where the swapped
display renders the component tree instead of field formatters) —
core's `buildMultiple()` still runs and still invokes
`hook_ENTITY_TYPE_view`, and the hook-added children render as
siblings of the template output. What's bypassed is field-formatter
rendering and `hook_entity_display_build_alter()`, not entity-view
hooks. Prop bindings also verified: title and `field_surface` values
rendered through the template.

**Q2 — the availability calendar does not fit, and it isn't alone.**
`field_availability_hourly` renders via `entity_reference_entity_view`
(the referenced `bat_unit` in its `hourly_calendar` view mode, which
embeds FullCalendar). A `ContentTemplate` *replaces* the whole view
display, so no field formatter runs — the calendar simply vanished.
And Canvas 1.7.1 offers no way to express it in a template: prop
sources extract field *values* (not formatter output), the adapter
list is image/date utilities, and the component sources are
SDC / Block / JS-component / Fallback — nothing renders a field
formatter. The same gap covers `field_open_hours`
(`office_hours_table` — a complex formatter render) and every
label-rendered field (`list_default`, `entity_reference_label`).

**Partial adoption was considered and rejected**: hook-injected
render arrays demonstrably survive (Q1), so the calendar/open-hours
could be re-injected by a companion module around the template — but
then the "migrated" page would be a template for the scalar fields
plus custom code for everything hard, i.e. *both* paradigms on one
page, with required prop bindings editable (breakable) from the
Canvas UI. That's more moving parts than either pure approach, for no
functional gain over the current verified pipeline.

**Bonus finding — ECA/bee interop bug on every template save**:
Drupal CMS ships an ECA rule (`content_template_disable_preview`)
that reacts to any `canvas.content_template.node.*` save by running a
*validated* config action against `node.type.<bundle>`. bee ships
**no config schema** for its `third_party_settings` (stored as
`third_party_settings.bee.bee` on the node type), so validation of
`node.type.bookable_facility` fails and ECA logs a hard error — on
*every* save of a template for this bundle. Worse, the
`preview_mode: 0` write **still went through** despite the reported
validation failure (restored to the sync value afterwards). Any real
adoption for a bee-enabled bundle trips this on every template edit.
Candidate upstream issue against bee: missing config schema for its
node-type third-party settings.

**Decision** (recorded in 0019, now accepted): keep the classic
pipeline + custom-code SDC composition for `bookable_facility` —
the same single approach the whole site uses after 0031/0032.
`commerce_product` remains ContentTemplate-ineligible
(drupal.org/i/3498525 still an open META), so adopting here would
also have created the facility-vs-product split 0019 set out to
avoid. Revisit only if 3498525 lands *and* Canvas grows a sanctioned
way to render formatter-driven output inside a template.

**Cleanup verified**: trial template deleted, `preview_mode`
restored, caches rebuilt; `/oval-track` re-fetched over real HTTP —
calendar and both CTAs back, trial text gone; `drush config:status`
clean (nothing to export — the trial never touched the sync store).

## Related
- [[shh-stables-platform]]
- [[0019-canvas-content-templates-for-structured-content]]
- [[0031-sdc-based-commerce-product-display]]
- [[0025-facility-booking-cta]]
- [[0018-facility-credit-packs]]
- [[0021-public-availability-calendar]]
</content>
