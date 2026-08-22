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
   * "For sale" is `field_sale_state: for_sale` on a published variation
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
      ->condition('field_sale_state', 'for_sale')
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
   * Every published horse — the whole herd, any sale_state (task 0057).
   *
   * "Our horses" is a roster page, not a sale catalog: it exists to show
   * the stud's breeding, not to sell — so unlike availableHorses() this
   * is *not* filtered by field_sale_state at all, deliberately including
   * ones for sale, sold, reserved, or never promoted into the sale
   * pipeline (NULL). A horse that's sold still demonstrates the herd's
   * bloodlines and quality, which is the whole point of this page.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   Deduplicated by product (one card per horse), newest first.
   */
  public function allHorses(): array {
    $storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $ids = $storage->getQuery()
      ->condition('type', 'horse')
      ->condition('status', TRUE)
      ->sort('variation_id', 'DESC')
      ->accessCheck(TRUE)
      ->execute();
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
    }
    return $horses;
  }

  /**
   * A single hestehoj:card render array for one horse variation.
   *
   * @param bool $include_price
   *   FALSE on the informational "Our horses" roster (task 0057): a
   *   horse never promoted into the sale pipeline has no meaningful
   *   price to show (the variation's price field still holds *some*
   *   value, Commerce requires one to exist, but it was never actually
   *   set for a buyer). Sale-catalog callers keep the default TRUE.
   */
  public function buildCard(ProductVariationInterface $variation, bool $include_price = TRUE): array {
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
    if ($include_price) {
      $price = $variation->getPrice();
      if ($price) {
        $summary_parts[] = $this->currencyFormatter->format(
          $price->getNumber(),
          $price->getCurrencyCode(),
        );
      }
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
