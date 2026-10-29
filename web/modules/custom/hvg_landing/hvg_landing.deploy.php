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

/**
 * Give accounts without a picture a bee's headshot as the default avatar.
 *
 * The user picture field's default image is a managed file (config holds only
 * its UUID), so the picture is changed by replacing that file's contents; the
 * field, the file entity and every page that shows it keep working as they are.
 */
function hvg_landing_deploy_bee_default_avatar(): string {
  $field = \Drupal::config('field.field.user.user.user_picture');
  $uuid = $field->get('settings.default_image.uuid');
  $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uuid' => $uuid]);
  $file = reset($files);
  if (!$file) {
    return 'No default avatar file found; nothing changed.';
  }
  $source = \Drupal::service('extension.list.module')->getPath('hvg_landing') . '/images/bee-avatar.svg';
  \Drupal::service('file_system')->copy($source, $file->getFileUri(), \Drupal\Core\File\FileExists::Replace);
  $file->setSize(filesize($source));
  $file->save();
  \Drupal::service('cache_tags.invalidator')->invalidateTags($file->getCacheTags());
  return 'Default avatar replaced with the bee headshot.';
}
