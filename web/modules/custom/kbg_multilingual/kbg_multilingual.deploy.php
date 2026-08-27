<?php

/**
 * @file
 * Deploy hooks for kbg_multilingual, run by `drush deploy:hook` after cim.
 *
 * The Makefile's `kbg-deploy` target runs `drush deploy:hook -y` as its last
 * step, so anything here fires on `make kbg-pull`.
 *
 * Run-once semantics: like hook_post_update_NAME(), each function here runs a
 * single time per target and is auto-baselined (marked done, not run) when the
 * module is first installed. On a fresh install the .po is imported by the
 * configurable_language_insert hook (when `da` is created during cim) or by
 * hook_install() (when `da` already exists) — not from here.
 *
 * To ship a refreshed translations/da.po (e.g. after a new hivelog release, or
 * new community strings) to already-deployed environments, re-export the file
 * AND add the next numbered function below (…_danish_translations_2, _3, …).
 * Editing the .po alone will not re-import it anywhere the current hook has
 * already run.
 */

/**
 * Task 0069 phase 2: import the committed Danish interface translations.
 */
function kbg_multilingual_deploy_import_danish_translations(): string {
  return kbg_multilingual_import_interface_translations();
}
