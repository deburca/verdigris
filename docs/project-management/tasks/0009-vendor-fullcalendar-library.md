---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-07
---
# Task: Vendor the FullCalendar library locally instead of CDN fallback

## Description
`bat_fullcalendar` depends on the `fullcalendar_library` module, which expects the
FullCalendar and FullCalendar Scheduler JS/CSS assets at `web/libraries/fullcalendar`
and `web/libraries/fullcalendar-scheduler`. Neither is currently vendored — the module
falls back to loading both from a CDN (`fullcalendar_library_requirements()` reports
`REQUIREMENT_INFO`, not an error, so this won't block install/enable).

Every other JS-dependent module on this platform (Webform's codemirror, signature_pad,
tabby, tippyjs, etc. — see [[0009-webform-for-forms]]) is vendored through Composer via
asset-packagist/custom repository entries rather than relying on a CDN. FullCalendar
should follow the same pattern for consistency, offline dev support, and to avoid a
runtime dependency on a third-party CDN for a checkout-adjacent booking calendar.

**License check required:** the BAT `bat-fullcalendar-scheduler` library explicitly
loads `fullcalendar_library/fullcalendar-scheduler`. FullCalendar's Scheduler plugin
is only free for non-commercial use — commercial use requires a paid license and a
`schedulerLicenseKey` set at runtime, or a GPL-compatible fallback view must be used.
This needs to be confirmed as a licensing/legal item before the booking calendar goes
live, not just a packaging task.

## Acceptance criteria
- [x] FullCalendar (core) ~~and FullCalendar Scheduler~~ JS/CSS vendored via Composer
      (custom repository entry), matching the pattern used for Webform's JS
      dependencies — *Scheduler deliberately NOT vendored, see AC 3*
- [x] Assets resolve locally so no CDN calls happen at runtime — *the real
      consumer turned out to be `bat_fullcalendar`'s own libraries, not
      `fullcalendar_library` (see premise correction below); its library now
      points at `/libraries/fullcalendar` and both calendar pages are
      CDN-free*
- [x] FullCalendar Scheduler licensing confirmed: ~~either a commercial license is
      purchased and `schedulerLicenseKey` configured, or~~ the Scheduler-specific
      (resource/timeline) views are dropped in favor of MIT-licensed FullCalendar
      views only
- [x] Verified with `bat_fullcalendar` enabled on shh (directly on the dev
      site, not a throwaway build — it was already enabled and in use)

## Resolution (2026-07-07)

**The task's premise was stale.** The `fullcalendar_library` module (FC
v3, the paths and `REQUIREMENT_INFO` described above) is **disabled** and
nothing enabled uses it — it's in the codebase only because the
`drupal/bat_fullcalendar`/`drupal/bat_event_ui` *metapackages* (pulled by
`drupal/bat_api`) require it; the enabled runtime module is
`drupal/bat`'s own `bat_fullcalendar` submodule (RC11), which ignores
`fullcalendar_library` entirely and loads **FullCalendar v6 global
bundles straight from jsdelivr** as `external` libraries: the standard
bundle **unpinned** (`npm/fullcalendar/index.global.min.js` — whatever
version jsdelivr serves that day) and the premium scheduler bundle
pinned at 6.1.15. The disabled FC3 module stays installed (composer
dependency chain, harmless); the actual work moved to bat/bee.

**Licensing resolved by dropping Scheduler entirely.** Facts found:
every configured view on this platform is `dayGridMonth`
(`bat_fullcalendar.settings`: view/edit, hourly/daily — all four), a
standard MIT view; bee attached the scheduler variant for hourly types
anyway (public facility pages, staff availability screen, availability
form), whose JS hardcodes premium `resourceTimeline*` toolbar buttons
and **never injects any `schedulerLicenseKey`** (BAT's `gpl` config key
is dead config in this RC — nothing reads it into the JS). So premium
views were user-reachable on every calendar, unlicensed — clicking one
would render FullCalendar's invalid-license warning. Dropping the
premium bundle is both the licensing answer (nothing premium is used or
needed) and a real-behavior fix. Bonus: the standard JS variant also
respects `locale`/`firstDay` and strips event URLs on public pages,
which the scheduler variant didn't.

**Implementation** (all per decision 0006 patterns):

1. `composer.json`: new `fullcalendar` repository entry
   (`fullcalendar/fullcalendar` 6.1.21, npm registry tarball, type
   `drupal-library`, installer-name `fullcalendar` — the tippyjs
   pattern) + `composer require fullcalendar/fullcalendar:6.1.21`.
   Lands at `web/libraries/fullcalendar/index.global.min.js`. 6.1.21 =
   npm latest at vendoring time; the unpinned CDN was already serving
   6.1.x, so this pins current behavior.
2. `patches/bat_fullcalendar-vendor-local-fullcalendar-library.patch`
   (drupal/bat): `bat-fullcalendar-cdn` library now loads
   `/libraries/fullcalendar/index.global.min.js` (local, aggregatable)
   instead of the unpinned CDN URL. The `bat-fullcalendar-cdn-scheduler`
   definition is left untouched — nothing attaches it after the bee
   patch, an unused definition makes no runtime calls, and neutering it
   would silently break genuine scheduler users of the patched file.
3. `patches/bee-use-standard-fullcalendar-not-premium-scheduler.patch`
   (drupal/bee): the three active `bat-fullcalendar-scheduler`
   attachments (hourly branch of `bee_preprocess_bat_fullcalendar` for
   `bee.node.availability` and `entity.node.canonical`, plus the
   availability-screen form alter) now attach the standard
   `bat_fullcalendar/bat-fullcalendar`.

**Verified over real HTTP**: public facility page (anonymous) and staff
availability screen (admin) both 200 with **zero** jsdelivr/CDN
references; `/libraries/fullcalendar/index.global.min.js` serves 200;
the footer JS aggregate (441 KB) contains both the FullCalendar global
bundle and bat's behavior JS — previously the external CDN file could
never aggregate; `drupalSettings.batCalendar` carries everything the
standard variant reads (`initialView=dayGridMonth`, `eventsUrl`,
`locale`, `firstDay`); and the anonymous events REST feed returns real
data (14 events incl. a booked slot) — the standard variant only needs
the events feed, not the units feed the scheduler variant also hit.
Residual: calendar render not re-checked in an actual browser this
session (curl can't execute JS); the standard variant is the same code
path every daily-type BEE site runs upstream.

### Operational notes

- **`patches.lock.json` was stale** (still listed only
  byte_theme/canvas/geofield from before this project's five later
  patches). composer-patches 2.x only applies what's in that lock:
  a plain `composer reinstall drupal/bee` **silently stripped the
  0017 cart patch** along with everything else. Fixed with
  `composer patches-relock` + `composer patches-repatch`; the lock now
  covers all 8 patched packages. **After adding/changing any patch:
  relock, then repatch — never bare `composer reinstall`.**
- The staff availability calendar's toolbar changes from
  resourceTimeline buttons (which showed license warnings) to standard
  dayGridYear/Week/Day buttons — worth a mention to the client if they
  ask where the timeline went.

## Related
- [[shh-stables-platform]]
- [[0013-bat-bee-booking-framework]]
- [[0006-composer-patch-management]]
- [[shh-stables-platform-model]]
- [[0021-public-availability-calendar]]
