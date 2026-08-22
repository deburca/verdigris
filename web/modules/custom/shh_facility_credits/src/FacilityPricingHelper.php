<?php

namespace Drupal\shh_facility_credits;

use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Computes facility slot/pack prices from live config (no hardcoded numbers).
 *
 * Extracted from BuyCreditPackForm — a single source of truth shared with
 * the facilities overview page (0020) and pricing comparison page (0023),
 * which both need the same "per-slot price" and "credit pack price"
 * numbers and must not silently drift out of sync with each other or with
 * the actual checkout flow (0023's own acceptance criteria says this
 * explicitly).
 *
 * Pack size and discount percentage live in `shh_facility_credits.settings`
 * (not class constants) so they can be changed via config — e.g. from a
 * future settings form — without a code deploy.
 */
class FacilityPricingHelper {

  public function __construct(
    protected RounderInterface $rounder,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * The number of reservations in one credit pack.
   */
  public function getPackSize(): int {
    return (int) $this->configFactory->get('shh_facility_credits.settings')->get('pack_size');
  }

  /**
   * The credit pack discount, as a percentage off the full per-slot price.
   */
  public function getDiscountPercentage(): int {
    return (int) $this->configFactory->get('shh_facility_credits.settings')->get('discount_percentage');
  }

  /**
   * Computes the per-slot price for a facility, or NULL if not set up.
   *
   * Per-minute rate × fixed slot duration — see task 0016's facility
   * fields. NULL means the facility isn't set up for fixed-length slots.
   */
  public function getSlotPrice(NodeInterface $node): ?Price {
    if (!$node->hasField('field_slot_duration_minutes') || $node->get('field_slot_duration_minutes')->isEmpty()) {
      return NULL;
    }
    if (!$node->hasField('field_price') || $node->get('field_price')->isEmpty()) {
      return NULL;
    }
    $slot_minutes = (int) $node->get('field_slot_duration_minutes')->value;
    $price_item = $node->get('field_price')->first();
    $per_minute = $price_item->number;
    $currency_code = $price_item->currency_code;
    $slot_price_number = bcmul((string) $per_minute, (string) $slot_minutes, 6);
    // Round here — the per-minute rate is stored truncated to 6 decimals
    // (e.g. Oval Track's 1.666666, not 1.666667), so multiplying back out
    // without rounding produces 49.99998 DKK instead of exactly 50.00. The
    // actual booking flow (bee_get_unit_price()) rounds its final result
    // too; this needs to do the same for any price preview/total.
    return $this->rounder->round(new Price($slot_price_number, $currency_code));
  }

  /**
   * Computes the credit pack price for a given slot price.
   */
  public function getPackPrice(Price $slot_price): Price {
    $full_price = $slot_price->multiply((string) $this->getPackSize());
    $multiplier = bcdiv((string) (100 - $this->getDiscountPercentage()), '100', 6);
    return $this->rounder->round($full_price->multiply($multiplier));
  }

}
