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
- [ ] Check [drupal.org/i/3498525](https://www.drupal.org/i/3498525)'s
      status. If resolved upstream and a Canvas update is feasible,
      treat this task as "use `ContentTemplate` like 0030" instead of
      the custom-code path below, and update this task's approach
      accordingly before proceeding.
- [ ] If still unresolved, prototype the custom-code approach:
  - [ ] A `hook_entity_view_alter()` (or equivalent) for
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
  - [ ] Confirm the existing `shh_horse_deposit_commerce_product_view()`
        CTA hook (the "Pay a deposit instead" link) and the standard
        Commerce `AddToCartForm` (title/price fields, quantity, submit
        button, plus [[0024-horse-sale-state-enforcement]]'s
        availability-checker-driven validation and its
        `shh_horse_sale_state` module's button-hiding form-alter) all
        still work correctly once the surrounding display is component-based
        — the add-to-cart form itself is not something to rebuild, only
        the informational field display around it
- [ ] Verified end to end over real HTTP: both sample horses' product
      pages render correctly with the new display, add-to-cart and pay
      deposit both still work, and the sale-state-unavailable messaging
      (0024) still displays correctly for a sold/reserved horse
- [ ] Update [[0019-canvas-content-templates-for-structured-content]]
      with the outcome

## Related
- [[shh-stables-platform]]
- [[0019-canvas-content-templates-for-structured-content]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0019-horse-catalog-page]]
- [[0024-horse-sale-state-enforcement]]
- [[0001-horse-deposit-reservation-flow]]
</content>
