<?php

namespace Drupal\shh_horse_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\shh_horse_catalog\HorseCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "Horses for sale" catalog page, and the "Our horses" roster.
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
 *
 * ourHorses() (task 0057) is the informational counterpart: the stud
 * has around thirty horses, of which only a few are old and trained
 * enough to sell — this page shows the whole herd, any sale_state, with
 * no price and no add-to-cart, so it can exist purely as trust content
 * rather than a sale surface.
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
      '#attached' => [],
    ];
    shh_common_attach_meta_tags(
      $build['#attached'],
      (string) $this->t('Icelandic horses for sale'),
      (string) $this->t('Browse Icelandic horses for sale at Stutteri Hestehøj — five-gaited and four-gaited horses, bred in Holbæk.'),
    );

    if (!$horses) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There are no horses for sale right now — please check back soon.') . '</p>',
      ];
      $build['our_horses_link'] = $this->ourHorsesLink();
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

    $build['our_horses_link'] = $this->ourHorsesLink();

    return $build;
  }

  /**
   * Builds the "Our horses" page: the whole herd, informational only.
   */
  public function ourHorses(): array {
    $horses = $this->cardBuilder->allHorses();

    $build = [
      '#cache' => [
        'tags' => ['commerce_product_list', 'commerce_product_variation_list'],
      ],
      '#attached' => [],
    ];
    shh_common_attach_meta_tags(
      $build['#attached'],
      (string) $this->t('Our horses'),
      (string) $this->t('Meet the Icelandic horses bred at Stutteri Hestehøj — the whole herd, not just the few currently for sale.'),
    );

    if (!$horses) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There are no horses to show right now — please check back soon.') . '</p>',
      ];
      return $build;
    }

    $cards = [];
    foreach ($horses as $variation) {
      $cards[] = $this->cardBuilder->buildCard($variation, include_price: FALSE);
    }

    $build['grid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['grid', 'grid-cols-1', 'gap-6', 'sm:grid-cols-2', 'lg:grid-cols-3'],
      ],
      'cards' => $cards,
    ];

    $build['for_sale_link'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mt-8', 'flex', 'justify-center']],
      'link' => [
        '#type' => 'component',
        '#component' => 'hestehoj:button',
        '#props' => [
          'label' => $this->t('See horses for sale'),
          'href' => Url::fromRoute('shh_horse_catalog.catalog')->toString(),
          'variant' => 'secondary',
          'size' => 'medium',
          'icon' => 'arrow-right',
          'icon_first' => FALSE,
          'disabled' => FALSE,
          'mobile_width' => FALSE,
        ],
      ],
    ];

    return $build;
  }

  /**
   * A "Meet the whole herd" link from the for-sale catalog to /our-horses.
   */
  protected function ourHorsesLink(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['mt-8', 'flex', 'justify-center']],
      'link' => [
        '#type' => 'component',
        '#component' => 'hestehoj:button',
        '#props' => [
          'label' => $this->t('Meet the whole herd'),
          'href' => Url::fromRoute('shh_horse_catalog.our_horses')->toString(),
          'variant' => 'secondary',
          'size' => 'medium',
          'icon' => 'arrow-right',
          'icon_first' => FALSE,
          'disabled' => FALSE,
          'mobile_width' => FALSE,
        ],
      ],
    ];
  }

}
