---
type: task
tags: [cms2/task]
status: done
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Horse sales catalog / browse page

## Description
No public page lists the horses currently for sale — only direct links to
individual `/product/{id}` pages exist. `/admin/commerce/products` is
staff-only. A buyer with no direct link cannot find any horse.

## Acceptance criteria
- [x] A public page/view listing all `horse` products with
      `field_sale_state: available` (exclude
      reserved/reserved-deposit/sold/withdrawn), showing at minimum:
      title, price, breed (constant), gaits, thumbnail
- [x] Linked from primary navigation
- [x] Sold/withdrawn horses not publicly listed (confirm with client
      whether "sold" horses should still show, e.g. as a sold-horses
      gallery for marketing — default assumption here is no)

## Resolution (2026-07-06)

New custom module `web/modules/custom/shh_horse_catalog`:

- **`HorseCatalogController`** at `/horses` — queries `commerce_product_variation`
  entities of type `horse` with `field_sale_state: available` directly
  (not a Views-built page — this project's established preference for
  code over hand-written/UI-built config, same reasoning as the webform
  and order-item-type entities built via the entity API elsewhere),
  deduplicated by parent product ID, rendered one `hestehoj:card`
  component per horse (title linking to the product page, breed, gaits
  resolved from their machine-name values to human-readable labels via
  the field's own allowed-values, and the formatted price).
- A small media-props helper builds the `{src, alt, width, height}` shape
  the card's `media` prop expects from a variation's `field_media`, for
  when a horse eventually has a photo (none currently do — no media
  entities exist on this site yet — verified the empty-media path
  renders correctly, real image rendering not yet exercised against
  real data).
- **A previously-undiscovered, more fundamental gap surfaced while
  verifying "linked from primary navigation" for real**: this site (and,
  checked for comparison, verdigris) had **no rendered navigation
  anywhere at all** — the `main` menu had only a single "Home" link and
  no block/region ever displayed it, despite the theme already shipping
  a fully-styled `templates/navigation/menu--main.html.twig` with
  nothing to invoke it. Fixed with a small separate module,
  `shh_main_navigation`, which places a standard "Main navigation"
  system menu block in the `header` region — scoped to hestehoj only.

Verified over real HTTP: `/horses` lists exactly the one `available`
horse in the current sample catalog (the other, marked `sold` during
task 0024's own verification, is correctly excluded); the nav link
renders and works on every page.

## Related
- [[shh-stables-platform]]
- [[shh-customer-facing-pages]]
- [[0011-shh-entity-content-type-modeling]]
- [[0014-icelandic-horse-gaits-field]]
- [[0024-horse-sale-state-enforcement]]
</content>
