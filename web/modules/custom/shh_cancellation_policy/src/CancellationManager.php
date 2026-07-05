<?php

namespace Drupal\shh_cancellation_policy;

use Drupal\bat_booking\Entity\Booking;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enforces the cancellation_policy: policy + time-to-slot before authorizing
 * a refund and releasing the BAT event.
 *
 * See docs/project-management/decisions/0015-cancellation-refund-policy.md.
 */
class CancellationManager {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Attempts to cancel a booking order item.
   *
   * @return array{authorized: bool, reason: string}
   *   'authorized' is TRUE only if the refund was issued and the slot
   *   released. FALSE means the request was denied outright (no partial
   *   "cancelled but unrefunded" state — see CancelBookingForm).
   */
  public function cancelBooking(OrderItemInterface $order_item): array {
    if ($order_item->bundle() !== 'bee') {
      return ['authorized' => FALSE, 'reason' => 'not_a_booking'];
    }

    $booking = $order_item->get('field_booking')->entity;
    if (!$booking instanceof Booking) {
      return ['authorized' => FALSE, 'reason' => 'no_booking'];
    }

    $node = $order_item->get('field_node')->entity;
    if (!$node || !$node->hasField('field_cancellation_policy') || $node->get('field_cancellation_policy')->isEmpty()) {
      return ['authorized' => FALSE, 'reason' => 'no_policy_configured'];
    }
    $policy = $node->get('field_cancellation_policy')->entity;

    $booked_events = [];
    $earliest_start = NULL;
    foreach ($booking->get('booking_event_reference')->referencedEntities() as $event) {
      $state = $event->get('event_state_reference')->entity;
      if (!$state || $state->getMachineName() !== 'bee_hourly_booked') {
        continue;
      }
      $booked_events[] = $event;
      $start = $event->getStartDate();
      if ($earliest_start === NULL || $start < $earliest_start) {
        $earliest_start = $start;
      }
    }
    if (empty($booked_events) || !$earliest_start) {
      return ['authorized' => FALSE, 'reason' => 'no_booked_events'];
    }

    $hours_until_start = ($earliest_start->getTimestamp() - \Drupal::time()->getRequestTime()) / 3600;
    if ($hours_until_start < $policy->getRefundWindowHours()) {
      $this->logger->info('Cancellation denied for order item @item: @hours hours until start, inside the @window-hour window.', [
        '@item' => $order_item->id(),
        '@hours' => round($hours_until_start, 1),
        '@window' => $policy->getRefundWindowHours(),
      ]);
      return ['authorized' => FALSE, 'reason' => 'inside_refund_window'];
    }

    // Outside the window: authorize the refund, then release the slot(s).
    $this->refundOrderItem($order_item);

    $available_state = bat_event_load_state_by_machine_name('bee_hourly_available');
    $released = [];
    foreach ($booked_events as $event) {
      $event->set('event_state_reference', $available_state->id());
      $event->save();
      $released[] = $event->id();
    }

    $this->logger->info('Cancellation authorized for order item @item: refunded and released event(s) [@ids].', [
      '@item' => $order_item->id(),
      '@ids' => implode(',', $released),
    ]);

    return ['authorized' => TRUE, 'reason' => 'refunded'];
  }

  /**
   * Refunds the payment covering this order item, if one exists.
   *
   * Refunds the order item's own total (not necessarily the whole order —
   * an order could in principle carry more than one booking line, though
   * this platform's booking UI only ever creates one at a time today).
   */
  protected function refundOrderItem(OrderItemInterface $order_item): void {
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
        $this->logger->warning('Payment @id\'s gateway does not support refunds; cannot refund order item @item automatically.', [
          '@id' => $payment->id(),
          '@item' => $order_item->id(),
        ]);
        continue;
      }
      $amount = $order_item->getTotalPrice();
      if ($amount->lessThanOrEqual($payment->getBalance())) {
        $gateway_plugin->refundPayment($payment, $amount);
        return;
      }
    }

    $this->logger->warning('No refundable payment found for order @order (order item @item) — refund not issued automatically.', [
      '@order' => $order->id(),
      '@item' => $order_item->id(),
    ]);
  }

}
