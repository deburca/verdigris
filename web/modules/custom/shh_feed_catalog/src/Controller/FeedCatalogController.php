<?php

namespace Drupal\shh_feed_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shh_feed_catalog\FeedCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "Feed & bedding" catalog page.
 *
 * See docs/project-management/tasks/0038-straw-and-wrap-sale-items.md.
 * The deliberate structural difference from HorseCatalogController: feed
 * products are commodity quantity goods with no field_sale_state
 * lifecycle, so this lists every published feed product rather than
 * filtering variations by sale state.
 *
 * The query and the card live in FeedCardBuilder (task 0051 section 5),
 * shared with the homepage's feed teaser so the two cannot drift apart.
 */
class FeedCatalogController extends ControllerBase {

  public function __construct(
    protected FeedCardBuilder $cardBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_feed_catalog.card_builder'),
    );
  }

  /**
   * Builds the catalog page: one card per feed product.
   */
  public function catalog(): array {
    $products = $this->cardBuilder->feedProducts();

    // Both list tags: publish/unpublish is task 0038's only stock lever,
    // and it is applied per harvest-year VARIATION as often as per
    // product — a variation save does not invalidate the product list
    // tag, so without the variation tag this page would keep listing a
    // sold-out year.
    $build = [
      '#cache' => [
        'tags' => ['commerce_product_list', 'commerce_product_variation_list'],
      ],
      '#attached' => [],
    ];
    shh_common_attach_meta_tags(
      $build['#attached'],
      (string) $this->t('Feed & bedding'),
      (string) $this->t('Straw and wrap for sale at Stutteri Hestehøj, priced per bale — pickup only.'),
    );

    if (!$products) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There are no feed or bedding products for sale right now — please check back soon.') . '</p>',
      ];
      return $build;
    }

    $cards = [];
    foreach ($products as $product) {
      $cards[] = $this->cardBuilder->buildCard($product);
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
