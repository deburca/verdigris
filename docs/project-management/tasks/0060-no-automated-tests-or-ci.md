---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: No automated tests or CI for the custom modules

## Description
Confirmed 2026-08-22: zero `tests/` directories exist across the
~40 custom modules under `web/modules/custom` (`find ... -type d -name
tests` returns nothing), and there is no `.github/workflows` directory
— no CI pipeline at all. `phpunit/phpunit` is present in
`composer.json` but unused.

This project's custom modules encode real business/money logic —
pricing (`shh_facility_credits`, `shh_pricing_comparison`,
`shh_facility_bundle_discount`), refund/cancellation policy
(`shh_cancellation_policy`, `shh_horse_deposit`), and sale-state
enforcement (`shh_horse_sale_state`) among them. Every regression check
across this project's ~52-task history has been manual re-verification
over real HTTP, task by task — which has worked through discipline
(and has genuinely caught real bugs, e.g. 0020's silently-drifted
pricing, 0043's form-reset bug), but it doesn't scale and leaves
nothing to automatically catch a future regression in, say, the
discount/rounding logic touched again this session.

## Acceptance criteria
- [ ] Decide scope: start with Kernel tests for the highest-risk pricing
      logic (`FacilityPricingHelper`, `FacilityBundleDiscountOrderProcessor`)
      rather than attempting full coverage at once
- [ ] `phpunit.xml` configured, `SIMPLETEST_DB`/`SIMPLETEST_BASE_URL`
      wired for DDEV
- [ ] At minimum one CI workflow (GitHub Actions) running `phpcs` and
      any tests added, on push/PR to `main`
- [ ] Document the testing convention in this project's docs so future
      tasks add tests as they go rather than needing a second sweep

## Related
- [[shh-stables-platform]]
- Platform-wide, not shh-specific — see the broader note in
  `docs/project-management/infrastructure/missing-configurations.md`
  (stale as of 2026-06-30; this task supersedes its CI/testing items
  for shh specifically)
