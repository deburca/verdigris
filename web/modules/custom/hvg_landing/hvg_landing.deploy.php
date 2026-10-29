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

/**
 * Create the default avatar's file where the site has none.
 *
 * The field's config names the default image by UUID, but the file itself is
 * content, not config: a site built from the config alone (hivelog.eu) has the
 * reference and no file, so the previous hook found nothing to replace and no
 * avatar was drawn at all. Creates the file entity under that UUID, with the
 * bee headshot, when it is missing, and refreshes the picture when it exists.
 */
function hvg_landing_deploy_bee_default_avatar_create(): string {
  $uuid = \Drupal::config('field.field.user.user.user_picture')->get('settings.default_image.uuid');
  if (!$uuid) {
    return 'The picture field has no default image; nothing changed.';
  }
  $source = \Drupal::service('extension.list.module')->getPath('hvg_landing') . '/images/bee-avatar.svg';
  $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid);
  if (!$file) {
    $uri = 'public://avatars/default-avatar.svg';
    $file_system = \Drupal::service('file_system');
    $directory = dirname($uri);
    $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
    $file_system->copy($source, $uri, \Drupal\Core\File\FileExists::Replace);
    $file = \Drupal\file\Entity\File::create([
      'uuid' => $uuid,
      'uri' => $uri,
      'filename' => 'default-avatar.svg',
      'filemime' => 'image/svg+xml',
      'status' => 1,
    ]);
    $file->save();
    return 'Created the default avatar file.';
  }
  \Drupal::service('file_system')->copy($source, $file->getFileUri(), \Drupal\Core\File\FileExists::Replace);
  $file->setSize(filesize($source));
  $file->save();
  return 'Refreshed the default avatar file.';
}

/**
 * Write the privacy policy into the placeholder page, as a draft.
 *
 * Drupal CMS ships a "Privacy policy" page whose body says it needs editing
 * before publishing. This fills it from content/privacy-policy.html, but only
 * while it still holds that placeholder, so a page someone has already edited
 * is never overwritten, and it never publishes: the moderation state stays
 * "draft" until the site owner has read it and publishes it.
 */
function hvg_landing_deploy_privacy_policy_draft(): string {
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'page', 'title' => 'Privacy policy']);
  $node = reset($nodes);
  if (!$node) {
    return 'No "Privacy policy" page found; nothing changed.';
  }
  if (!str_contains((string) $node->get('field_content')->value, 'needs to be edited before publishing')) {
    return 'The privacy policy page has been edited already; left as it is.';
  }
  $path = \Drupal::service('extension.list.module')->getPath('hvg_landing') . '/content/privacy-policy.html';
  $node->set('field_content', ['value' => file_get_contents($path), 'format' => $node->get('field_content')->format]);
  $node->set('field_description', 'How hivelog.eu and the Vinculum app handle your personal information.');
  $node->save();
  return 'Privacy policy drafted (state: ' . $node->get('moderation_state')->value . ').';
}

/**
 * Make sure a privacy policy page and its footer link exist (draft only).
 *
 * The earlier hook only fills a placeholder page and leaves a site without one
 * untouched, and a page and a menu link are content, not config, so a site
 * built from config (hivelog.eu) may have neither. Creates the page from
 * content/privacy-policy.html when none is titled "Privacy policy", always as
 * an unpublished draft, and adds a footer-menu link to it when the footer menu
 * has none. Never changes a page or a link that exists.
 */
function hvg_landing_deploy_privacy_policy_page(): string {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes = $storage->loadByProperties(['type' => 'page', 'title' => 'Privacy policy']);
  $node = reset($nodes);
  $messages = [];
  if (!$node) {
    $path = \Drupal::service('extension.list.module')->getPath('hvg_landing') . '/content/privacy-policy.html';
    $values = [
      'type' => 'page',
      'title' => 'Privacy policy',
      'uid' => 1,
      'status' => 0,
      'field_content' => ['value' => file_get_contents($path), 'format' => 'content_format'],
      'field_description' => 'How hivelog.eu and the Vinculum app handle your personal information.',
      'path' => ['alias' => '/privacy-policy'],
    ];
    $node = $storage->create($values);
    if ($node->hasField('moderation_state')) {
      $node->set('moderation_state', 'draft');
    }
    $node->save();
    $messages[] = 'Created the privacy policy page (draft)';
  }
  $uri = 'entity:node/' . $node->id();
  $links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'footer', 'link__uri' => $uri]);
  if (!$links) {
    \Drupal::entityTypeManager()->getStorage('menu_link_content')->create([
      'title' => 'Privacy policy',
      'menu_name' => 'footer',
      'link' => ['uri' => $uri],
      'weight' => 0,
    ])->save();
    $messages[] = 'added the footer link';
  }
  return $messages ? implode('; ', $messages) . '.' : 'The privacy policy page and its footer link exist; nothing changed.';
}
