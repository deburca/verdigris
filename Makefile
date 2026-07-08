# ---------------------------------------------------------------------
# Environment/deploy targets (kbg / vdg / shh): run ON the target
# environment (testing or production) after `git pull` + `composer
# install`, using that host's drush directly.
#
# kbg and vdg have no tracked config store yet (decision 0020 is
# shh-only), so their targets stay updb + cr. shh's tracked store
# (config/shh/sync) is authoritative, so its target is `drush deploy`
# = updb -> config:import -> cache:rebuild -> deploy:hook, in that
# order. On import, the config_split "local" split stays inactive
# (stored status: false), so dev-only modules (Field UI, Views UI)
# are uninstalled on testing/production by construction.
#
# The target host's settings.php must carry the block documented in
# docs/project-management/decisions/0020-shh-config-export-strategy.md
# (settings files are gitignored and cannot come from git).
# ---------------------------------------------------------------------

kbg:
	vendor/bin/drush --uri=kragebaekgaard.dk updb
	vendor/bin/drush --uri=kragebaekgaard.dk cr

vdg:
	vendor/bin/drush --uri=verdigris.nu updb
	vendor/bin/drush --uri=verdigris.nu cr

# For a testing environment, override the URI:
#   make shh SHH_URI=test.hestehoj.dk
SHH_URI ?= hestehoj.dk
shh:
	vendor/bin/drush --uri=$(SHH_URI) deploy -y

# ---------------------------------------------------------------------
# shh config commit workflow (decision 0020, task 0033): run LOCALLY
# (dev, via ddev). A config-affecting change is only complete when the
# exported config diff is committed together with the code — a stale
# export means the next import on any environment reverts values or
# uninstalls modules.
#
# Order:
#   1. make shh-export      export active config (config_split-aware:
#                           base -> config/shh/sync, dev-only modules
#                           -> config/shh/local)
#   2. review `git diff config/shh` — every changed line should be
#      explained by the change you just made
#   3. `git add` your code changes
#   4. make shh-commit      re-exports, stages config/shh, and opens
#                           the commit editor (house style: feat(shh):
#                           subject + "Site: stutteri-hestehoj.dk (shh)"
#                           line in the body)
#   5. push, then roll forward per environment: testing first, then
#      production — on each: git pull, composer install, make shh
# ---------------------------------------------------------------------

shh-export:
	ddev drush -l https://hestehoj.ddev.site config:export -y

shh-commit: shh-export
	git add config/shh
	git commit

.PHONY: kbg vdg shh shh-export shh-commit
