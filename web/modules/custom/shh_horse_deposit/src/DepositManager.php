<?php

namespace Drupal\shh_horse_deposit;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Computes deposit amounts and enforces deposit-refund policy.
 *
 * See docs/project-management/tasks/0001-horse-deposit-reservation-flow.md.
 */
class DepositManager {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
    protected TimeInterface $time,
  ) {}

  /**
   * Computes the deposit amount for a horse variation.
   *
   * A percentage of the variation's own price (config: deposit_percentage,
   * default 20%) — not a flat amount, so it scales with the horse's price.
   */
  public function computeDepositAmount(ProductVariationInterface $variation): Price {
    $percentage = $this->configFactory->get('shh_horse_deposit.settings')->get('deposit_percentage') ?? 20;
    $price = $variation->getPrice();
    $number = bcmul($price->getNumber(), (string) ($percentage / 100), 6);
    return new Price($number, $price->getCurrencyCode());
  }

  /**
   * Whether the variation is currently eligible to take a deposit.
   */
  public function isDepositable(ProductVariationInterface $variation): bool {
    if (!$variation->hasField('field_sale_state')) {
      return FALSE;
    }
    return $variation->get('field_sale_state')->value === 'for_sale';
  }

  /**
   * Marks a variation reserved-deposit — called on checkout completion.
   */
  public function markReservedDeposit(ProductVariationInterface $variation): void {
    $variation->set('field_sale_state', 'reserved-deposit');
    $variation->save();
    $this->logger->info('Variation @id marked reserved-deposit.', ['@id' => $variation->id()]);
  }

  /**
   * Attempts to cancel a paid deposit.
   *
   * Unlike a facility booking hold (0015), the horse is released back to
   * `available` *regardless* of refund eligibility — the seller wants it
   * back on the market immediately if the buyer isn't completing the
   * purchase; there's no "disincentivize late cancellation of scarce
   * inventory" reason to withhold that the way there is for an hourly slot.
   * Only whether the deposit itself is refunded depends on the policy
   * window.
   *
   * @return array{released: bool, refunded: bool, reason: string}
   *   Whether the horse was released, whether the deposit was refunded,
   *   and a machine reason code.
   */
  public function cancelDeposit(OrderItemInterface $order_item): array {
    if ($order_item->bundle() !== 'horse_deposit') {
      return ['released' => FALSE, 'refunded' => FALSE, 'reason' => 'not_a_deposit'];
    }

    $variation = $order_item->getPurchasedEntity();
    if (!$variation instanceof ProductVariationInterface) {
      return ['released' => FALSE, 'refunded' => FALSE, 'reason' => 'no_variation'];
    }
    if ($variation->get('field_sale_state')->value !== 'reserved-deposit') {
      return ['released' => FALSE, 'refunded' => FALSE, 'reason' => 'not_reserved_deposit'];
    }

    $order = $order_item->getOrder();
    $placed = $order->getPlacedTime();
    if (!$placed) {
      return ['released' => FALSE, 'refunded' => FALSE, 'reason' => 'order_not_placed'];
    }

    $refunded = FALSE;
    if ($variation->hasField('field_deposit_policy') && !$variation->get('field_deposit_policy')->isEmpty()) {
      $policy = $variation->get('field_deposit_policy')->entity;
      $days_since_deposit = ($this->time->getRequestTime() - $placed) / 86400;
      if ($days_since_deposit <= $policy->getRefundWindowDays()) {
        $refunded = $this->refundOrderItem($order_item);
      }
      else {
        $this->logger->info('Deposit refund denied for order item @item: @days days since deposit, outside the @window-day window.', [
          '@item' => $order_item->id(),
          '@days' => round($days_since_deposit, 1),
          '@window' => $policy->getRefundWindowDays(),
        ]);
      }
    }
    // No policy configured: no refund path, but the horse is still
    // released — same "fail closed on refund, not on release" logic.
    $variation->set('field_sale_state', 'for_sale');
    $variation->save();

    $this->logger->info('Deposit cancelled for order item @item: released (refunded: @refunded).', [
      '@item' => $order_item->id(),
      '@refunded' => $refunded ? 'yes' : 'no',
    ]);

    return ['released' => TRUE, 'refunded' => $refunded, 'reason' => $refunded ? 'refunded' : 'released_unrefunded'];
  }

  /**
   * Staff action: release a horse from a deposit reservation.
   *
   * The robust, "free up the horse regardless of how it got here"
   * counterpart to the rider-facing self-service cancel (which needs a
   * placed order it owns). Used for real-world corrections and testing:
   *
   * - If a *placed* deposit order still backs the reservation, this
   *   delegates to cancelDeposit() so the normal rules apply (release
   *   always; refund only within the policy window and only if a
   *   payment was actually captured — an unpaid deposit refunds
   *   nothing).
   * - If nothing valid backs it (an abandoned-cart draft, an orphaned
   *   state, or a value set out-of-band), the horse is still released
   *   directly to `for_sale`. There is no payment to refund in that
   *   case by definition.
   *
   * @return array{released: bool, via: string, refunded: bool, reason: string}
   *   Whether the horse was released, which path released it
   *   ('deposit_order' or 'direct'), whether a deposit was refunded,
   *   and a machine reason code.
   */
  public function releaseReservation(ProductVariationInterface $variation): array {
    if (!$variation->hasField('field_sale_state') || $variation->get('field_sale_state')->value !== 'reserved-deposit') {
      return ['released' => FALSE, 'via' => 'none', 'refunded' => FALSE, 'reason' => 'not_reserved_deposit'];
    }

    // Prefer cancelling through a real placed deposit order so refund
    // handling runs. Most recent first, so a re-deposited horse cancels
    // its current reservation rather than an old completed one.
    $item_ids = $this->entityTypeManager->getStorage('commerce_order_item')->getQuery()
      ->condition('type', 'horse_deposit')
      ->condition('purchased_entity', $variation->id())
      ->accessCheck(FALSE)
      ->execute();
    if ($item_ids) {
      $items = $this->entityTypeManager->getStorage('commerce_order_item')->loadMultiple($item_ids);
      krsort($items);
      foreach ($items as $item) {
        $order = $item->getOrder();
        if ($order && $order->getPlacedTime()) {
          $result = $this->cancelDeposit($item);
          if ($result['released']) {
            return $result + ['via' => 'deposit_order'];
          }
        }
      }
    }

    // Nothing valid backs the reservation — release the horse directly.
    $variation->set('field_sale_state', 'for_sale');
    $variation->save();
    $this->logger->warning('Horse variation @id force-released from reserved-deposit by staff: no placed deposit order to cancel through.', ['@id' => $variation->id()]);
    return ['released' => TRUE, 'via' => 'direct', 'refunded' => FALSE, 'reason' => 'direct_release'];
  }

  /**
   * Refunds the payment covering this order item, if one exists.
   */
  protected function refundOrderItem(OrderItemInterface $order_item): bool {
    $order = $order_item->getOrder();
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);

    foreach ($payments as $payment) {
      if (!in_array($payment->getState()->getId(), ['completed', 'partially_refunded'], TRUE)) {
        continue;
      }
      $gateway_plugin = $payment->getPaymentGateway()->getPlugin();
      if (!$gateway_plugin instanceof SupportsRefundsInterface) {
        continue;
      }
      $amount = $order_item->getTotalPrice();
      if ($amount->lessThanOrEqual($payment->getBalance())) {
        $gateway_plugin->refundPayment($payment, $amount);
        return TRUE;
      }
    }

    $this->logger->warning('No refundable payment found for order @order (order item @item).', [
      '@order' => $order->id(),
      '@item' => $order_item->id(),
    ]);
    return FALSE;
  }

}
