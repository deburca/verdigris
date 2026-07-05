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
# Task: Enable Commerce/BAT/BEE modules and set the hestehoj theme

## Description
The shh site is installed (`ddev exec drush --uri=hestehoj.ddev.site status`
bootstraps cleanly, DB `shh` connected) and all required packages are already
vendored (`drupal/commerce` 3.3.6 + submodules, `drupal/bat` 11.1.0-rc11,
`drupal/bee` 11.1.0-rc3 — confirmed via `composer show -i`), but nothing has been
enabled yet and the site is still running the default `mercury` theme instead of
the custom `hestehoj` theme. This is the blocking first step before any entity
modeling ([[0011-shh-entity-content-type-modeling]]) can start.

## Acceptance criteria
- [x] Modules enabled per [[shh-stables-platform-model]] (see "Resolution" below —
      done incrementally, one module at a time, not as a single batch command)
- [x] Dependency/install-time errors resolved and documented here (see
      "Resolution")
- [x] `hestehoj` set as the default theme — and properly *installed* (not just
      pointed to via config), see "Resolution"
- [x] `ddev exec drush --uri=hestehoj.ddev.site cr` clean, no new errors in
      `watchdog:show` after enabling (only a pre-existing, unrelated ECA/`entity`
      module deprecation notice remains, logged on every cache rebuild regardless
      of this work)
- [x] Confirmed `bat_fullcalendar` requirements report is non-blocking (CDN
      fallback expected until [[0009-vendor-fullcalendar-library]] is done)

## Resolution (2026-07-05)

Running the single documented `pm:enable` command with all 13 modules at once
**failed with exit status 1** partway through
(`Attempted to create, modify or delete an instance of field with name
booking_end_date on entity type bat_booking when the field storage does not
exist`, thrown from `bat_booking_entity_insert()` → `FieldConfig::preSave()`).
Drush still marked all 13 modules (plus transitive dependencies) as "Enabled" in
`core.extension`, but a full audit of every enabled module's `config/install`
directory against the actual config table showed **59 mandatory config objects
silently missing** — including both `commerce_product` default type/variation
bundles. The missing product variation type in particular broke the entire
front end: `Commerce\ProductVariationContext` is a global Layout Builder context
provider that unconditionally instantiates a `commerce_product_variation` entity
on every page render, so every page 500'd with
`Missing bundle for entity type commerce_product_variation` the moment
`commerce_product` was enabled without its default bundle.

**Fix applied:**
1. Fully uninstalled all 32 modules that had been installed by the failed batch
   (in three ordered `pm:uninstall` calls: BAT/BEE stack → Commerce stack →
   shared deps `datetime_range`/`rest`/`webform`/etc.) to return to a clean
   baseline (verified: front page 200, no leftover modules).
2. Re-enabled everything **one module at a time**, verifying config completeness
   and front-page health after each step. This isolated a second, independently
   reproducible bug:
   - `bat_booking`'s `hook_entity_insert()` creates three fields
     (`booking_start_date`, `booking_end_date`, `booking_event_reference`) on the
     "standard" booking bundle in a single request. The 2nd and 3rd
     `FieldStorageConfig::create()->save()` calls are not picked up by the entity
     field manager's static cache within the same request, so the immediately
     following `FieldConfig::create()->save()` throws
     "field storage does not exist" even though it was just saved. Confirmed
     reproducible in isolation (not a batch-transaction artifact).
   - **Workaround:** enable `bat_booking` (it will error, but the module ends up
     marked enabled with `booking_start_date` + the bundle created), run
     `drush cr`, then manually complete the missing fields:
     ```bash
     ddev exec drush --uri=hestehoj.ddev.site php:eval \
       "bat_booking_add_end_date_field('standard');"
     ddev exec drush --uri=hestehoj.ddev.site cr
     ddev exec drush --uri=hestehoj.ddev.site php:eval \
       "bat_booking_add_event_reference_field('standard');"
     ```
     (each of these may itself need one `cr` in between if it errors the first
     time — check `config:get field.field.bat_booking.standard.<name>` after).
3. After all 13 target modules + dependencies were enabled one at a time, a full
   diff of every module's `config/install/*.yml` basename against
   `SELECT name FROM config` showed **zero missing items**.
4. Setting the theme via `drush config:set system.theme default hestehoj` alone
   is **not sufficient** — it only points the default-theme config key at a
   theme that was never actually installed, which later 500'd on any page using
   CKEditor5 (`UnknownExtensionException: The theme hestehoj does not exist or
   is not installed`, from `Ckeditor5Hooks::themeCss()`). Correct sequence:
   ```bash
   ddev exec drush --uri=hestehoj.ddev.site theme:enable hestehoj -y
   ddev exec drush --uri=hestehoj.ddev.site config:set system.theme default hestehoj -y
   ```
5. Final verification: all 20 target modules `Enabled` via `pm:list`, `hestehoj`
   theme `Enabled` and set as default, front page and login flow both return
   200, `entity:updates`/`updb` report nothing pending, watchdog clean of new
   errors.

**Resolved:** the `/admin/commerce` 403 seen during scripted `curl` testing was
confirmed to be a testing artifact (real browser session works fine) — not a
real access bug, consistent with the programmatic access-manager check done at
the time.

**Also found and fixed in this session (platform-wide, not shh-specific):**
enabling `bat`/`bee` surfaced that PHP's `display_errors` was `On` for the
web (PHP-FPM) SAPI, which was writing raw, unstyled PHP deprecation notices
(from BAT's many implicit-nullable-parameter deprecations) directly into
rendered page HTML — bypassing Drupal's own error display settings entirely,
since `system.logging.error_level` doesn't govern raw PHP-level `E_DEPRECATED`
output. Fixed via `.ddev/php/error-display.ini`. See
[[missing-configurations]] item 7 for detail.

**Config export note:** none of the above is exported to
`sites/shh/files/sync` yet — per
[[missing-configurations]] item 1, this site has no per-site config-sync
strategy in place. Exporting now would only reinforce the current ad hoc
approach; recommend resolving the config-management strategy before the first
`config:export` for shh.

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0013-bat-bee-booking-framework]]
- [[0011-shh-entity-content-type-modeling]]
</content>
