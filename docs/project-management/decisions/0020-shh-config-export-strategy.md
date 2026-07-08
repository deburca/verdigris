---
tags:
  - cms2/decision
status: accepted
created: 2026-07-08
updated:
decided: 2026-07-08
site: shh
deciders:
  - Paddy de Burca
---

# 0020: Config export as source of truth for shh (with config_split for dev-only modules)

## Status

accepted — resolves [[0033-durable-config-strategy-shh]]'s open strategy
question; amends the previously implicit "install hooks are the only
config mechanism" house practice for shh.

## Context

Every shh feature module applies its configuration imperatively in a
fire-once `hook_install()` (the deliberate house pattern since 0010),
and shh's `config_sync_directory` pointed at `sites/shh/files/sync` —
which was **empty and gitignored**. Nothing durable re-asserted any of
this state. The failure mode was reproduced live before deciding
anything: `drush cex`, flip one exported value
(`user.settings: register` → `admin_only`), `drush cim` — and the
client-decided registration policy from [[0026-rider-account-access-policy]]
silently reverted to the platform default. No error, no trace.

Two strategies were on the table (0033's own framing): (a) commit to
the imperative pattern and block config import on shh outright, or
(b) adopt config export so the sync store is the source of truth. A
guard module for (a) (`shh_config_guard`, a
`ConfigEvents::IMPORT_VALIDATE` subscriber rejecting every import with
a settings.php escape hatch) was actually built and verified as far as
installation before the decision review. The deciding input: **shh is
being built to go to production for external users**, and the standard
Drupal deployment pipeline (`git push` + `drush deploy` =
`updb`+`cim`+`cr`) moves config through a git-tracked sync store. The
imperative-only pattern has no deployment story beyond "re-run 18
modules' install hooks against prod and hope."

## Decision

Config export becomes the source of truth for shh:

- **`config_sync_directory` moves to `config/shh/sync`** — project
  root, outside the webroot, **git-tracked** (the old location under
  `sites/shh/files/` is gitignored, which is exactly what made the
  trap silent). The full active config (~850 items) is exported there
  as the baseline.
- **`config_split` (contrib, ^2.0) handles dev-only modules.** A
  single split, `local` (folder storage at `config/shh/local`, also
  tracked), carries `field_ui` and `views_ui`. Its **stored** status is
  `false`; DDEV activates it via a `$config` override in `settings.php`
  keyed on `IS_DDEV_PROJECT`. So the same tracked store yields: local
  import keeps the UI modules, production import uninstalls them.
- **Per-environment scalar differences use `$config` overrides in
  settings.php**, not splits — overrides never enter the export, need
  no module, and cover error verbosity/aggregation/mail-type values
  whenever prod settings are written.
- **`config_ignore` is deliberately NOT adopted** (neither as
  "exclude install-hook values from sync" — unnecessary once the sync
  store carries the correct values, and a per-module ignore list would
  be a new silent-failure trap of exactly the kind 0033 targets — nor
  preemptively for prod-editable config; add it only when a real
  "staff edit this in prod UI" case appears).
- **Scope: shh only.** vdg and kbg keep their current arrangements;
  they should adopt the same pattern (own `config/<site>/sync`) when
  their build phases warrant it.

**The workflow rule this creates** (the actual point of the decision):
after any config-affecting change on shh — enabling a module, an
install hook writing config, a field/display edit — run
`ddev drush -l https://hestehoj.ddev.site cex -y` and commit the
config diff **in the same commit as the code**. A stale export is the
new failure mode: `drush cim` would revert live values or uninstall a
not-yet-exported module, exactly the 0033 trap but now looking
authoritative.

**settings.php requirements** (spelled out here because settings files
are gitignored — this block cannot be recovered from git):

```php
$settings['config_sync_directory'] = '../config/shh/sync';
if (getenv('IS_DDEV_PROJECT') == 'true') {
  $config['config_split.config_split.local']['status'] = TRUE;
}
```

## Consequences

### Positive

- shh config is versioned: history, review, and revert for every
  config change — a tampered or drifted sync store shows up in
  `git diff` instead of importing silently
- A real deployment path exists: prod bootstraps from the tracked
  store via `drush deploy`, no install-hook replay required
- `drush cim` now *re-asserts* install-hook-managed values (the export
  carries `register: visitors_admin_approval`) instead of reverting
  them — the 0033 revert scenario is closed by construction
- Field UI / Views UI stay available locally but can never leak into a
  production import

### Negative

- Ongoing discipline: `cex` + commit must ride with every
  config-affecting change; a forgotten export means the next `cim`
  reverts values or uninstalls modules (mitigated by the tracked
  store making any divergence *visible*, unlike before)
- ~850 tracked YAML files add diff noise to feature commits
- The existing install hooks and the sync store are now two
  descriptions of the same state; they converge (hooks run, then
  export captures the result) but future authors must know exports
  are the authoritative one ([[0035-shh-install-hook-cleanup]] is the
  natural place to rationalize the hooks' role to first-install-only
  bootstrapping)

### Neutral

- The install-hook pattern itself is unchanged for *building* features
  (create things imperatively, verify, then export) — what changed is
  that the result is captured durably instead of living only in the
  database
- `sites/default/files/sync` still holds a stale 565-item export from
  platform installation (including `register: admin_only`); it is not
  referenced by any site's effective `config_sync_directory` and was
  left untouched

## Alternatives Considered

### Alternative 1: Imperative pattern + import guard (0033's option a)

Built and working (`shh_config_guard`: an import-validate subscriber
rejecting every `ConfigImporter` run on shh — drush full/partial and
both admin UI forms — with a `$settings['shh_config_guard_allow_import']`
escape hatch). Discarded once the production goal was made explicit:
it protects local state but leaves the site undeployable, and the
"deliberate future adoption" its escape hatch anticipated is simply
this decision, taken now. Module deleted (never committed).

### Alternative 2: Export + config_ignore exclusions for install-hook values

Rejected: once the sync store carries the correct values there is
nothing to exclude, and an ignore list that every future shh module
must remember to extend recreates the silent-failure class 0033 set
out to kill.

### Alternative 3: Export now, config_split later

Viable, but rejected because dev-only modules already exist on shh
(Field UI, Views UI were enabled all along) — deferring the split
would bake them into the first production import, and retrofitting a
split later means re-touching the baseline anyway.

## Implementation Notes

- Implemented under [[0033-durable-config-strategy-shh]] (see its
  Resolution for the verification transcript): baseline export of 852
  base items + 1 split item (`field_ui.settings.yml`);
  `core.extension.yml` in the base store contains `config_split` but
  neither `field_ui` nor `views_ui`; split entity exported with
  `status: false`
- Verified: `cex` → `cim` roundtrip is a no-op with the split active;
  the tampered-store scenario now behaves predictably in both
  directions (store value wins, restore-and-reimport recovers);
  effective split status is `true` under DDEV / stored `false`;
  Field UI, Views UI remain enabled locally; all discovery pages
  serve 200 anonymously afterwards
- `drupal/config_split` added via `ddev composer require` (patches
  verified intact afterwards, per the 0009 lesson)
- The split's `folder` is relative to the Drupal root, like the sync
  setting: `../config/shh/local`
- When prod settings are eventually written they must include the
  sync path but **omit/false** the split activation, and add `$config`
  overrides for the usual scalar hardening (logging, aggregation,
  mail)
- The workflow is codified in the project-root `Makefile`:
  `make shh-export` / `make shh-commit` on dev (ddev), and
  `make shh [SHH_URI=…]` on each target environment (testing first,
  then production) — `drush deploy` = updb → cim → cr → deploy:hook.
  Note `drush/drush` currently sits in composer's `require-dev`, so
  target hosts must run plain `composer install` (not `--no-dev`), or
  drush should move to `require` as part of real production prep

## References

- Task: [[0033-durable-config-strategy-shh]]
- Related decisions: [[0012-ddev-local-development]],
  [[0006-composer-patch-management]]
- Related tasks: [[0026-rider-account-access-policy]] (the reverted
  value that surfaced this), [[0035-shh-install-hook-cleanup]]
- Project: [[shh-stables-platform]]
