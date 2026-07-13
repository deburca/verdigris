<?php

namespace Drupal\shh_horse_catalog\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\shh_horse_catalog\HorseCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Featured horses for the homepage (task 0051, section 3).
 *
 * Code, not Canvas content, deliberately: a static section listing
 * horses by name would advertise a sold horse the moment one sells.
 * This renders the *live* set — the same query and the same card as the
 * /horses catalog, via the shared HorseCardBuilder.
 *
 * Renders nothing at all when no horse is for sale, so the homepage
 * silently loses the section rather than showing an empty shelf.
 */
#[Block(
  id: 'shh_featured_horses',
  admin_label: new TranslatableMarkup('SHH featured horses'),
)]
class FeaturedHorsesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * How many horses the homepage shows before "see all".
   */
  const LIMIT = 3;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HorseCardBuilder $cardBuilder,
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
      $container->get('shh_horse_catalog.card_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $horses = $this->cardBuilder->availableHorses(self::LIMIT);
    if (!$horses) {
      return [];
    }

    $cards = [];
    foreach ($horses as $variation) {
      $cards[] = $this->cardBuilder->buildCard($variation);
    }

    // Fit the grid to how many horses are actually for sale, and centre
    // it. A stud often has one or two — in a fixed three-column grid a
    // lone horse sits stranded at the left with two empty cells beside
    // it, which reads as "something failed to load" rather than "we have
    // one lovely mare".
    $columns = match (count($cards)) {
      1 => ['grid-cols-1', 'max-w-sm'],
      2 => ['grid-cols-1', 'sm:grid-cols-2', 'max-w-3xl'],
      default => ['grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3'],
    };

    return [
      'grid' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => array_merge(['grid', 'gap-6', 'mx-auto'], $columns),
        ],
        'cards' => $cards,
      ],
      'more' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['mt-8', 'flex', 'justify-center']],
        'link' => [
          '#type' => 'component',
          '#component' => 'hestehoj:button',
          '#props' => [
            'label' => $this->t('See all horses for sale'),
            'href' => Url::fromRoute('shh_horse_catalog.catalog')->toString(),
            'variant' => 'secondary',
            'size' => 'medium',
            'icon' => 'arrow-right',
            'icon_first' => FALSE,
            'disabled' => FALSE,
            'mobile_width' => FALSE,
          ],
        ],
      ],
      // BOTH list tags, deliberately. `field_sale_state` — the field that
      // decides whether a horse appears here at all — lives on the
      // VARIATION, and saving a variation does not invalidate
      // `commerce_product_list`. Tagging only the product list left a
      // sold horse on the homepage until an unrelated cache clear
      // (caught while verifying this section: relisting a sold horse
      // changed nothing on the page).
      '#cache' => [
        'tags' => ['commerce_product_list', 'commerce_product_variation_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
