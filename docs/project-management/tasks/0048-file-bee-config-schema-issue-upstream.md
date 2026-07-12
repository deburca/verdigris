---
type: task
tags: [cms2/task]
status: todo
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
- [ ] Duplicate search + dev-HEAD confirmation done
- [ ] Issue text prepared and filed (by Paddy), issue number recorded
      here and in [[0030-canvas-content-template-bookable-facility]]
- [ ] Decide whether a local schema patch is worth carrying in the
      meantime (probably not — the ECA error is admin-only noise and
      Canvas templates are not in use per decision 0019 — record the
      call)

## Related
- [[shh-stables-platform]]
- [[0030-canvas-content-template-bookable-facility]]
- [[0043-bee-price-frequency-form-reset]]
