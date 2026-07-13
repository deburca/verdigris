<?php

namespace Drupal\shh_feed_catalog\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\shh_feed_catalog\FeedCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Feed & bedding teaser for the homepage (task 0051, section 5).
 *
 * A secondary line of business, so this stays short: the products, the
 * per-bale price, and the two facts a buyer needs up front — collected
 * at the stable, and availability is confirmed when we call back (task
 * 0038: the platform tracks no stock and bales are also sold locally).
 */
#[Block(
  id: 'shh_featured_feed',
  admin_label: new TranslatableMarkup('SHH feed & bedding'),
)]
class FeaturedFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FeedCardBuilder $cardBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('shh_feed_catalog.card_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $products = $this->cardBuilder->feedProducts();
    if (!$products) {
      return [];
    }

    $cards = [];
    foreach ($products as $product) {
      $cards[] = $this->cardBuilder->buildCard($product);
    }

    $columns = match (count($cards)) {
      1 => ['grid-cols-1', 'max-w-sm'],
      2 => ['grid-cols-1', 'sm:grid-cols-2', 'max-w-3xl'],
      default => ['grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3'],
    };

    return [
      'intro' => [
        '#type' => 'component',
        '#component' => 'hestehoj:text',
        '#props' => [
          'text' => $this->t('Straw and wrapped haylage from our own fields, sold by the bale and collected at the stable. We confirm availability when we contact you about your order.'),
          'text_size' => 'normal',
          'text_color' => 'default',
        ],
        '#weight' => -10,
      ],
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => array_merge(['grid', 'gap-6', 'mx-auto'], $columns)],
        'cards' => $cards,
      ],
      'more' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['mt-8', 'flex', 'justify-center']],
        'link' => [
          '#type' => 'component',
          '#component' => 'hestehoj:button',
          '#props' => [
            'label' => $this->t('See feed & bedding'),
            'href' => Url::fromRoute('shh_feed_catalog.catalog')->toString(),
            'variant' => 'secondary',
            'size' => 'medium',
            'icon' => 'arrow-right',
            'icon_first' => FALSE,
            'disabled' => FALSE,
            'mobile_width' => FALSE,
          ],
        ],
      ],
      // BOTH list tags. Task 0038's only stock lever is publish/unpublish
      // — per product, or per harvest-year VARIATION — and unpublishing a
      // variation does not invalidate commerce_product_list. Without the
      // variation tag this teaser would keep advertising a sold-out year
      // (the same trap caught in section 3).
      '#cache' => [
        'tags' => ['commerce_product_list', 'commerce_product_variation_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
