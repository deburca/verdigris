---
type: task
tags: [cms2/task]
status: todo
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-11
updated: 2026-07-11
---
# Task: bee resets price frequency on every facility form save (0,00 DKK bug returns)

## Description
Caught live during [[0041-gallery-lightbox]] verification
(2026-07-11): the client edited Lunge Ring through the node form (a
real-world test of 0040's new media widget, adding a photo) and the
save **silently flipped `field_price_frequency` from `minute` back
to `hour`** — reinstating the exact bug
[[0020-facilities-overview-page]] fixed: bee's `hour`-frequency
pricing truncates to whole hours, so every 30-minute slot booking
computes **0,00 DKK**.

Root cause is upstream, in bee's node-form alter
(`bee.module:303`):

```php
$form['field_price_frequency']['widget']['#default_value'] = 'hour';
```

— applied unconditionally on every hourly BEE node form, stomping
the stored value. Confirmed over real HTTP: Oval Track stores
`minute` but its edit form renders `<option value="hour" selected>`.
**Any staff save of any facility node form re-breaks pricing.**
This also finally explains *how* 0020's mysterious "drift to hour
on all three facilities" happened in the first place: someone
edited the nodes through the form.

Immediate data fix applied under 0041: Lunge Ring set back to
`minute`; Oval Track and Manège verified still `minute` (they were
displayed wrong in the form but not re-saved).

## Acceptance criteria
- [ ] Composer patch on bee (house pattern: `patches/` +
      composer.json + `ddev composer patches-relock` +
      `patches-repatch`, then **grep the patched file** — 0035's
      "Patching…" output lies on malformed hunks): only apply the
      `hour` default when the node is **new**; an existing node's
      stored value must win
- [ ] Verified over real HTTP as staff: Oval Track's edit form
      shows `minute` selected; a real form save leaves
      `field_price_frequency` untouched and a subsequent real
      30-minute booking still charges the correct price (the 0020
      regression test)
- [ ] All three facilities' stored values confirmed `minute` after
      the dust settles
- [ ] Candidate upstream bee issue drafted (this joins the bee
      0.00-DKK pricing quirk and the 0030 config-schema finding on
      the report-upstream list)

## Warning until fixed
Do **not** save any Bookable Facility node form — every save
silently resets the price frequency and facility bookings start
charging 0,00 DKK.

## Related
- [[shh-stables-platform]]
- [[0041-gallery-lightbox]]
- [[0020-facilities-overview-page]]
- [[0016-facility-fixed-length-slots]]
