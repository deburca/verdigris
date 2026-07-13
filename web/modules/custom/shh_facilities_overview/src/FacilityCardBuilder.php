<?php

namespace Drupal\shh_facilities_overview;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\shh_facility_credits\FacilityPricingHelper;

/**
 * Finds the bookable facilities and builds their cards.
 *
 * Factored out of FacilitiesOverviewController (task 0051 section 4) so
 * the homepage's facilities section renders exactly the same card, from
 * the same query, as /facilities — the shape task 0051 section 3
 * established for horses.
 *
 * Live data rather than static homepage copy, deliberately: the
 * facilities have been renamed before ("Outdoor Arena 1" → "Oval Track"),
 * their photos arrived in task 0040, and their prices are computed from
 * config (task 0020's FacilityPricingHelper). Hardcoded homepage copy
 * would drift from all three.
 */
class FacilityCardBuilder {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CurrencyFormatterInterface $currencyFormatter,
    protected FacilityPricingHelper $pricingHelper,
  ) {}

  /**
   * The published bookable facilities, by title.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The published bookable facility nodes, keyed by node id.
   */
  public function facilities(): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'bookable_facility')
      ->condition('status', TRUE)
      ->sort('title', 'ASC')
      ->accessCheck(TRUE)
      ->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * A single hestehoj:card render array for one facility.
   */
  public function buildCard(NodeInterface $node): array {
    $summary_parts = [];

    if ($node->hasField('field_facility_kind') && !$node->get('field_facility_kind')->isEmpty()) {
      $allowed_values = $node->get('field_facility_kind')->getFieldDefinition()
        ->getFieldStorageDefinition()->getSetting('allowed_values');
      $value = $node->get('field_facility_kind')->value;
      $summary_parts[] = $allowed_values[$value] ?? $value;
    }

    if ($node->hasField('field_indoor') && !$node->get('field_indoor')->isEmpty()) {
      $summary_parts[] = $node->get('field_indoor')->value ? $this->t('Indoor') : $this->t('Outdoor');
    }

    if ($node->hasField('field_capacity') && !$node->get('field_capacity')->isEmpty()) {
      $summary_parts[] = $this->formatPlural(
        (int) $node->get('field_capacity')->value,
        '1 rider',
        '@count riders',
      );
    }

    $slot_price = $this->pricingHelper->getSlotPrice($node);
    if ($slot_price) {
      $slot_minutes = (int) $node->get('field_slot_duration_minutes')->value;
      $summary_parts[] = $this->t('@price / @minutes min', [
        '@price' => $this->currencyFormatter->format($slot_price->getNumber(), $slot_price->getCurrencyCode()),
        '@minutes' => $slot_minutes,
      ]);
    }

    $props = [
      'heading_text' => $node->label(),
      'orientation' => 'vertical',
      'style' => 'framed',
      'url' => $node->toUrl()->toString(),
      'text' => implode(' · ', array_filter($summary_parts)),
    ];

    // Featured image (task 0040): first image on the facility.
    $media_props = shh_common_image_media_props($node, 'field_media');
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
