<?php

/**
 * @file
 * Deploy hooks for vdg_multilingual, run by `drush deploy:hook` after cim.
 *
 * The Makefile's `vdg-deploy` target runs `drush deploy:hook -y` as its last
 * step, so anything here fires on `make vdg-pull`.
 *
 * Run-once semantics: like hook_post_update_NAME(), each function here runs a
 * single time per target and is auto-baselined (marked done, not run) when the
 * module is first installed — so a fresh deploy imports the .po via
 * hook_install() only, with no double import.
 *
 * To ship a refreshed translations/da.po to already-deployed environments,
 * re-export the file AND add the next numbered function below
 * (…_danish_translations_2, _3, …). Editing the .po alone will not re-import
 * it anywhere the current hook has already run.
 */

/**
 * Task 0069 phase 2: import the committed Danish interface translations.
 */
function vdg_multilingual_deploy_import_danish_translations(): string {
  return vdg_multilingual_import_interface_translations();
}
