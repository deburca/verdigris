---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-08
---
# Task: Rate limiting for public availability calendar

## Description
Public read-only availability endpoint (0017) has no rate limiting, exposing it
to scraping or scripted slot-sniping.

## Acceptance criteria
- [x] Rate limit applied to availability/calendar endpoint (flood control or reverse-proxy rule)
- [x] Confirm no sensitive rider PII is exposed in the public calendar response

## Resolution (2026-07-08)

The endpoint is bat_api's `/bat_api/rest/calendar-events`
(`rest.bat_api_events_resource.GET`) — the same REST resource
[[0021-public-availability-calendar]] opened to anonymous and 0025
fixed. Its sibling `/bat_api/rest/calendar-units` needs no limiting
(anonymous has no permission for it — 403 confirmed over HTTP).

**Flood control, not a reverse-proxy rule**: implemented in Drupal via
core's `flood` service so the limit ships with the site and holds on
any future host, instead of depending on an nginx/Varnish config that
does not exist yet (no production host is chosen). Added to
`shh_public_availability` — the module that owns this endpoint's
public exposure, so grant and guard live together:

- **`CalendarRateLimitSubscriber`** on `KernelEvents::REQUEST` at
  priority 30 (after routing resolves the route name, before any
  controller work — a limited request costs nothing). Defaults **60
  requests / 60 s**, overridable without a deploy via
  `shh_public_availability.settings` (`rate_limit_threshold` /
  `rate_limit_window`; defaults live in code, same pattern as
  `shh_horse_deposit`'s `deposit_percentage`, so no install config
  ships). A real visitor's FullCalendar fires one request per month
  navigation, so 60/min is invisible to humans and fatal to polling
  loops.
- **Bucketing**: authenticated users are keyed `user:{uid}` (a
  logged-in scraper can't hide behind — or exhaust — a shared NAT
  IP's allowance), anonymous by client IP.
- **Over-limit response**: plain `JsonResponse` 429 with
  `Retry-After: 60` and `Cache-Control: no-store`.
- New restricted permission **`bypass availability calendar rate
  limiting`** (granted to no role — admins via bypass; for staff
  tooling that legitimately polls).

**Page-cache interaction** (found while testing): anonymous
`page_cache` serves *repeated identical* URLs itself
(`x-drupal-cache: HIT`), so those never reach the limiter — the right
semantics, since cache hits are cheap and the expensive path is
exactly the unique-query-string pattern a scraper generates, which
always misses the cache and is flood-counted. The 429 can never
poison the page cache: `page_cache` only stores
`CacheableResponseInterface` responses and the 429 is a plain
`JsonResponse` (verified: `x-drupal-cache: UNCACHEABLE (no
cacheability)`).

**No PII in the response** (criterion 2): a full-year anonymous pull
returned 20 events whose only keys are `resourceId`, `bat_id`,
`start`, `end`, `title`, `color`, `blocking`, `fixed`, `editable`,
`type`; every title is a facility unit name ("Outdoor Arena 1",
"Manège 1", …) or "N/A" for booked/blocked slots. No rider names,
usernames, uids, or e-mail addresses anywhere (grepped the raw JSON
for all test-account markers). Booked slots are indistinguishable
from staff-blocked ones.

Verified over real HTTP:
1. **Anonymous, 66 unique URLs** (cache-busted): 60×200 then 6×429
   with `Retry-After: 60`.
2. **Bucket separation**: with the anonymous IP bucket full (429),
   a logged-in rider (test_rider) got 200 in their own bucket, and
   then tripped their own limit at exactly 60 requests.
3. **Bypass**: admin ran 63 straight unique requests, all 200.

No config changed (`drush config:status` clean — the permission is
assigned to no role, limits default in code); nothing to export.

## Related
- [[shh-stables-platform]]
- [[0017-anonymous-vs-authenticated-booking-access]]
- [[0021-public-availability-calendar]]
