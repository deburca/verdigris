---
type: task
tags: [cms2/task]
status: done
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
- [x] Composer patch on bee (house pattern: `patches/` +
      composer.json + `ddev composer patches-relock` +
      `patches-repatch`, then **grep the patched file** — 0035's
      "Patching…" output lies on malformed hunks) — see Resolution:
      the branch turns out to run on *edit forms only*, so the
      default is simply dropped rather than gated on `isNew()`
- [x] Verified over real HTTP as staff: Oval Track's edit form
      shows `minute` selected; a real form save leaves
      `field_price_frequency` untouched and a subsequent real
      30-minute booking still charges the correct price (the 0020
      regression test) — 50,00 DKK, not 0,00
- [x] All three facilities' stored values confirmed `minute` after
      the dust settles (and all three published)
- [x] Candidate upstream bee issue drafted (see Resolution)

## Resolution (2026-07-11)

**Patch** (`patches/bee-respect-stored-price-frequency-on-edit.patch`,
registered in composer.json): the offending branch sits inside
bee's `node_.*_edit_form` route match — it **never runs on the node
add form at all**, so the hardcoded
`$form['field_price_frequency']['widget']['#default_value'] = 'hour'`
can never serve its presumable "default for new nodes" purpose; its
only possible effect is overriding an existing node's stored value.
The fix deletes the line (with an explaining comment), letting the
options_select widget carry the stored value as every other field
does. Generated as a real `git diff --no-index` rather than a
hand-written hunk (the 0035 lesson), applied via
`composer patches-relock` + `patches-repatch`, and verified by
grep: the comment is in the installed `bee.module`, the hardcoded
line is gone, and both existing bee patches (0017's cart fix,
0009's fullcalendar variant) survived the repatch.

**Verified over real HTTP as staff (admin)**:
1. Oval Track's edit form now renders
   `<option value="minute" selected>` (it rendered `hour` selected
   before the patch, against a stored `minute`).
2. A genuine full form save of `/node/3/edit` (every form value
   round-tripped and POSTed, "has been updated") left the node
   **byte-identical** to its pre-save field snapshot — frequency
   still `minute`, still published. One harness lesson recorded for
   future scripted node-form saves: gin renders some node-form
   controls (the `status` checkbox) physically *outside* the
   `<form>` element, associated via the HTML `form=""` attribute —
   a parser that only scans inside the form tag drops them and the
   save silently unpublishes the node (bookable_facility has no
   moderation workflow, despite a leftover `basic_editorial`
   dependency in its form-display config, so `status` alone decides
   publication). Caught by the snapshot comparison, node restored.
3. **The 0020 regression test**: as non-admin `test_rider`, a real
   `/node/3/add-reservation` booking of tomorrow 09:00–09:30 went
   to checkout at **50,00 DKK** (10,00 DKK included VAT) — correct
   sub-hour pricing on the very node that had just been through a
   real staff form save. (test_rider needed a temporary active
   membership for 0003's gate — created for the test and deleted
   after; uid 2 had never had one, facility booking being the only
   membership-gated flow.) Cleanup audited by 0002's booking log:
   the cart-add hold (customer) and the release on order deletion
   (system), event 33 — no orphaned hold, test order 48 and the
   temp membership deleted.

**Draft upstream bee issue** (to file against drupal/bee):
> Title: Node edit form alter overrides stored field_price_frequency
> with 'hour' on every save
> bee_form_alter()'s `node_.*_edit_form` branch sets
> `$form['field_price_frequency']['widget']['#default_value'] = 'hour'`
> unconditionally (bee.module, "Payments" details section, hourly
> branch). Because this branch only matches *edit* forms, the line
> cannot act as a new-node default — its only effect is replacing
> the stored value in the widget, so saving the form silently
> flips a 'minute'-priced node back to 'hour'. Combined with
> bee_commerce's hour-frequency price calculation truncating to
> whole hours, any sub-hour bookable node starts pricing bookings
> at 0.00 after any routine content edit. Fix: remove the
> override (the widget already carries the entity's value); if a
> default for new nodes is wanted it belongs in the field config
> or a `node_.*_form` (add-form) branch.

Files: `patches/bee-respect-stored-price-frequency-on-edit.patch`,
composer.json + patches.lock.json. No config or custom-module
changes; nothing to export (`drush config:status` clean).

## Related
- [[shh-stables-platform]]
- [[0041-gallery-lightbox]]
- [[0020-facilities-overview-page]]
- [[0016-facility-fixed-length-slots]]
