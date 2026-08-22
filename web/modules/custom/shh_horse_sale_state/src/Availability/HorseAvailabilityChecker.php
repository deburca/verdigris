<?php

namespace Drupal\shh_horse_sale_state\Availability;

use Drupal\commerce\Context;
use Drupal\commerce_order\AvailabilityCheckerInterface;
use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Blocks purchase of a horse variation whose field_sale_state isn't for_sale.
 *
 * See docs/project-management/tasks/0024-horse-sale-state-enforcement.md.
 *
 * Mirrors \Drupal\shh_horse_deposit\DepositManager::isDepositable()'s check,
 * but registered via Commerce's own pluggable availability-checker extension
 * point (tagged `commerce_order.availability_checker`) instead of a
 * form-alter. Commerce already validates every order item's
 * `purchased_entity` field with `PurchasedEntityAvailableConstraint`, which
 * calls the availability manager — so this checker is automatically
 * enforced by the standard `AddToCartForm` (add-to-cart) and again whenever
 * the cart order item is re-validated before checkout, with no changes
 * needed to any Commerce/BEE form.
 *
 * Deliberately applies to *any* order item purchasing a variation with
 * `field_sale_state`, not just the `horse` order item type — so it also
 * protects any other current or future purchase path built as a normal
 * `ContentEntityForm` (entity validation is what actually invokes this
 * checker; see `applies()`/`check()` below). This does *not* cover
 * `shh_horse_deposit`'s `PayDepositForm`: that form builds and saves its
 * `horse_deposit` order item directly rather than through
 * `ContentEntityForm`'s validate step, so this checker never runs for it.
 * That form is still safe, just via a different mechanism: it hides its
 * submit button (`#access = FALSE`) when
 * `DepositManager::isDepositable()` is false, and Drupal core's Form API
 * deliberately never processes input for an inaccessible element (see
 * the comment above `$process_input` in
 * `\Drupal\Core\Form\FormBuilder::doBuildForm()`) — confirmed empirically
 * while implementing this task: a forged POST against a hidden button
 * never reaches `submitForm()` at all, unlike a forged add-to-cart POST
 * for an unavailable horse, which reaches this checker's `check()` and is
 * rejected with a real validation violation regardless of button
 * accessibility.
 *
 * This only closes the race window at each validation point (add-to-cart,
 * and cart/checkout review); it does not place a transient hold the moment
 * an item is added to cart the way
 * \Drupal\shh_booking_hold\BookingHoldManager does for facility bookings.
 * Per this task's acceptance criteria, that narrower window is an accepted
 * minimum bar for horse sales: a horse purchase's checkout is comparatively
 * slow (arranging payment, etc.) and low-volume compared to hourly facility
 * slots, so the cost/benefit of building a full hold mechanism here doesn't
 * match 0012's — the state-machine subscriber below is what actually
 * removes a sold horse from availability once an order is genuinely placed.
 */
class HorseAvailabilityChecker implements AvailabilityCheckerInterface {

  use StringTranslationTrait;

  /**
   * Human-readable descriptions for each non-for_sale sale state.
   */
  protected const STATE_LABELS = [
    'reserved' => 'reserved',
    'sold' => 'sold',
    'reserved-deposit' => 'reserved (a deposit has already been paid)',
    'withdrawn' => 'withdrawn from sale',
  ];

  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();
    return $purchased_entity instanceof ProductVariationInterface
      && $purchased_entity->hasField('field_sale_state');
  }

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $order_item->getPurchasedEntity();
    $sale_state = $variation->get('field_sale_state')->value;

    if ($sale_state === 'for_sale') {
      return AvailabilityResult::neutral();
    }

    // A NULL sale_state (task 0057 — the herd roster) is deliberately
    // blocked here, not neutral: it means this horse has never been
    // promoted into the sale pipeline at all, not that its state is
    // simply unset by accident.
    $label = $sale_state !== NULL ? (self::STATE_LABELS[$sale_state] ?? $sale_state) : 'not for sale';
    return AvailabilityResult::unavailable($this->t('This horse is @state and cannot be purchased.', [
      '@state' => $label,
    ]));
  }

}
