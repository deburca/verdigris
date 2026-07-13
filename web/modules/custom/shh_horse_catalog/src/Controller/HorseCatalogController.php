<?php

namespace Drupal\shh_horse_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shh_horse_catalog\HorseCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "Horses for sale" catalog page.
 *
 * See docs/project-management/tasks/0019-horse-catalog-page.md. There was
 * previously no way to browse horses at all without a direct
 * /product/{id} link (the admin product list at /admin/commerce/products
 * is staff-only) — every "horse sale" verification in this project's
 * history landed directly on an individual product page.
 *
 * The query and the card itself live in HorseCardBuilder (task 0051
 * section 3), shared with the homepage's featured-horses block so the
 * two can never drift apart on what "for sale" means.
 */
class HorseCatalogController extends ControllerBase {

  public function __construct(
    protected HorseCardBuilder $cardBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_horse_catalog.card_builder'),
    );
  }

  /**
   * Builds the catalog page: one card per horse currently for sale.
   */
  public function catalog(): array {
    $horses = $this->cardBuilder->availableHorses();

    // Both list tags: field_sale_state lives on the variation, and a
    // variation save does not invalidate commerce_product_list — so the
    // catalog would otherwise keep advertising a horse that just sold.
    $build = [
      '#cache' => [
        'tags' => ['commerce_product_list', 'commerce_product_variation_list'],
      ],
    ];

    if (!$horses) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There are no horses for sale right now — please check back soon.') . '</p>',
      ];
      return $build;
    }

    $cards = [];
    foreach ($horses as $variation) {
      $cards[] = $this->cardBuilder->buildCard($variation);
    }

    $build['grid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['grid', 'grid-cols-1', 'gap-6', 'sm:grid-cols-2', 'lg:grid-cols-3'],
      ],
      'cards' => $cards,
    ];

    return $build;
  }

}
