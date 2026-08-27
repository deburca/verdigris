<?php

/**
 * @file
 * Deploy hooks for shh_multilingual, run by `drush deploy:hook` after cim.
 *
 * The Makefile's `shh-deploy` target runs `drush deploy:hook -y` as its last
 * step, so anything here fires on `make shh-pull`.
 *
 * Run-once semantics: like hook_post_update_NAME(), each function here runs a
 * single time per target and is auto-baselined (marked done, not run) when the
 * module is first installed. On a fresh install the .po is imported by the
 * configurable_language_insert hook (when `da` is created during cim) or by
 * hook_install() (when `da` already exists) — not from here.
 *
 * To ship a refreshed translations/da.po (e.g. after new community strings land) to already-deployed environments, re-export the file
 * AND add the next numbered function below (…_danish_translations_2, _3, …).
 * Editing the .po alone will not re-import it anywhere the current hook has
 * already run.
 */

/**
 * Task 0069 phase 2: import the committed Danish interface translations.
 */
function shh_multilingual_deploy_import_danish_translations(): string {
  return shh_multilingual_import_interface_translations();
}

/**
 * Task 0069: seed Danish translations of the menu-link titles.
 */
function shh_multilingual_deploy_seed_da_menu_links(): string {
  return shh_multilingual_seed_da_menu_links();
}

