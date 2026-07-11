---
tags: [cms2/infrastructure]
site: shh
created: 2026-07-11
updated: 2026-07-11
---
# SHH — Production Deployment Procedure

Covers both the **initial go-live** (copying the dev database) and
**all subsequent deployments** (code-only, no database copy).

## How it works

The site uses a git-tracked config store (`config/shh/sync`) as the
source of truth — see [[0020-shh-config-export-strategy]]. The standard
deployment target is `make shh`, which runs:

```
vendor/bin/drush --uri=hestehoj.dk deploy -y
```

`drush deploy` executes in order:
1. `updb` — run any pending database schema updates
2. `config:import` — import `config/shh/sync` into the live database
3. `cache:rebuild` — rebuild all caches
4. `deploy:hook` — run any `hook_deploy_NAME()` implementations

The `config_split` `local` split (carrying `field_ui` + `views_ui`) has
`status: false` in the tracked store. Without the DDEV `$config`
activation override, those dev-only modules are uninstalled by
`config:import` on any non-DDEV host — by construction.

## One-time server pre-condition

The production `web/sites/shh/settings.php` must contain the following
block (settings files are gitignored — this cannot come from git and
must be added manually on each new host):

```php
$settings['config_sync_directory'] = '../config/shh/sync';
if (getenv('IS_DDEV_PROJECT') == 'true') {
  $config['config_split.config_split.local']['status'] = TRUE;
}
```

Without the first line, `config:import` reads from the wrong (empty)
sync directory and does nothing. Without the second line, DDEV ignores
the local split — which is fine in production but must be present in
DDEV settings.local.php.

Additionally: `drush/drush` is currently in Composer's `require-dev`.
Production hosts must therefore run plain `composer install`, **not**
`composer install --no-dev`. Move drush to `require` before a production
environment that restricts dev packages.

---

## Initial go-live (DB copy from dev)

The dev database already carries the correct module state, config, and
content (modules installed/uninstalled, privacy policy published, etc.).
Copying it to production avoids any per-entity cleanup steps.

```bash
# 1. Pull the latest code
git pull origin main

# 2. Install all Composer dependencies (including drush — see above)
composer install

# 3. Copy the dev database to production (method depends on host)
#    e.g. export from DDEV, import on production:
#    ddev export-db --file=shh-YYYY-MM-DD.sql.gz   (run locally)
#    [upload and import on production host]

# 4. Run the deploy target (updb + config:import + cr + deploy:hook)
#    Will be near-instant: DB already matches the tracked config store.
make shh
```

After the DB import, `config:import` is effectively a no-op (the
database and the sync store are already in agreement), but running
`make shh` is still the correct step — it ensures any stale caches are
rebuilt and any deploy hooks fire.

---

## Subsequent deployments (code-only, no DB copy)

For every deployment after go-live:

```bash
# 1. Pull the code (and any new config/shh/sync changes)
git pull origin main

# 2. Install any new Composer dependencies
composer install

# 3. Deploy
make shh
```

`drush deploy` will pick up and apply any config changes committed to
`config/shh/sync` since the last deployment. Each commit on this project
follows the rule: a config-affecting change always commits the config
diff in the same commit as the code — so `git pull` + `make shh`
is always a complete, self-contained deployment step.

To target a non-production host (e.g. a staging environment):

```bash
make shh SHH_URI=test.hestehoj.dk
```

---

## Dev workflow (local, DDEV)

After any config-affecting change on the dev site, export and commit
before pushing:

```bash
# Export active config (config_split-aware: base → config/shh/sync,
# local split → config/shh/local)
make shh-export

# Review the diff
git diff config/shh

# Stage code + config together and open the commit editor
make shh-commit
```

**Never push a code change without a matching config export.** A stale
export means the next `config:import` on any environment reverts values
or uninstalls modules silently.

---

## Rollback

Drupal has no built-in rollback. Standard procedure:

1. `git revert <merge-commit>` — creates a new commit undoing the change
2. `git push`
3. On production: `git pull origin main && make shh`

For data changes (e.g. a published node or entity edit), restore from a
database snapshot taken before the deployment.

---

## References

- [[0020-shh-config-export-strategy]] — config export strategy and
  settings.php requirements (canonical)
- [[0033-durable-config-strategy-shh]] — task that introduced the
  decision
- `Makefile` — `shh`, `shh-export`, `shh-commit` targets
- [[shh-stables-platform]] — project overview
