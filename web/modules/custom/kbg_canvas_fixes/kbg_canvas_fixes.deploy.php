<?php

/**
 * @file
 * Deploy hooks for kbg_canvas_fixes, run by `drush deploy:hook` after cim.
 *
 * The Makefile's `kbg-deploy` target runs `drush deploy:hook -y` as its last
 * step, so anything here fires on `make kbg-pull`. Run-once semantics: like
 * hook_post_update_NAME(), each function here runs a single time per target
 * and is auto-baselined (marked done, not run) when the module is first
 * installed.
 */

/**
 * Repoint canvas_page component instances off the disabled zwarte_piet theme.
 *
 * zwarte_piet is not an enabled theme on kragebaekgaard (only mercury and
 * beeswax are). canvas.content_template.node.page.full was fixed in config
 * (see the commit that repoints it to sdc.beeswax.*), but canvas_page
 * entities are content, not config, so any component instance that was
 * authored directly against sdc.zwarte_piet.* (as opposed to inheriting from
 * the content template) survives cim untouched and keeps rendering blank
 * `data-fallback` placeholders.
 *
 * This walks every canvas_page's *current* revision, remaps known
 * zwarte_piet component IDs to their schema-equivalent beeswax counterparts,
 * and re-saves any entity it touched. It does not rewrite historical
 * revisions — those remain harmless (nothing renders them) unless someone
 * explicitly reverts to one, at which point Canvas will show its usual
 * fallback for that stale revision, which is expected.
 */
function kbg_canvas_fixes_deploy_repoint_homepage_from_zwarte_piet(): string {
  // component_id => [new component_id, new component_version, extra inputs
  // to merge in for props that don't exist on the old component].
  $map = [
    'sdc.zwarte_piet.cta' => ['sdc.beeswax.cta', '7f6c40ea90b84edc', ['overlay_opacity' => '20%']],
    'sdc.zwarte_piet.button' => ['sdc.beeswax.button', '0ca665b3987e81dd', []],
    'sdc.zwarte_piet.section' => ['sdc.beeswax.section', 'a917d5c9be8f2830', []],
    'sdc.zwarte_piet.heading' => ['sdc.beeswax.heading', '9ba4baa1476e5856', []],
    'sdc.zwarte_piet.text' => ['sdc.beeswax.text', '16c79c28a4108f9b', []],
    'sdc.zwarte_piet.navbar' => ['sdc.beeswax.navbar', NULL, []],
    'sdc.zwarte_piet.footer' => ['sdc.beeswax.footer', NULL, []],
  ];

  $storage = \Drupal::entityTypeManager()->getStorage('canvas_page');
  $ids = $storage->getQuery()->accessCheck(FALSE)->execute();
  $fixed = [];

  foreach ($storage->loadMultiple($ids) as $entity) {
    $items = $entity->get('components');
    $touched = FALSE;

    foreach ($items as $item) {
      $old_id = $item->component_id;
      if (!isset($map[$old_id])) {
        continue;
      }
      [$new_id, $new_version, $extra_inputs] = $map[$old_id];
      if ($new_version === NULL) {
        // No live equivalent identified for this one; skip rather than
        // guess, so it surfaces for manual attention instead of rendering
        // wrong.
        continue;
      }
      $item->component_id = $new_id;
      $item->component_version = $new_version;
      if ($extra_inputs) {
        $inputs = json_decode($item->inputs, TRUE) ?? [];
        $inputs += $extra_inputs;
        $item->inputs = json_encode($inputs);
      }
      $touched = TRUE;
    }

    if ($touched) {
      $entity->save();
      $fixed[] = $entity->id() . ' (' . $entity->label() . ')';
    }
  }

  return $fixed
    ? 'Repointed zwarte_piet component instances on canvas_page: ' . implode(', ', $fixed) . '.'
    : 'No canvas_page entities referenced sdc.zwarte_piet.* — nothing to do.';
}
