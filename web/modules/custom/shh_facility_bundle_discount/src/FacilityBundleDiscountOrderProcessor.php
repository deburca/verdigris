<?php

namespace Drupal\shh_facility_bundle_discount;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Applies a flat discount for each complete same-timeframe facility bundle
 * found in an order.
 *
 * Not built on commerce_promotion's condition/offer plugin system —
 * enabling that module hit a genuine, reproducible interaction bug between
 * Drupal core's entity-definition-update machinery and Canvas/Layout
 * Builder's eager block-plugin rebuild on this environment (see
 * docs/project-management/tasks/0017-facility-bundle-discount.md for the
 * full diagnosis). This order processor achieves the identical outcome —
 * a flat discount whenever all of `product_ids` are booked for the exact
 * same start/end time in one order — without that dependency, since the
 * Promotion module's admin UI/coupons/multi-promotion-stacking machinery
 * was never actually needed for this one fixed business rule.
 *
 * "Same timeframe" is the key constraint: booking all three facilities but
 * at different times does *not* qualify — only an exact-match set of
 * bookings sharing one (start, end) pair counts as one complete bundle. A
 * single order can contain more than one complete bundle (e.g. the same
 * three facilities booked again for a different slot); each one is
 * discounted independently.
 */
class FacilityBundleDiscountOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

  const SOURCE_ID = 'shh_facility_bundle_discount';

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RounderInterface $rounder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // Clear any bundle-discount adjustments from a previous refresh first —
    // this runs on every order refresh (cart add/remove, checkout steps),
    // so it must be idempotent rather than stacking discounts.
    foreach ($order->getItems() as $order_item) {
      foreach ($order_item->getAdjustments(['promotion']) as $adjustment) {
        if ($adjustment->getSourceId() === self::SOURCE_ID) {
          $order_item->removeAdjustment($adjustment);
        }
      }
    }

    $config = $this->configFactory->get('shh_facility_bundle_discount.settings');
    // Cast to int — ProductVariation::getProductId() returns a string, and
    // strict in_array() comparison in findCompleteBundles() would silently
    // never match otherwise (confirmed the hard way).
    $product_ids = array_map('intval', $config->get('product_ids') ?? []);
    $discount_amount_array = $config->get('discount_amount');
    if (count($product_ids) < 2 || empty($discount_amount_array['number'])) {
      return;
    }
    $discount_amount = Price::fromArray($discount_amount_array);

    foreach ($this->findCompleteBundles($order, $product_ids) as $group) {
      $this->applyDiscountToGroup($group, $discount_amount);
    }
  }

  /**
   * Finds every complete same-timeframe bundle in the order.
   *
   * @return array
   *   A list of groups; each group is an array of order items, one per
   *   product ID in $product_ids, all sharing the same booking start/end.
   */
  protected function findCompleteBundles(OrderInterface $order, array $product_ids): array {
    $items_by_product = [];
    foreach ($order->getItems() as $order_item) {
      if ($order_item->bundle() !== 'bee') {
        continue;
      }
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || $purchased_entity->getEntityTypeId() !== 'commerce_product_variation') {
        continue;
      }
      $product_id = (int) $purchased_entity->getProductId();
      if (!in_array($product_id, $product_ids, TRUE)) {
        continue;
      }
      $booking = $order_item->get('field_booking')->entity ?? NULL;
      if (!$booking) {
        continue;
      }
      $start = $booking->get('booking_start_date')->value;
      $end = $booking->get('booking_end_date')->value;
      if (!$start || !$end) {
        continue;
      }
      $timeframe_key = $start . '|' . $end;
      $items_by_product[$product_id][$timeframe_key][] = $order_item;
    }

    $all_timeframes = [];
    foreach ($product_ids as $product_id) {
      foreach (array_keys($items_by_product[$product_id] ?? []) as $timeframe_key) {
        $all_timeframes[$timeframe_key] = TRUE;
      }
    }

    $groups = [];
    foreach (array_keys($all_timeframes) as $timeframe_key) {
      $group = [];
      $complete = TRUE;
      foreach ($product_ids as $product_id) {
        if (empty($items_by_product[$product_id][$timeframe_key])) {
          $complete = FALSE;
          break;
        }
        // If more than one item of the same product shares this exact
        // timeframe (shouldn't normally happen — BAT availability prevents
        // double-booking the same slot), only the first counts toward this
        // bundle.
        $group[$product_id] = $items_by_product[$product_id][$timeframe_key][0];
      }
      if ($complete) {
        $groups[] = $group;
      }
    }

    return $groups;
  }

  /**
   * Splits the discount evenly across one bundle's order items.
   *
   * Split rather than piled onto a single item, to avoid skewing that
   * item's tax base.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $group
   *   The order items making up one complete bundle.
   */
  protected function applyDiscountToGroup(array $group, Price $discount_amount): void {
    $count = count($group);
    $currency_code = $discount_amount->getCurrencyCode();
    $per_item = $this->rounder->round(new Price(
      bcdiv($discount_amount->getNumber(), (string) $count, 6),
      $currency_code
    ));

    $allocated = new Price('0', $currency_code);
    $items = array_values($group);
    foreach ($items as $index => $order_item) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $item_price = $order_item->getTotalPrice();
      if (!$item_price) {
        continue;
      }
      // Last item absorbs the rounding remainder.
      $amount = ($index === $count - 1)
        ? $discount_amount->subtract($allocated)
        : $per_item;
      if ($amount->greaterThan($item_price)) {
        $amount = $item_price;
      }
      if ($amount->isZero() || $amount->isNegative()) {
        continue;
      }
      $allocated = $allocated->add($amount);

      $order_item->addAdjustment(new Adjustment([
        'type' => 'promotion',
        'label' => $this->t('Facility bundle discount'),
        'amount' => $amount->multiply('-1'),
        'source_id' => self::SOURCE_ID,
      ]));
    }
  }

}
