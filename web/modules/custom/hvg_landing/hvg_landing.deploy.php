<?php

/**
 * @file
 * Deploy hooks for hvg_landing, run by `drush deploy:hook` after cim.
 *
 * The Makefile's `hvg-deploy` target runs `drush deploy:hook -y` as its last
 * step, so anything here fires on `make hvg-pull`.
 *
 * Run-once semantics: like hook_post_update_NAME(), each function here
 * runs a single time per target and is auto-baselined (marked done, not
 * run) when the module is first installed, so on a first deploy the page is
 * created by the ConfigEvents::IMPORT subscriber instead. Editing the page
 * builder alone will not change an already-seeded page: after the first
 * seed the page is edited in Canvas, which is the source of truth. To
 * reseed deliberately, delete the page and add the next numbered function
 * below.
 */

use Drupal\hvg_landing\LandingPage;

/**
 * Create the Canvas landing page at /home.
 */
function hvg_landing_deploy_create_home_page(): string {
  return LandingPage::create();
}
