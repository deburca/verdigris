<?php

namespace Drupal\shh_news\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shh_news\NewsCardBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "News" listing page.
 *
 * See docs/project-management/tasks/0066-news-blog-section-deferred.md.
 * Same shape as the other discovery pages (HorseCatalogController,
 * FacilitiesOverviewController, FeedCatalogController): the query and
 * card live in NewsCardBuilder, shared with the homepage teaser block
 * so the two can never drift apart.
 */
class NewsController extends ControllerBase {

  public function __construct(
    protected NewsCardBuilder $cardBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_news.card_builder'),
    );
  }

  /**
   * Builds the listing page: one card per published news post.
   */
  public function catalog(): array {
    $posts = $this->cardBuilder->posts();

    $build = [
      '#cache' => [
        'tags' => ['node_list:news'],
      ],
      '#attached' => [],
    ];
    shh_common_attach_meta_tags(
      $build['#attached'],
      (string) $this->t('News'),
      (string) $this->t('News from Stutteri Hestehøj — updates on the herd, the facilities, and the stable.'),
    );

    if (!$posts) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There is no news to show right now — please check back soon.') . '</p>',
      ];
      return $build;
    }

    $cards = [];
    foreach ($posts as $node) {
      $cards[] = $this->cardBuilder->buildCard($node);
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
