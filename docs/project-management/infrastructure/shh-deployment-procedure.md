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

### Go-live data checks (run once, after the DB copy)

The DB copied to production carries dev's data, so check it for the
traces of bugs that were live before they were fixed:

```bash
# 1. Horse items with an overridden unit price (task 0046: until
#    2026-07-12 an anonymous forged POST could set any price on a
#    horse — a 45.000 DKK horse for 1 DKK). Any hit is a suspect
#    order, not a real sale.
#    NB: restrict to type = 'horse'. Do NOT check overridden_unit_price
#    across all types — bee bookings, deposits and credit packs set
#    overridden prices legitimately from their own forms (they compute
#    prices in code), so an unrestricted query is all false positives.
ddev drush -l https://hestehoj.ddev.site sql:query \
  "SELECT order_id, order_item_id, title, unit_price__number \
   FROM commerce_order_item WHERE type = 'horse' AND overridden_unit_price = 1"
# Verified empty on dev 2026-07-12 (no real horse order was ever
# exploited — only this task's own test carts, since deleted).

# 2. Facility price frequency (task 0043: bee's form alter silently
#    reset 'minute' → 'hour' on every staff node save, which makes
#    sub-hour bookings compute 0,00 DKK). All three must read 'minute'.
ddev drush -l https://hestehoj.ddev.site sql:query \
  "SELECT entity_id, field_price_frequency_value FROM node__field_price_frequency"

# 3. Zero-priced facility bookings (the symptom of #2, if it ever ran):
ddev drush -l https://hestehoj.ddev.site sql:query \
  "SELECT order_id, title, total_price__number FROM commerce_order_item \
   WHERE type = 'bee' AND total_price__number = 0"
```

Also delete any leftover test content before go-live: test accounts
(`test_rider`, `shh_test_rider`, `soren_holm`, `freya_jensen`), test
orders, and the GD-generated placeholder product/facility photos
(tasks 0039/0040) once the client's real photos are in.

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

## Standing operational chores (staff, not deployment)

Things the platform deliberately does **not** automate, so a human has
to. Recorded here because "nothing enforces it" is exactly why they get
forgotten.

- **Unpublish a sold-out feed variation.** The platform tracks **no
  stock** (client decision, 2026-07-12, [[0038-straw-and-wrap-sale-items]]):
  straw and wrap are also sold word-of-mouth and via Instagram, so the
  web shop is never the full picture. Publish/unpublish is the only
  availability lever — per product, or per **year variation**, so a
  sold-out 2025 can be taken down while 2026 stays on sale. **The site
  will otherwise happily sell bales that no longer exist** and take
  payment for them.
- **Feed prices are staff-managed content.** No schedules, no seasonal
  automation — edit the variation when the business decides
  (quality/quantity dependent; see 0038).
- **Never list a bought-in horse** (not bred in-house) until the
  accountant confirms margin-scheme (brugtmoms) treatment —
  [[0005-tax-classification-horses-vs-bookings]]; Commerce has no
  margin-invoicing support, so it would need custom development.

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
