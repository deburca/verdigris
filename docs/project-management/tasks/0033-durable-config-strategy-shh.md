---
type: task
tags: [cms2/task]
status: done
priority: medium
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-07
updated: 2026-07-08
---
# Task: Durable config strategy for shh's install-hook-managed state

## Description
Code-review finding on [[0026-rider-account-access-policy]]'s
implementation, and a property of the whole shh module family: every
feature module (`shh_rider_registration`, `shh_site_footer`,
`shh_main_navigation`, `shh_public_availability`, …) applies its
config imperatively in a fire-once `hook_install()`. shh's
`config_sync_directory` (`sites/shh/files/sync`) is **empty**, so
nothing re-asserts this state: the moment config export/import is
adopted (or a Drupal CMS recipe reapplies `user.settings`), values
like `register: visitors_admin_approval` silently revert to their
platform defaults with no error — reinstalling each module is
currently the only re-assertion mechanism. Not urgent while the
project stays on the imperative pattern (which is the deliberate,
consistent house style), but it becomes a real trap the day anyone
runs `drush config:import` on shh. Note `sites/default/files/sync`
*is* populated and carries `register: admin_only` — a nearby example
of exactly the kind of store that would clobber live state.

## Acceptance criteria
- [x] Decide the strategy: (a) commit to imperative install hooks and
      document that `drush cim` must never run against shh (e.g. a
      guard/README, or removing the empty sync dir setting), or
      (b) adopt config export for shh so the sync store is the source
      of truth and the install hooks' config writes are captured in it
- [x] Whichever way: `user.settings: register` on shh survives the
      chosen workflow (test the actual revert scenario, don't assume)
- [x] Document the decision where the next module author will see it
      (e.g. AGENTS.md or a project-management decision entry)

## Resolution (2026-07-08)

**Strategy (b) — config export adopted**, with `config_split` for
dev-only modules. Full rationale, workflow rule, and the required
settings.php block recorded in
[[0020-shh-config-export-strategy]] (new decision entry).

The trap was **reproduced live before deciding anything**: `drush cex`
into the (then-effective) `sites/shh/files/sync`, flip the exported
`register:` to `admin_only`, `drush cim` — the 0026 registration
policy silently reverted. No error anywhere. That settled that the
risk was real, not theoretical.

The decision then went through one deliberate reversal, worth keeping
for the record: strategy (a) was implemented first — a working
`shh_config_guard` module (a `ConfigEvents::IMPORT_VALIDATE`
subscriber rejecting every `ConfigImporter` run on shh: drush full and
`--partial`, admin UI sync and single-import forms; `site.path`-guarded
no-op elsewhere; `$settings['shh_config_guard_allow_import']` escape
hatch) — on the grounds that it matched the imperative house style
with the smallest change. Review with the client corrected the
premise: **shh is meant to reach production for external users**, so
a deployable config pipeline (`drush deploy` from a tracked store)
matters more than protecting a local-only pattern. The guard module
was uninstalled and deleted (never committed); the question it was
built to defer — "adopt export deliberately, later" — was simply
answered now instead.

What was actually done:
- `config_sync_directory` → **`config/shh/sync`** (project root,
  outside webroot, **git-tracked** — the old `sites/shh/files/sync`
  location is gitignored, which is what made the revert silent).
  Old dir left empty in place; `sites/default/files/sync`'s stale
  565-item platform export noted and deliberately left untouched
  (referenced by no site's effective setting).
- `drupal/config_split` (^2.0, → 2.0.2) required and enabled on shh.
  Composer patches spot-verified intact afterwards (0009 lesson: bee
  cart-accumulation hunk and bat local-FullCalendar library both
  still applied).
- Split `local` (folder storage, `../config/shh/local`, tracked):
  carries `field_ui` + `views_ui` — the only dev-only modules found
  enabled on shh (no devel/examples here). Stored `status: false`;
  DDEV activates it via `$config` override keyed on
  `IS_DDEV_PROJECT` in settings.php. Verified effective vs stored
  status differ exactly as intended (`--include-overridden` true,
  plain false).
- Baseline export: 852 items in the base store + 1 in the split
  (`field_ui.settings.yml`); base `core.extension.yml` contains
  `config_split` but **neither** `field_ui` nor `views_ui`, so a
  production import uninstalls them by construction.
- `config_ignore` considered and deliberately not adopted; `$config`
  overrides cover per-env scalars — see the decision entry's
  Alternatives for why an ignore list would recreate this task's own
  failure class.

Verification (acceptance criterion 2, the "don't assume" one):
- `cex` → immediate `cim`: **no changes to import** (transform is
  symmetric with the split active).
- Tampered-store scenario re-run under the new regime: flip
  `register:` in `config/shh/sync/user.settings.yml` → `cim` applies
  it (the store is now *authoritative*, and — once committed — the
  tamper is a visible `git diff`, the actual protection); restore the
  file → `cim` → `register: visitors_admin_approval` confirmed live.
  Predictable in both directions, ends correct.
- Field UI / Views UI still enabled locally; `/`, `/horses`,
  `/facilities`, `/pricing` all HTTP 200 anonymous afterwards.

**The workflow rule every future task inherits**: after any
config-affecting change on shh, `ddev drush -l
https://hestehoj.ddev.site cex -y` and commit the config diff **with
the code in the same commit**. A stale export is the new failure mode
(next `cim` reverts values / uninstalls unexported modules).

Note for [[0035-shh-install-hook-cleanup]]: the install hooks'
config writes are now captured in the tracked store, so that task can
rationalize the hooks toward first-install bootstrapping only —
uninstall-hook symmetry (like 0026's) still matters, but re-assertion
is now `cim`'s job.

## Related
- [[shh-stables-platform]]
- [[0026-rider-account-access-policy]]
- [[0020-shh-config-export-strategy]]
- [[0035-shh-install-hook-cleanup]]
