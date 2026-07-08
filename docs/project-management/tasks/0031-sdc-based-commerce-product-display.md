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
# Task: Investigate and implement SDC-based display for horse product pages

## Description
Follow-up to [[0019-canvas-content-templates-for-structured-content]]
(deferred, not decided): `/product/{id}` (`commerce_product`, bundle
`horse`) currently renders through the classic Drupal entity-view
pipeline, the same as `bookable_facility` before
[[0030-canvas-content-template-bookable-facility]].

Unlike `bookable_facility`, **Canvas's `ContentTemplate` mechanism
cannot be used here at all** — it's hard-restricted to the `node`
entity type (a config schema `Choice: [node]` constraint plus a
hardcoded `=== 'node'` check in `ContentTemplateHooks::entityTypeAlter()`,
confirmed still present in the installed Canvas 1.7.1), and
`commerce_product` is a different entity type entirely. This is
tracked upstream as [drupal.org/i/3498525](https://www.drupal.org/i/3498525)
and was not resolved at the time decision 0019 was written — check its
status first, since if it has since landed, this task should default
to the same `ContentTemplate` approach as 0030 instead of the
alternative below.

**A viable path that doesn't depend on that upstream restriction at
all**: tasks 0019–0023 already proved that rendering a page with SDC
components (`hestehoj:card`, `hestehoj:button`, `hestehoj:text`, etc.)
via direct PHP `#type: component` render arrays — not Canvas's
`ContentTemplate` — works for any entity type, since it's plain custom
code, not something Canvas's entity-type restriction gates. The same
approach (a `hook_entity_view_alter()` for `commerce_product`, or a
dedicated view builder, replacing the default field-formatter output
with a component-based render array built from the variation's field
values) could give `/product/{id}` the same visual consistency as the
rest of the site without waiting on upstream Canvas support.

## Acceptance criteria
- [x] Check [drupal.org/i/3498525](https://www.drupal.org/i/3498525)'s
      status — re-checked 2026-07-08: still an **open [META]**, no fix
      merged, `ContentTemplate` still `node`-only. So the custom-code
      path below applies, as anticipated.
- [x] If still unresolved, prototype the custom-code approach:
  - [x] A `hook_entity_view_alter()` (or equivalent) for
        `commerce_product` bundle `horse`, full view mode, that
        replaces the default field-formatter render with SDC component
        render arrays for: title, price, breed, gaits (reusing the
        gait-label-resolution logic already written for
        [[0019-horse-catalog-page]]'s catalog controller — factor it
        out to a shared helper rather than duplicating it), sex,
        discipline, height, date of birth, pedigree, vetting status,
        health notes, sale state, and media/thumbnail (reusing
        `HorseCatalogController`'s media-props helper, also worth
        factoring out)
  - [x] Confirm the existing `shh_horse_deposit_commerce_product_view()`
        CTA hook (the "Pay a deposit instead" link) and the standard
        Commerce `AddToCartForm` (title/price fields, quantity, submit
        button, plus [[0024-horse-sale-state-enforcement]]'s
        availability-checker-driven validation and its
        `shh_horse_sale_state` module's button-hiding form-alter) all
        still work correctly once the surrounding display is component-based
        — the add-to-cart form itself is not something to rebuild, only
        the informational field display around it
- [x] Verified end to end over real HTTP: both sample horses' product
      pages render correctly with the new display, add-to-cart and pay
      deposit both still work, and the sale-state-unavailable messaging
      (0024) still displays correctly for a sold/reserved horse
- [x] Update [[0019-canvas-content-templates-for-structured-content]]
      with the outcome

## Resolution (2026-07-08)

Upstream `drupal.org/i/3498525` re-checked first: still an **open
[META]** ("Allow Canvas to be used on any content entity type"), no
fix merged — `ContentTemplate` stays `node`-only, so `commerce_product`
can't use it. Implemented the **custom-code SDC path** as the task
anticipated.

New module `web/modules/custom/shh_horse_product_display`: a single
`hook_ENTITY_TYPE_view_alter()` for `commerce_product` bundle `horse`
(default/full view modes). It removes the classic output — the
`variation_*` field formatters Commerce's ProductViewBuilder injects,
plus the duplicate display title (the page-title block already renders
the H1) — and re-presents the same data as hestehoj SDC components:
`hestehoj:heading` (price), `hestehoj:badge` (sale-state + one per
gait), `hestehoj:text` (a key-facts definition list: breed, sex,
discipline, vetting, born, height), `hestehoj:image` (the media
thumbnail), and `hestehoj:heading`+`text` narrative sections for
pedigree and health notes (only when populated).

**Why an *alter* and not a view builder / `hook_ENTITY_TYPE_view()`:**
running last on a `$build` that already contains every other module's
additions means the add-to-cart form (`variations` — carrying 0024's
availability-checker validation and unavailable-horse messaging) and
`shh_horse_deposit`'s `shh_pay_deposit_link` CTA survive untouched by
construction. The task's explicit boundary — "the add-to-cart form
itself is not something to rebuild, only the informational field
display around it" — falls out for free.

**Shared helpers factored out** (the task's "rather than duplicating
it" requirement): `HorseCatalogController`'s gait-label resolution and
image-media-props logic moved into `shh_common` as
`shh_common_list_string_labels()` and `shh_common_image_media_props()`;
the controller now calls them, and this module reuses both. (While
there, renamed 0035's `shh_ensure_menu_link` →
`shh_common_ensure_menu_link` across all six files so the module's
functions are consistently prefixed — phpcs Drupal sniff, which 0035
hadn't run on `shh_common`; now clean.)

One real bug found and fixed mid-verification: the key-facts HTML and
the `->processed` narrative fields were being autoescaped by the text
component's `{{ text }}` output (raw `<dl>`/`<p>` tags showing on the
page). Fixed by passing `Markup`/`MarkupInterface` objects, not
strings — the same pattern `FacilitiesOverviewController` already
uses. The facts HTML is built with `htmlspecialchars()` on every
value, so marking it safe is correct; the narrative fields are
`->processed` (already filtered by their text format).

Verification over real HTTP:
- Both sample pages (`/product/1` reserved-deposit, `/product/3` sold)
  render the component display; gaits show as badges, facts as a
  styled list, pedigree/health as their own sections.
- Temporarily flipped horse 1 to `available`: add-to-cart button
  present, deposit CTA present, catalog lists it. A real **anonymous**
  add-to-cart POST created `horse_sale` draft order 40 with Freja at
  45.000 DKK — proving the untouched cart form still works through the
  new display. Draft cleaned up, horse restored to `reserved-deposit`
  afterwards.
- Forged anonymous add-to-cart POST on the **sold** horse 3: rejected,
  HTTP 200 re-render with "no longer available for purchase." (0024's
  message), **zero** new order items referencing variation 3 (the four
  existing references are all `completed` historical orders). 0024's
  server-side boundary holds under the new display.
- phpcs (Drupal + DrupalPractice) clean; all pages 200; no new
  watchdog errors.

**Pre-existing, out of scope (noted, not fixed):** the horse
add-to-cart form still exposes "Override the unit price" and a
currency selector to anonymous visitors (present in the pre-change
baseline too — a `commerce_order_item.horse` form-display config
oddity, not display-layer). Flagged for a possible small follow-up;
this task deliberately did not touch the cart form.

**Config:** `shh_horse_product_display` enabled; exported. No view
display config changed (the classic `commerce_product.horse.default`
display is left intact — the alter operates on its output at render
time, so it remains the fallback if this module is ever uninstalled).

## Related
- [[shh-stables-platform]]
- [[0019-canvas-content-templates-for-structured-content]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0019-horse-catalog-page]]
- [[0024-horse-sale-state-enforcement]]
- [[0001-horse-deposit-reservation-flow]]
</content>
