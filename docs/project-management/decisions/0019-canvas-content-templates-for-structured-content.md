---
tags: [cms2/decision]
status: accepted
created: 2026-07-06
updated: 2026-07-08
decided: 2026-07-08
site: shh
deciders: []
---

# 0019: Canvas ContentTemplate for structured content types (bookable_facility, commerce_product)

## Status

accepted — the deferral resolved empirically by [[0030-canvas-content-template-bookable-facility]]
(2026-07-08): **do not adopt ContentTemplate**; custom-code SDC
composition stays the single site-wide approach. See the 0030 Outcome
section below.

## Context

Tasks 0019–0023 built five new public pages (`/horses`, `/facilities`,
`/pricing`, `/user/{user}/bookings`, plus the existing facility/product
pages) using SDC components (`hestehoj:card`, `hestehoj:button`,
`hestehoj:text`) rendered directly from PHP controllers. Separately,
the individual entity pages this platform already had —
`/oval-track`/`/manege`/`/lunge-ring` (node bundle `bookable_facility`)
and `/product/{id}` (`commerce_product`) — are rendered through the
classic Drupal entity-view pipeline: core's default `field.html.twig`/
`node.html.twig` (overridden in the hestehoj theme with plain Tailwind
utility classes, added while fixing an unstyled-display issue), plus
several `hook_ENTITY_TYPE_view()` implementations
(`shh_facility_booking_cta`, `shh_facility_credits`,
`shh_horse_deposit`) that inject "Book now" / "Buy a credit pack" /
"Pay a deposit" links into the render array.

This raised the question of whether the *individual* entity pages
(`/oval-track`, `/product/{id}`) could also be built with Canvas/SDC,
the way the site's own homepage and the five new discovery pages are,
for a more visually consistent, on-brand result and a single
page-building paradigm across the site — rather than two parallel
rendering approaches (Canvas component trees vs. classic
field-formatter + custom Twig templates).

Research into Canvas 1.7.1 (the version installed here) found:

- Canvas ships a `ContentTemplate` config entity
  (`canvas.content_template.{entity_type}.{bundle}.{view_mode}`)
  specifically for this: a reusable, site-builder-authored component
  tree applied to *every* entity of a bundle+view mode, with field
  values (title, price, capacity, etc.) wired into SDC component props
  dynamically via Canvas's "prop source" / shape-matching system. This
  is a real, currently-usable mechanism, not a hack.
- The theme is already wired to detect it:
  `hestehoj`'s `preprocess_page()` computes `rendered_by_canvas` for
  *any* node bundle by checking whether an enabled `ContentTemplate`
  exists for `(node, <bundle>, full)` — nothing currently triggers this
  for `bookable_facility` only because no such template has been
  created.
