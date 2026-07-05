<?php

namespace Drupal\shh_horse_deposit;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
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
    return $variation->get('field_sale_state')->value === 'available';
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
      $days_since_deposit = (\Drupal::time()->getRequestTime() - $placed) / 86400;
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

    $variation->set('field_sale_state', 'available');
    $variation->save();

    $this->logger->info('Deposit cancelled for order item @item: released (refunded: @refunded).', [
      '@item' => $order_item->id(),
      '@refunded' => $refunded ? 'yes' : 'no',
    ]);

    return ['released' => TRUE, 'refunded' => $refunded, 'reason' => $refunded ? 'refunded' : 'released_unrefunded'];
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
      if (!$gateway_plugin instanceof \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface) {
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
