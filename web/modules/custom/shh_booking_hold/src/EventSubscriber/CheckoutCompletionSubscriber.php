<?php

namespace Drupal\shh_booking_hold\EventSubscriber;

use Drupal\shh_booking_hold\BookingHoldManager;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Promotes on-hold booking events to booked when an order is placed.
 *
 * Runs on the same workflow transition as
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
    // post_transition, i.e. after the placed order is saved: the bat_event
    // save inside promoteToBooked() triggers shh_booking_log's rider
    // confirmation email, which must carry the real order number. During
    // pre_transition the number only exists on the in-flight order object
    // ($event->getEntity()) — a storage load still sees NULL, so anything
    // resolving the order independently (as the logger does) gets the
    // wrong reference. Priority 0 still beats the order receipt (-100).
    return ['commerce_order.place.post_transition' => 'promoteHolds'];
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
