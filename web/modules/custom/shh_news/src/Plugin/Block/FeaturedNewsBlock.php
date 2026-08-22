<?php

namespace Drupal\shh_news\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\shh_news\NewsCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * News teaser for the homepage (task 0066).
 *
 * Same pattern as the other featured-content blocks (0051 sections
 * 3-5): live data, not static copy, so the homepage can never show a
 * post that's been unpublished. Renders nothing when there's no news
 * yet, so the homepage loses the section rather than showing an empty
 * shelf — same rule as the featured-horses block.
 */
#[Block(
  id: 'shh_featured_news',
  admin_label: new TranslatableMarkup('SHH news'),
)]
class FeaturedNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const LIMIT = 3;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected NewsCardBuilder $cardBuilder,
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
      $container->get('shh_news.card_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $posts = $this->cardBuilder->posts(self::LIMIT);
    if (!$posts) {
      return [];
    }

    $cards = [];
    foreach ($posts as $node) {
      $cards[] = $this->cardBuilder->buildCard($node);
    }

    $columns = match (count($cards)) {
      1 => ['grid-cols-1', 'max-w-sm', 'mx-auto'],
      2 => ['grid-cols-1', 'sm:grid-cols-2', 'max-w-3xl', 'mx-auto'],
      default => ['grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3'],
    };

    return [
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => array_merge(['grid', 'gap-6'], $columns)],
        'cards' => $cards,
      ],
      'more' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['mt-8', 'flex', 'justify-center']],
        'link' => [
          '#type' => 'component',
          '#component' => 'hestehoj:button',
          '#props' => [
            'label' => $this->t('See all news'),
            'href' => Url::fromRoute('shh_news.catalog')->toString(),
            'variant' => 'secondary',
            'size' => 'medium',
            'icon' => 'arrow-right',
            'icon_first' => FALSE,
            'disabled' => FALSE,
            'mobile_width' => FALSE,
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:news'],
      ],
    ];
  }

}
