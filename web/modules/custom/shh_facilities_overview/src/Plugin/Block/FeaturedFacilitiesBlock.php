<?php

namespace Drupal\shh_facilities_overview\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\shh_facilities_overview\FacilityCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The riding facilities, for the homepage (task 0051, section 4).
 *
 * Live data, like the featured-horses block: the facilities have been
 * renamed before, carry photos (task 0040), and their prices are
 * computed from config — static homepage copy would drift from all
 * three. Renders nothing if no facility is published.
 */
#[Block(
  id: 'shh_featured_facilities',
  admin_label: new TranslatableMarkup('SHH facilities'),
)]
class FeaturedFacilitiesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FacilityCardBuilder $cardBuilder,
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
      $container->get('shh_facilities_overview.card_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $facilities = $this->cardBuilder->facilities();
    if (!$facilities) {
      return [];
    }

    $cards = [];
    foreach ($facilities as $node) {
      $cards[] = $this->cardBuilder->buildCard($node);
    }

    // Fit the grid to the number of facilities (three today), the same
    // rule the featured-horses block uses.
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
          'text' => $this->t('Book by the half hour, from 08:00 to 20:00. Ride one, or book several for the same slot and pay less.'),
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
            'label' => $this->t('See facilities and prices'),
            'href' => Url::fromRoute('shh_facilities_overview.overview')->toString(),
            'variant' => 'secondary',
            'size' => 'medium',
            'icon' => 'arrow-right',
            'icon_first' => FALSE,
            'disabled' => FALSE,
            'mobile_width' => FALSE,
          ],
        ],
      ],
      // Facility nodes carry the names, photos and (via field_price)
      // the prices shown here.
      '#cache' => [
        'tags' => ['node_list:bookable_facility'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
