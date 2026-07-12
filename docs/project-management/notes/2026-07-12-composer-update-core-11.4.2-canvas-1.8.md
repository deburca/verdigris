---
type: note
tags: [cms2/note]
site: shh
created: 2026-07-12
---
# Composer update 2026-07-12 — core 11.4.2, Canvas 1.8.0

`composer update -W` run at Paddy's request, with patch monitoring.
DB snapshot taken first (`ddev snapshot --name
pre-composer-update-2026-07-12`).

## What moved (19 packages)

- **drupal/core 11.4.1 → 11.4.2** (+ scaffold/project-message/
  recipe-unpack/vendor-hardening)
- **drupal/canvas 1.7.1 → 1.8.0**
- **drupal/webform 6.3.0-rc2 → 6.3.0** (finally out of RC)
- drupal/eca 3.1.3 → 3.1.4, drupal/easy_email 3.0.8 → 3.0.9
- drupal/ai 1.4.3 → 1.4.4 (+ ai_agents, ai_provider_amazeeio,
  ai_provider_openai)
- drush 13.7.4 → 13.7.6, guzzle 7.13.1 → 7.14.0, league/commonmark,
  php-tuf/composer-stager, phpunit/phpstan (dev)

No security advisories. `updb` clean. Every public page, product,
facility and staff surface (booking calendar, all three reports)
verified 200 over real HTTP with galleries, add-to-cart, deposit CTA,
availability calendar and per-bale labels intact. No new log errors —
the ECA `DeleteAction` deprecation noise dates to 2026-07-06 and is
unrelated.

## Config drift: Canvas 1.8.0 (benign, exported)

Canvas 1.8.0 derives and stores new **`derived_schema_metadata.string_shape`**
per component prop, so all 49 `canvas.component.sdc.*` config items
changed — purely additive. Exported per decision
[[0020-shh-config-export-strategy]]; without exporting, the next
`config:import` would have fought the database.

## Patch monitoring — 9/10 fine, 1 long-broken

Patches were verified by **grepping the patched files**, not by
trusting composer's output (tasks 0009/0035: `patches-repatch` prints
"Patching …" even when a hunk silently fails). All four bee patches,
bat, bat_api, canvas (re-applied cleanly to 1.8.0), byte_theme and
drupal_cms_helper: **applied**.

**`drupal/mercury` — the patch had been silently failing since June.**
`No available patcher was able to apply patch` (11 of its 14 file-hunks
unapplied). **Not caused by this update**: mercury has been at 1.0.5
with June-dated files, and the hunks were already unapplied before
anything was run today. Classic 0009/0035-class silent failure.

Two discoveries about it:

1. **Most of it is obsolete.** `patch --dry-run` reports "previously
   applied" on most hunks — mercury 1.0.5 upstream adopted the custom
   prop schema (`overlay_opacity` on cta, the enums). Only the ten
   `format: uri-reference` removals still carry intent.
2. **Canvas 1.8.0 raises the stakes.** Because the patch is unapplied,
   the strict constraint is now baked into config as
   `derived_schema_metadata.string_shape.format: uri-reference` on
   mercury's link props. Harmless under Canvas 1.7 (which derived no
   such metadata); live under 1.8.

### Resolution (Paddy's call)

- **Regenerated** the patch against mercury 1.0.5 as
  `patches/mercury-relax-uri-reference-format.patch` — 10 files, 10
  removals, applies cleanly with no fuzz and no rejects (verified).
- **Deliberately NOT registered in composer.json**, and the stale
  `mercury-component-schema-customizations.patch` retired. A patch
  that can never apply is a landmine: every `patches-repatch` deletes
  and reinstalls the theme, and the site demonstrably runs fine with
  mercury pristine.
- **Documented in `patches/README.md`** — what it does, why it's
  shelved, the exact symptoms that mean "activate it", and the
  copy-paste steps to do so.

**The residual risk, stated plainly:** shh renders with `hestehoj`'s
own components, not mercury's, so nothing is broken today. But mercury
is the source theme behind `hestehoj`, `quick_silver` and
`zwarte_piet`, and its components stay available in the Canvas library
with the strict format now recorded. If a Canvas component with a link
prop (`url`, `cite_url`, `href`, `button_url`) ever refuses to
validate or save — an anchor-only `#section`, an empty value, a token —
that is this patch's cue. `patches/README.md` has the activation
recipe.

## Related
- [[shh-stables-platform]]
- `patches/README.md` (canonical: active + shelved patches, and the
  "grep the file, don't trust the output" rule)
