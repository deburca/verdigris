---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: No meta description or Open Graph tags anywhere on the site

## Description
Confirmed by inspecting the `<head>` of the homepage and several
content pages (2026-08-22): zero `<meta name="description">` tags and
zero `og:*`/`twitter:*` tags anywhere. Only the generic Drupal
`<title>` (e.g. "Home | Stutteri Hestehøj", "Lunge Ring | Stutteri
Hestehøj") is present.

This matters concretely for this site: the footer already links a
Facebook and an Instagram page ([[0032-adopt-footer-navbar-sdc-components]]),
so social sharing is an expected traffic path, and a shared horse
listing or facility page currently renders with no title override, no
description, and no preview image on any platform. Google search
results also fall back to an arbitrary page snippet instead of a
written description.

## Acceptance criteria
- [ ] A meta-tag solution in place (Drupal core's Metatag module, or a
      hand-rolled `hook_page_attachments()`/`hook_ENTITY_TYPE_view()`
      addition per content type) — evaluate which fits this project's
      "no unnecessary contrib" pattern before adding a dependency
- [ ] Homepage, horse product pages, facility pages, and `/feed`,
      `/horses`, `/facilities`, `/pricing` all get a real
      `<meta name="description">`
- [ ] At minimum `og:title`, `og:description`, `og:image` (falling
      back to the entity's featured image where one exists) on
      product/facility pages
- [ ] Verified over real HTTP: view-source on at least one page of each
      content type

## Related
- [[shh-stables-platform]]
- [[0032-adopt-footer-navbar-sdc-components]] — the social links this depends on
- [[0039-product-images-featured-and-gallery]], [[0040-facility-images-featured-and-gallery]] — the featured-image source for `og:image`
