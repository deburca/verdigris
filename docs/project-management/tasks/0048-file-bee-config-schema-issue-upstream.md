---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-12
updated: 2026-07-12
---
# Task: File 0030's bee config-schema finding upstream

## Description
[[0030-canvas-content-template-bookable-facility]] found (2026-07-08)
that **bee ships no config schema for its node-type
`third_party_settings`**, which makes Drupal CMS's
`content_template_disable_preview` ECA rule error on every Canvas
template save for a BEE-enabled bundle — and, worse, the reported
validation failure doesn't stop the `preview_mode` write from
landing. 0030 called it a "candidate upstream issue against bee" but
it was never filed; tracked here so it stops living only inside a
closed task's resolution.

Follow the 0043 filing pattern: search bee's drupal.org queue for
duplicates first, confirm the schema is still missing at bee dev
HEAD, then prepare the issue text for Paddy to file (title, steps
via the 0030 reproduction, proposed `config_schema.yml` addition
covering `bee.bee` third-party settings on node types).

## Acceptance criteria
- [x] Duplicate search + dev-HEAD confirmation done
- [x] Issue text prepared and filed (by Paddy), issue number recorded
      here and in [[0030-canvas-content-template-bookable-facility]]
- [x] Decide whether a local schema patch is worth carrying in the
      meantime — **no**, reasoning below

## Resolution (2026-07-12)

**Filed by Paddy 2026-07-12 as
[bee #3610510](https://www.drupal.org/project/bee/issues/3610510)** —
"No config schema for bee's node-type third_party_settings — node type
config fails validation".

**Pre-filing checks:**
- **Duplicate search**: bee's drupal.org queue has nothing on config
  schema (searched "config schema" and related terms; the only
  near-miss hits are two long-closed 8.x issues unrelated to this).
- **Still present at dev HEAD**: queried the project's repository tree
  via the GitLab API — bee has a `config/install` directory and
  **zero `*.schema.yml` files anywhere in the project**. Not just
  missing for the third-party settings: the module ships no schema at
  all.

**Reproduction sharpened for the report.** 0030 observed the ECA
error; this task pinned down the mechanism. Because the third-party
key has no schema, typed config **falls back to the node type's own
definition**, so validating `node.type.bookable_facility` yields **12
violations** — not only `'bee' is not a supported key` but a cascade
of nonsense demands *inside* `third_party_settings.bee`: `'uuid' is a
required key`, `'name' is a required key`, `'type' is a required
key`, and so on. The report carries that output, the settings keys
bee actually writes (`bookable`, `bookable_type`, `availability`,
`payment`, `payment_default_value`, `type_id`), and a proposed
`node.type.*.third_party.bee` schema mapping.

**No local patch, deliberately.** The symptom is admin-only noise,
and decision 0019 closed the Canvas ContentTemplate track for this
platform ([[0030-canvas-content-template-bookable-facility]]), so the
interop breakage doesn't affect anything we run. Carrying a schema
patch would be pure maintenance burden across bee upgrades for zero
functional gain — unlike [[0043-bee-price-frequency-form-reset]],
where the bug silently corrupted pricing data and a patch was
mandatory. Revisit only if the Canvas track reopens or Drupal starts
hard-failing on unvalidatable config entities.

**Upstream patch watch** (unchanged by this task): the only bee patch
this platform carries is 0043's, retired when
[bee #3610134](https://www.drupal.org/project/bee/issues/3610134)
lands. This issue (#3610510) needs no patch retirement — nothing to
retire.

## Related
- [[shh-stables-platform]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0043-bee-price-frequency-form-reset]]
- [[0019-canvas-content-templates-for-structured-content]]
