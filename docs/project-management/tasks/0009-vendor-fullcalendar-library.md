---
type: task
tags: [cms2/task]
status: backlog
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
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
- [ ] FullCalendar (core) and FullCalendar Scheduler JS/CSS vendored via Composer
      (asset-packagist or equivalent custom repository entry), matching the pattern
      used for Webform's JS dependencies
- [ ] Assets resolve to `web/libraries/fullcalendar` and
      `web/libraries/fullcalendar-scheduler` so `fullcalendar_library_requirements()`
      reports `REQUIREMENT_OK`, no CDN calls at runtime
- [ ] FullCalendar Scheduler licensing confirmed: either a commercial license is
      purchased and `schedulerLicenseKey` configured, or the Scheduler-specific
      (resource/timeline) views are dropped in favor of GPL-licensed FullCalendar
      views only
- [ ] Verified on a throwaway build with `bat_fullcalendar` enabled on shh

## Related
- [[shh-stables-platform]]
- [[0013-bat-bee-booking-framework]]
- [[shh-stables-platform-model]]