- **Critical limitation**: `ContentTemplate` is hard-restricted to the
  `node` entity type — enforced by a config schema `Choice` constraint,
  a hardcoded `=== 'node'` check in
  `ContentTemplateHooks::entityTypeAlter()` (which decorates the view
  builder that actually swaps in the Canvas render tree), and a
  hardcoded `'node'` in the admin UI's
  `ApiUiContentTemplateControllers::listViewModes()`. Every one of
  these carries an explicit `@todo` referencing
  [drupal.org/i/3498525](https://www.drupal.org/i/3498525) ("remove
  the restriction that this only works with nodes"), which is **not
  yet resolved upstream**.
- `bookable_facility` is a `node` bundle, so it *is* eligible for a
  `ContentTemplate` today. `commerce_product` (the horse product page)
  is a **different entity type**, and is not — converting only
  `bookable_facility` would leave `/product/{id}` on the classic
  pipeline, an inconsistent split rather than a uniform improvement.
- Two open, unverified questions would need answering (by prototyping,
  not by reading code) before this is a safe path even for
  `bookable_facility`:
  1. Whether existing `hook_ENTITY_TYPE_view()` implementations (the
     three CTA-injecting hooks above) still fire when
     `ContentTemplateAwareViewBuilder` renders an entity — its own
     description says it bypasses `hook_entity_display_build_alter()`
     entirely, and it's unclear from the code alone whether plain
     entity-view hooks survive that bypass or would need to be rebuilt
     as Canvas component instances instead.
  2. Whether `field_availability_hourly`'s `hourly_calendar` formatter
     (the embedded FullCalendar availability widget — a complex custom
     render, not a scalar value) fits Canvas's prop-source/shape-matching
     model at all, which is documented mainly for simple field types.

## Decision

**Deferred, not decided.** Do not migrate `bookable_facility` or
`commerce_product` entity pages to Canvas `ContentTemplate` right now.
Revisit once:

1. [drupal.org/i/3498525](https://www.drupal.org/i/3498525) lands
   upstream (removing the `node`-only restriction), so `commerce_product`
   could be converted alongside `bookable_facility` instead of only
   one of the two — avoiding a permanent architectural split between
   facility pages and product pages, OR a deliberate decision is made
   that a split is acceptable.
2. The two open questions above are answered empirically (by building
   a real trial `ContentTemplate` for `bookable_facility` in a
   throwaway/dev context and observing whether the CTA hooks and the
   calendar widget still work), not assumed.

Keep the current approach (custom `templates/content/{field,node}.html.twig`
overrides + `hook_ENTITY_TYPE_view()`-based CTA injection) as the
supported pattern for both `bookable_facility` and `commerce_product`
in the meantime.

## Consequences

### Positive
- No risk of breaking the currently-working, verified CTA links
  (Book now, buy credit pack, pay deposit) or the availability calendar
  widget by migrating prematurely.
- Keeps `bookable_facility` and `commerce_product` on the *same*
  rendering approach as each other, rather than introducing an
  inconsistency where only facility pages are Canvas-managed.
- Explicitly tracked here, so the option isn't silently forgotten and
  doesn't need re-researching from scratch next time it comes up.

### Negative
- The site continues to have two parallel page-building paradigms
  (Canvas component trees for landing/discovery pages vs. classic
  field-formatter rendering for facility/product pages) for longer.
- Content editors don't get Canvas's visual editing experience for
  facility/product pages yet, even though it exists for the rest of
  the site.

### Neutral
- This doesn't block any of the work already done (tasks 0019–0023,
  0024, 0025) — those pages are already Canvas/SDC-based via direct
  PHP `#type: component` render arrays (not `ContentTemplate`), which
  remains a valid, independent way to use SDC components without
  needing per-bundle `ContentTemplate` support at all.

## Alternatives Considered

### Alternative 1: Migrate `bookable_facility` to `ContentTemplate` now, leave `commerce_product` on the classic pipeline
Rejected for now — would resolve the visual-consistency goal for two
out of three individual-entity page types while making the split
between facility and product pages permanent-feeling rather than
temporary, and still requires answering the CTA-hook and
calendar-widget questions empirically before it's safe.

### Alternative 2: Add the `component_tree` field type directly to `commerce_product`/`bookable_facility` via Field UI (bypassing `ContentTemplate`)
Rejected — this is the same underlying field type `canvas_page` uses,
but per Canvas's own data model docs it's "currently limited to the
'default' view mode" and isn't the documented/supported
per-bundle-template workflow (no admin UI support, not exercised
anywhere in this codebase outside `canvas_page`). Would be genuinely
experimental/unsupported, not a sanctioned path.

## Implementation Notes

When revisited:
- Check `drupal.org/i/3498525`'s status first; if still open, decide
  explicitly whether a facility-only migration (accepting the
  product-page split) is acceptable rather than assuming it isn't.
- Prototype in a disposable environment: create a real
  `canvas.content_template.node.bookable_facility.full` config entity
  via the Canvas UI, then verify (a) the three CTA hooks still render
  their links, and (b) the availability calendar still renders
  correctly, before treating this as a viable migration path.
- If viable, plan the migration as its own task (content mapping for
  each field, rebuilding the three CTAs as Canvas component instances
  if hooks don't survive, verifying the calendar widget, then a real
  HTTP verification pass matching this project's established
  standard) rather than a quick swap.

**Tracked as** [[0030-canvas-content-template-bookable-facility]]
(the `ContentTemplate` prototype + migration, for `bookable_facility`)
and [[0031-sdc-based-commerce-product-display]] (a custom-code SDC
display for `commerce_product`, since `ContentTemplate` doesn't apply
there — doesn't wait on the upstream restriction lifting, since it
doesn't use `ContentTemplate` at all).

## Outcome — 0031 done via the custom-code path (2026-07-08)

`drupal.org/i/3498525` re-checked 2026-07-08: still an **open [META]**
("Allow Canvas to be used on any content entity type"), no fix merged
— so `ContentTemplate` remains `node`-only and unavailable for
`commerce_product`, exactly as this decision assumed. [[0031-sdc-based-commerce-product-display]]
was therefore implemented on the **custom-code SDC path** (new
`shh_horse_product_display` module: a `hook_ENTITY_TYPE_view_alter()`
for `commerce_product` bundle `horse` that drops the injected
`variation_*` field formatters and re-presents the same data as
`hestehoj:heading`/`badge`/`text`/`image` render arrays — the
0019–0023 discovery-page pattern, gated by nothing Canvas restricts).
Verified over real HTTP: both sample horse pages render the new
display; anonymous add-to-cart on an available horse creates a
`horse_sale` order end to end; 0024's availability checker still
rejects a forged POST on the sold horse with "no longer available"
(no order item created); the 0001 "Pay a deposit" CTA still appears —
all because running as an *alter* leaves every other
`hook_ENTITY_TYPE_view()` addition (the add-to-cart form included)
untouched. This validates the decision's own Neutral note: direct
`#type: component` render arrays are a full alternative to
`ContentTemplate`, needing none of its per-bundle support.

So the ContentTemplate/`node`-only path (0030, still backlog) now
matters only for `bookable_facility`; the product side is settled
without it. Should 3498525 ever land, converting `horse` product
pages to `ContentTemplate` would be a *consolidation* option, not a
prerequisite — the custom-code display is the supported pattern in
the meantime.

## Outcome — 0030 prototyped for real; ContentTemplate not adopted (2026-07-08)

The prototype this decision's Implementation Notes called for was done
by [[0030-canvas-content-template-bookable-facility]]: a real, enabled
`canvas.content_template.node.bookable_facility.full` on dev (title
bound to `hestehoj:heading` via an `entity-field` prop source, a
static and a `field_surface`-bound `hestehoj:text`), rendered over
real HTTP, then deleted. Both open questions are now answered
empirically:

1. **`hook_ENTITY_TYPE_view()` survives.** Both facility CTA hooks
   ("Book now", "Buy a 10-session credit pack") rendered on the
   templated page. The bypass this decision worried about is real but
   narrower than feared: `ContentTemplateAwareViewBuilder` replaces
   field-formatter rendering (and unsets `#theme`), but core's
   `buildMultiple()` still invokes entity-view hooks, whose additions
   render as siblings of the template output.
2. **The availability calendar does not fit — structurally.** The
   template replaces the entire view display, so
   `field_availability_hourly`'s `entity_reference_entity_view`
   formatter (→ `bat_unit` in `hourly_calendar` view mode, the
   FullCalendar embed) never runs, and Canvas 1.7.1 has no prop
   source, adapter, or component source that renders field-formatter
   output. The same gap covers `field_open_hours`
   (`office_hours_table`) and label-rendered fields. A hybrid
   (template for scalars + hook-injected renders for the rest) works
   mechanically but was rejected: both paradigms on one page, more
   moving parts than either pure approach.

Plus an interop bug found in passing: Drupal CMS's
`content_template_disable_preview` ECA rule runs a validated config
action on `node.type.<bundle>` on every `canvas.content_template.node.*`
save; bee ships no config schema for its node-type
`third_party_settings`, so the action errors on every template save
for `bookable_facility` — and its `preview_mode` write lands anyway
despite the reported failure. (Candidate upstream issue against bee.)

**Decision finalized**: keep the classic entity-view pipeline +
custom-code SDC composition (`hook_ENTITY_TYPE_view` /
`hook_ENTITY_TYPE_view_alter` building `#type: component` render
arrays) as the one approach for *all* individual entity pages —
`bookable_facility` and `commerce_product` alike. Revisit only if
drupal.org/i/3498525 lands (lifting `node`-only, so products could
follow) **and** Canvas grows a sanctioned way to render
formatter-driven output inside a template.

Note this decision also spawned [[0032-adopt-footer-navbar-sdc-components]]
(the theme's `navbar`/`footer` slotted SDCs), done in the same
session by the *same* custom-code mechanism (block plugins rendering
`#type: component`), not Canvas page regions — see that task and the
0032 note below for why page-region composition was not adopted.

## References

- Canvas docs: `web/modules/contrib/canvas/docs/config-management.md`
  §3.2 (`ContentTemplate`), `data-model.md`, ADR
  `0006-One-field-row-per-component-instance.md`
- Upstream issue: [drupal.org/i/3498525](https://www.drupal.org/i/3498525)
  — "remove the restriction that ContentTemplate only works with Node"
- Related decisions: [[0003-canvas-for-page-building]],
  [[0004-sdc-component-architecture]],
  [[0007-site-specific-custom-themes]]
- Project: [[shh-stables-platform]]
