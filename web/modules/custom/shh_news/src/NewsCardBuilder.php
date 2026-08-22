<?php

namespace Drupal\shh_news;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Finds published news posts and builds their cards.
 *
 * Same pattern as HorseCardBuilder/FacilityCardBuilder/FeedCardBuilder
 * (task 0051 sections 3-5): the query and the card live here, shared
 * between the homepage teaser block and the /news listing page, so the
 * two can never disagree about which posts exist or how they're shown.
 */
class NewsCardBuilder {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Published news posts, newest first.
   *
   * @param int|null $limit
   *   Maximum to return; NULL for all.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function posts(?int $limit = NULL): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'news')
      ->condition('status', TRUE)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);
    if ($limit !== NULL) {
      $query->range(0, $limit);
    }
    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * A single hestehoj:card render array for one news post.
   */
  public function buildCard(NodeInterface $node): array {
    $props = [
      'heading_text' => $node->label(),
      'orientation' => 'vertical',
      'style' => 'framed',
      'url' => $node->toUrl()->toString(),
      'text' => $node->hasField('field_description') ? (string) $node->get('field_description')->value : '',
    ];

    $media_props = shh_common_image_media_props($node, 'field_featured_image');
    if ($media_props) {
      $props['media'] = $media_props;
    }

    return [
      '#type' => 'component',
      '#component' => 'hestehoj:card',
      '#props' => $props,
    ];
  }

}
