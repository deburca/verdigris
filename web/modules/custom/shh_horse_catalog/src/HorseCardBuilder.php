<?php

namespace Drupal\shh_horse_catalog;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Builds horse cards and finds the horses currently for sale.
 *
 * Factored out of HorseCatalogController (task 0051 section 3) so the
 * homepage's featured-horses block renders *exactly* the same card as
 * the /horses catalog, from the same query — one definition of "a horse
 * that is for sale", not two that can drift apart.
 */
class HorseCardBuilder {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CurrencyFormatterInterface $currencyFormatter,
  ) {}

  /**
   * The horse variations currently for sale, newest first.
   *
   * "For sale" is `field_sale_state: available` on a published variation
   * — the same rule 0024 enforces at add-to-cart, so the catalog can
   * never advertise a horse the checkout would refuse.
   *
   * @param int|null $limit
   *   Maximum to return; NULL for all.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   Deduplicated by product (one card per horse).
   */
  public function availableHorses(?int $limit = NULL): array {
    $storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $query = $storage->getQuery()
      ->condition('type', 'horse')
      ->condition('field_sale_state', 'available')
      ->condition('status', TRUE)
      ->sort('variation_id', 'DESC')
      ->accessCheck(TRUE);
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }

    $horses = [];
    $seen_products = [];
    foreach ($storage->loadMultiple($ids) as $variation) {
      $product = $variation->getProduct();
      if (!$product || isset($seen_products[$product->id()])) {
        continue;
      }
      $seen_products[$product->id()] = TRUE;
      $horses[] = $variation;
      if ($limit !== NULL && count($horses) >= $limit) {
        break;
      }
    }
    return $horses;
  }

  /**
   * A single hestehoj:card render array for one horse variation.
   */
  public function buildCard(ProductVariationInterface $variation): array {
    $product = $variation->getProduct();

    $summary_parts = [];
    if ($variation->hasField('field_breed') && !$variation->get('field_breed')->isEmpty()) {
      $summary_parts[] = $variation->get('field_breed')->value;
    }
    if ($variation->hasField('field_gaits')) {
      $gait_labels = shh_common_list_string_labels($variation->get('field_gaits'));
      if ($gait_labels) {
        $summary_parts[] = implode(', ', $gait_labels);
      }
    }
    $price = $variation->getPrice();
    if ($price) {
      $summary_parts[] = $this->currencyFormatter->format(
        $price->getNumber(),
        $price->getCurrencyCode(),
      );
    }

    $props = [
      'heading_text' => $product->label(),
      'orientation' => 'vertical',
      'style' => 'framed',
      'url' => $product->toUrl()->toString(),
      'text' => implode(' · ', array_filter($summary_parts)),
    ];

    $media_props = shh_common_image_media_props($variation, 'field_media');
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
