# Patches

Composer patches applied to contrib packages, registered in
`composer.json` under `extra.patches` and locked in
`patches.lock.json`.

## Applying / changing a patch (composer-patches 2.x)

```bash
ddev composer patches-relock     # after ANY change to extra.patches
ddev composer patches-repatch
```

**Then grep the patched file for the hunk's added lines.**
`patches-repatch` prints `Patching <pkg>` **even when a hunk fails to
apply and the package is left pristine** — the output is not evidence
(learned in task 0035, again in the 2026-07-12 composer update). A bare
`composer reinstall` applies nothing and silently strips existing
patches (task 0009).

## Active patches

Registered in `composer.json`; all verified applied 2026-07-12 after
the core 11.4.2 / Canvas 1.8.0 update.

| Package | Patch | Retire when |
|---|---|---|
| `drupal/bee` | accumulate cart instead of emptying | — (task 0017) |
| `drupal/bee` | standard FullCalendar, not premium scheduler | — (task 0009) |
| `drupal/bee` | respect stored `field_price_frequency` on edit | [bee #3610134](https://www.drupal.org/project/bee/issues/3610134) lands (task 0043) |
| `drupal/bee` | fix `bee_form_alter()` route-name resolution | — |
| `drupal/bat` | vendor local FullCalendar library | — (task 0009) |
| `drupal/bat_api` | fix undefined `eventType` in events REST resource | — |
| `drupal/canvas` | component metadata closure type fix | — |
| `drupal/drupal_cms_helper` | scope register-form alter to admin-create | [Drupal CMS #3591417](https://git.drupalcode.org/project/drupal_cms/-/work_items/3591417) lands (tasks 0026/0035) |
| `drupal/byte_theme` | main | — |

## Shelved patches (present, deliberately NOT registered)

### `mercury-relax-uri-reference-format.patch`

**Status: shelved.** Regenerated against **mercury 1.0.5** on
2026-07-12 and verified to apply cleanly (10 files, 10 removals, no
fuzz, no rejects) — but **deliberately not registered in
`composer.json`**. Activate it only if the problem below actually
manifests.

**What it does.** Removes the `format: uri-reference` constraint from
10 link props in mercury's SDC component schemas: `url` (badge,
button, card, card-icon, card-logo, image), `cite_url` (blockquote,
card-testimonial), `href` (heading), `button_url` (card-pricing).

**Why it exists.** Its predecessor
(`mercury-component-schema-customizations.patch`, now retired) was
registered from 2026-07-05 and described as relaxing an "overly strict
`uri-reference` format on url/href/cite_url/button_url props that
breaks the site". That patch also carried custom prop schema
(`overlay_opacity` on cta, various enums) — **all of which mercury
1.0.5 has since adopted upstream**, so only the `uri-reference`
removals remain meaningful. Hence the narrower, renamed patch.

**Why it was shelved rather than fixed in place.** The old patch had
**silently failed to apply since mercury reached 1.0.5 (June 2026)** —
composer reported `No available patcher was able to apply patch`, and
11 of its 14 file-hunks were unapplied — yet the site has run fine
throughout, and every page still returns 200 with mercury pristine. A
patch nobody needs shouldn't be carried: registering it means every
`patches-repatch` deletes and reinstalls the theme, and a patch that
guards against a problem you can't reproduce is maintenance burden.

**The risk, and what would make us activate it.** Canvas **1.8.0**
(installed 2026-07-12) newly derives and stores `string_shape`
metadata from component schemas, so with the patch unapplied the
strict constraint is now recorded in config as
`derived_schema_metadata.string_shape.format: uri-reference` on those
mercury props (visible in `config/shh/sync/canvas.component.sdc.mercury.*`).
That is the exact constraint the original patch was written to remove.
It is **not currently causing any failure** — shh renders with the
`hestehoj` theme's own components, not mercury's — but mercury is the
source theme behind `hestehoj`, `quick_silver` and `zwarte_piet`, and
its components remain available in the Canvas component library.

**Symptoms that mean "activate this patch":** a Canvas component with
a link prop refuses to save or validate — e.g. a mercury `badge`,
`button`, `card`, `heading` or `card-pricing` rejecting a URL value
(anchor-only `#section`, an empty string, a token, or any value the
`uri-reference` format rejects), or a validation error naming
`string_shape`/`uri-reference` on a `canvas.component.sdc.mercury.*`
component.

**To activate:**

```jsonc
// composer.json → extra.patches
"drupal/mercury": {
    "Relax the overly strict uri-reference format on mercury's SDC link props (url/cite_url/href/button_url), which rejects valid values in Canvas; regenerated against 1.0.5, see patches/README.md": "patches/mercury-relax-uri-reference-format.patch"
}
```

```bash
ddev composer patches-relock && ddev composer patches-repatch
grep -rc "format: uri-reference" web/themes/contrib/mercury/components/  # expect 0 hits
make shh-export   # Canvas re-derives string_shape; commit the config diff
```

**Re-check on every mercury upgrade** whether the patch still applies
and whether upstream has fixed the format (as it already did for the
custom-prop half).
