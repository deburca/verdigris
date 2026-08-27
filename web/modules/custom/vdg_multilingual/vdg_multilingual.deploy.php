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
 * module is first installed. On a fresh install the .po is imported by the
 * configurable_language_insert hook (when `da` is created during cim) or by
 * hook_install() (when `da` already exists) — not from here.
 *
 * To ship a refreshed translations/da.po to already-deployed environments,
 * re-export the file AND add the next numbered function below
 * (…_danish_translations_3, _4, …). Editing the .po alone will not re-import
 * it anywhere the current hook has already run.
 */

/**
 * Task 0069 phase 2: import the committed Danish interface translations.
 */
function vdg_multilingual_deploy_import_danish_translations(): string {
  return vdg_multilingual_import_interface_translations();
}

/**
 * Task 0069: back-fill the .po on environments deployed before the
 * configurable_language_insert hook existed.
 *
 * On the first `make vdg-pull` the module installed and the
 * `..._danish_translations` deploy hook above was auto-baselined in the same
 * step, while hook_install() skipped (the `da` language did not exist yet) —
 * so no strings were imported. This function post-dates that deploy, so it is
 * not baselined there and runs once to fix it. On a fresh install it is
 * auto-baselined and the insert hook does the work instead; the import is
 * idempotent either way.
 */
function vdg_multilingual_deploy_import_danish_translations_2(): string {
  return vdg_multilingual_import_interface_translations();
}
