# =====================================================================
# Multisite push/pull workflow — vdg (verdigris.nu), kbg
# (kragebaekgaard.dk), shh (stutteri-hestehoj.dk / hestehoj.dk).
#
# <site>-push — run LOCALLY (dev, via ddev), in this order:
#   <site>-refresh (update-database -> clear-cache) ->
#   <site>-commit (= export-config -> git add config/<site> ->
#   git commit; editor opens; house style: feat(<site>): subject +
#   "Site: <domain> (<site>)" body line) -> git push
# Aborting the commit editor stops the chain, so nothing is pushed.
# Review `git diff config/<site>` before the commit — every changed
# line should be explained by the change you just made (decision 0020).
#
# <site>-pull — run ON the target environment (testing or production),
# the same steps in reverse: fetch what push published, then apply it:
#   git pull -> git submodule update --init --recursive ->
#   composer install --no-dev -> <site>-deploy
#   (the submodule step is required: quick_silver, zwarte_piet, and
#   hestehoj are git submodules — see .gitmodules — and plain `git
#   pull` never populates or updates submodule content on its own;
#   update-database -> import-config -> clear-cache -> deploy hooks;
#   the `drush deploy` order: updb before config:import so imports
#   never run against a stale schema). config:import is skipped while
#   the site's sync store (config/<site>/sync) has no *.yml yet, so a
#   pull cannot wipe a site whose config was never exported.
#
# all-push / all-pull — the same for all three sites at once:
#   all-push refreshes and exports every site, then makes ONE commit
#   covering config/{vdg,kbg,shh} and pushes; all-pull does one
#   git pull + composer install, then deploys every site in turn.
#
# Production URIs are the defaults; on a testing instance override:
#   make shh-pull SHH_URI=test.hestehoj.dk
#   make all-pull SHH_URI=test.hestehoj.dk VDG_URI=test.verdigris.nu ...
#
# Each target host needs (settings files are gitignored and cannot
# come from git):
#   $settings['config_sync_directory'] = '../config/<site>/sync';
#   in web/sites/<site>/settings.php — shh additionally carries the
#   config_split block from
#   docs/project-management/decisions/0020-shh-config-export-strategy.md
#   (its dev-only "local" split stays inactive on import, so Field UI /
#   Views UI are uninstalled on testing/production by construction).
# =====================================================================

VDG_URI ?= verdigris.nu
KBG_URI ?= kragebaekgaard.dk
SHH_URI ?= hestehoj.dk

VDG_DDEV = https://verdigris.ddev.site
KBG_DDEV = https://kragebaekgaard.ddev.site
SHH_DDEV = https://hestehoj.ddev.site

DRUSH = vendor/bin/drush

# Target order is load-bearing (refresh before commit, pull before
# deploy) — never run this Makefile with -j.
.NOTPARALLEL:

# ------------------------------------------------------------------ vdg

vdg-refresh:
	ddev drush -l $(VDG_DDEV) updb -y
	ddev drush -l $(VDG_DDEV) cache:rebuild

vdg-export:
	ddev drush -l $(VDG_DDEV) config:export -y

vdg-commit: vdg-export
	git add config/vdg
	git commit

vdg-push: vdg-refresh vdg-commit
	git push

vdg-deploy:
	$(DRUSH) --uri=$(VDG_URI) updb -y
	@if ls config/vdg/sync/*.yml >/dev/null 2>&1; then \
		$(DRUSH) --uri=$(VDG_URI) config:import -y; \
	else \
		echo "config/vdg/sync has no exported config yet — skipping config:import"; \
	fi
	$(DRUSH) --uri=$(VDG_URI) cache:rebuild
	$(DRUSH) --uri=$(VDG_URI) deploy:hook -y

vdg-pull:
	git pull
	git submodule update --init --recursive
	composer install --no-dev
	$(MAKE) vdg-deploy

# ------------------------------------------------------------------ kbg

kbg-refresh:
	ddev drush -l $(KBG_DDEV) updb -y
	ddev drush -l $(KBG_DDEV) cache:rebuild

kbg-export:
	ddev drush -l $(KBG_DDEV) config:export -y

kbg-commit: kbg-export
	git add config/kbg
	git commit

kbg-push: kbg-refresh kbg-commit
	git push

kbg-deploy:
	$(DRUSH) --uri=$(KBG_URI) updb -y
	@if ls config/kbg/sync/*.yml >/dev/null 2>&1; then \
		$(DRUSH) --uri=$(KBG_URI) config:import -y; \
	else \
		echo "config/kbg/sync has no exported config yet — skipping config:import"; \
	fi
	$(DRUSH) --uri=$(KBG_URI) cache:rebuild
	$(DRUSH) --uri=$(KBG_URI) deploy:hook -y

kbg-pull:
	git pull
	git submodule update --init --recursive
	composer install --no-dev
	$(MAKE) kbg-deploy

# ------------------------------------------------------------------ shh

shh-refresh:
	ddev drush -l $(SHH_DDEV) updb -y
	ddev drush -l $(SHH_DDEV) cache:rebuild

shh-export:
	ddev drush -l $(SHH_DDEV) config:export -y

shh-commit: shh-export
	git add config/shh
	git commit

shh-push: shh-refresh shh-commit
	git push

shh-deploy:
	$(DRUSH) --uri=$(SHH_URI) updb -y
	@if ls config/shh/sync/*.yml >/dev/null 2>&1; then \
		$(DRUSH) --uri=$(SHH_URI) config:import -y; \
	else \
		echo "config/shh/sync has no exported config yet — skipping config:import"; \
	fi
	$(DRUSH) --uri=$(SHH_URI) cache:rebuild
	$(DRUSH) --uri=$(SHH_URI) deploy:hook -y

shh-pull:
	git pull
	git submodule update --init --recursive
	composer install --no-dev
	$(MAKE) shh-deploy

# ------------------------------------------------- all sites at once

all-push: vdg-refresh vdg-export kbg-refresh kbg-export shh-refresh shh-export
	git add config/vdg config/kbg config/shh
	git commit
	git push

all-pull:
	git pull
	git submodule update --init --recursive
	composer install --no-dev
	$(MAKE) vdg-deploy kbg-deploy shh-deploy

# Back-compat aliases for the old per-site deploy targets.
vdg: vdg-pull
kbg: kbg-pull
shh: shh-pull

.PHONY: vdg kbg shh all-push all-pull \
	vdg-refresh vdg-export vdg-commit vdg-push vdg-deploy vdg-pull \
	kbg-refresh kbg-export kbg-commit kbg-push kbg-deploy kbg-pull \
	shh-refresh shh-export shh-commit shh-push shh-deploy shh-pull
