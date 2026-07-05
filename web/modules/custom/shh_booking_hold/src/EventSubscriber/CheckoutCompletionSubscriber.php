<?php

namespace Drupal\shh_booking_hold\EventSubscriber;

use Drupal\shh_booking_hold\BookingHoldManager;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Promotes on-hold booking events to booked when an order is placed.
 *
 * Runs on the same event as
 * \Drupal\bee\EventSubscriber\OrderEventSubscriber::finalizeCart(); that
 * subscriber only creates a fresh event if the booking has none yet, so it
 * is a no-op here (our on-hold event was already created at cart-add time by
 * \Drupal\shh_booking_hold\BookingHoldManager::placeHold()) — no ordering
 * dependency between the two subscribers.
 */
class CheckoutCompletionSubscriber implements EventSubscriberInterface {

  public function __construct(protected BookingHoldManager $holdManager) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return ['commerce_order.place.pre_transition' => 'promoteHolds'];
  }

  /**
   * Promotes all "bee" order items' held events to booked.
   */
  public function promoteHolds(WorkflowTransitionEvent $event): void {
    $order = $event->getEntity();
    foreach ($order->getItems() as $order_item) {
      $this->holdManager->promoteToBooked($order_item);
    }
  }

}
