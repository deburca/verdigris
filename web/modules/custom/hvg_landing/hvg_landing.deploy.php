<?php

/**
 * @file
 * Deploy hooks for hvg_landing, run by `drush deploy:hook` after cim.
 *
 * The Makefile's `hvg-deploy` target runs `drush deploy:hook -y` as its last
 * step, so anything here fires on `make hvg-pull`.
 *
 * Idempotent: the hook does nothing when a page already exists at /home.
 * The ConfigEvents::IMPORT subscriber normally creates the page during the
 * first config import (for example `site:install --existing-config`, where
 * deploy hooks never run); this hook covers any environment that missed
 * that, and later reseeds. Editing the page builder alone will not change an
 * already-seeded page: after the first seed the page is edited in Canvas,
 * which is the source of truth. To reseed deliberately, delete the page and
 * add the next numbered function below.
 */

use Drupal\hvg_landing\LandingPage;

/**
 * Create the Canvas landing page at /home.
 */
function hvg_landing_deploy_create_home_page(): string {
  return LandingPage::create();
}
