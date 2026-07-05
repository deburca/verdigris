<?php

namespace Drupal\shh_booking_hold;

use Drupal\bat_booking\Entity\Booking;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\node\Entity\NodeType;
use Psr\Log\LoggerInterface;

/**
 * Places, promotes, and releases hourly booking cart-holds.
 *
 * This is the prototype for the concurrency problem flagged as the hardest
 * part of the shh booking build (see
 * docs/project-management/infrastructure/shh-stables-platform-model.md and
 * docs/project-management/tasks/0012-cart-hold-concurrency-prototype.md).
 *
 * BEE/BAT only create the BAT event (which is what actually blocks a slot)
 * at *checkout completion* time (see
 * \Drupal\bee\EventSubscriber\OrderEventSubscriber::finalizeCart()) — there
 * is no reservation between "add to cart" and "complete checkout", so two
 * concurrent shoppers can both pass availability validation and both add the
 * same hourly slot to their carts. This service creates the event *at
 * cart-add time* instead, in the `bee_hourly_on_hold` (blocking) state, so
 * the very next shopper's availability check correctly sees the slot as
 * unavailable.
 */
class BookingHoldManager {

  const ON_HOLD_STATE = 'bee_hourly_on_hold';
  const BOOKED_STATE = 'bee_hourly_booked';
  const AVAILABLE_STATE = 'bee_hourly_available';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected LockBackendInterface $lock,
  ) {}

  /**
   * Places an on-hold event for a newly-added "bee" order item.
   *
   * Structurally mirrors
   * \Drupal\bee\EventSubscriber\OrderEventSubscriber::finalizeCart(), but
   * runs at cart-add time and uses the on-hold state instead of booked.
   */
  public function placeHold(OrderItemInterface $order_item): void {
    if ($order_item->bundle() !== 'bee') {
      return;
    }
    $booking = $order_item->get('field_booking')->entity;
    if (!$booking instanceof Booking) {
      return;
    }
    if (!$booking->get('booking_event_reference')->isEmpty()) {
      // Already has event(s) — nothing to do (defensive; shouldn't happen
      // for a freshly-inserted order item).
      return;
    }

    $node = $order_item->get('field_node')->entity;
    if (!$node) {
      return;
    }
    /** @var \Drupal\node\Entity\NodeType $node_type */
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    $bee_settings = $node_type->getThirdPartySetting('bee', 'bee');
    if (empty($bee_settings['bookable']) || $bee_settings['bookable_type'] !== 'hourly') {
      // This prototype only covers the hourly case — the only bookable_type
      // this platform actually uses (per shh-stables-platform-model.md).
      return;
    }

    $start_date = new \DateTime($booking->get('booking_start_date')->value);
    $end_date = new \DateTime($booking->get('booking_end_date')->value);

    // Serialize concurrent hold-placement attempts for this facility. This
    // is what actually closes the race window: without it, two near-
    // simultaneous requests could both run getAvailableUnits() before either
    // has created its event, and both would see the slot as free. The lock
    // forces the second request to wait until the first has committed its
    // event, so its own availability check then correctly sees the slot as
    // held. Scoped per-node (not per-timeslot) — coarser than strictly
    // necessary, but this platform's booking volume per facility is low
    // enough that this is not a contention concern, and it keeps the
    // prototype simple.
    $lock_name = 'shh_booking_hold:node:' . $node->id();
    $lock_acquired = $this->lock->acquire($lock_name, 10);
    if (!$lock_acquired) {
      $this->lock->wait($lock_name, 10);
      $lock_acquired = $this->lock->acquire($lock_name, 10);
    }
    if (!$lock_acquired) {
      throw new \RuntimeException('Could not acquire the booking lock for this facility; please try again.');
    }

    try {
      $on_hold_state = bat_event_load_state_by_machine_name(self::ON_HOLD_STATE);
      $available_units = $this->getAvailableUnits($node, $bee_settings, $start_date, $end_date);
      $unit_id = reset($available_units);
      if (!$unit_id) {
        // Availability changed between form validation and this insert hook
        // (a real, if narrow, race window, now closed for the common case
        // by the lock above — this remains as a defensive fallback for the
        // case where it's genuinely gone, e.g. a repeat booking created it
        // moments earlier).
        $this->logger->warning('No available unit for booking @booking on node @node (@start - @end) at hold-placement time.', [
          '@booking' => $booking->id(),
          '@node' => $node->id(),
          '@start' => $start_date->format('c'),
          '@end' => $end_date->format('c'),
        ]);
        throw new \RuntimeException('The selected time slot is no longer available.');
      }

      $capacity = $booking->get('booking_capacity')->value ?: 1;
      $events = [];
      for ($i = 0; $i < $capacity; $i++) {
        $event = bat_event_create(['type' => 'availability_hourly']);
        $event->set('event_dates', [
          'value' => $start_date->format('Y-m-d\TH:i:00'),
          'end_value' => $end_date->format('Y-m-d\TH:i:00'),
        ]);
        $event->set('event_state_reference', $on_hold_state->id());
        $event->set('event_bat_unit_reference', $unit_id);
        $event->save();
        $events[] = $event;
      }

      $booking->set('booking_event_reference', $events);
      $booking->save();

      $this->logger->info('Placed on-hold event(s) [@ids] for booking @booking (order item @item).', [
        '@ids' => implode(',', array_map(fn ($e) => $e->id(), $events)),
        '@booking' => $booking->id(),
        '@item' => $order_item->id(),
      ]);
    }
    finally {
      $this->lock->release($lock_name);
    }
  }

  /**
   * Promotes an order item's held event(s) to booked (checkout completion).
   */
  public function promoteToBooked(OrderItemInterface $order_item): void {
    if ($order_item->bundle() !== 'bee') {
      return;
    }
    $booking = $order_item->get('field_booking')->entity;
    if (!$booking instanceof Booking) {
      return;
    }

    $booked_state = bat_event_load_state_by_machine_name(self::BOOKED_STATE);
    $promoted = [];
    foreach ($booking->get('booking_event_reference')->referencedEntities() as $event) {
      $current_state = $event->get('event_state_reference')->entity;
      if ($current_state && $current_state->getMachineName() === self::ON_HOLD_STATE) {
        $event->set('event_state_reference', $booked_state->id());
        $event->save();
        $promoted[] = $event->id();
      }
    }
    if ($promoted) {
      $this->logger->info('Promoted event(s) [@ids] from on-hold to booked for booking @booking.', [
        '@ids' => implode(',', $promoted),
        '@booking' => $booking->id(),
      ]);
    }
  }

  /**
   * Releases an order item's held event(s) back to available.
   *
   * Called when a cart (or a single item within it) is abandoned — either a
   * user removing the item, or Commerce Cart's own cron-driven stale-cart
   * cleanup (\Drupal\commerce_cart\Cron), which is what makes the hold TTL
   * "tied to Commerce cart expiration config" rather than a separate timer.
   */
  public function releaseHold(OrderItemInterface $order_item): void {
    if ($order_item->bundle() !== 'bee') {
      return;
    }
    $booking = $order_item->get('field_booking')->entity;
    if (!$booking instanceof Booking) {
      return;
    }

    $available_state = bat_event_load_state_by_machine_name(self::AVAILABLE_STATE);
    $released = [];
    foreach ($booking->get('booking_event_reference')->referencedEntities() as $event) {
      $current_state = $event->get('event_state_reference')->entity;
      if ($current_state && $current_state->getMachineName() === self::ON_HOLD_STATE) {
        $event->set('event_state_reference', $available_state->id());
        $event->save();
        $released[] = $event->id();
      }
    }
    if ($released) {
      $this->logger->info('Released event(s) [@ids] from on-hold back to available for booking @booking.', [
        '@ids' => implode(',', $released),
        '@booking' => $booking->id(),
      ]);
    }
  }

  /**
   * Same availability query BEE itself uses (bee.module / AddReservationForm).
   */
  protected function getAvailableUnits($node, array $bee_settings, \DateTime $start_date, \DateTime $end_date): array {
    $units_ids = [];
    foreach ($node->get('field_availability_hourly') as $unit) {
      if ($unit->entity) {
        $units_ids[] = $unit->entity->id();
      }
    }

    $temp_end_date = clone $end_date;
    $temp_end_date->sub(new \DateInterval('PT1M'));

    $available_units_ids = bat_event_get_matching_units(
      $start_date,
      $temp_end_date,
      [self::AVAILABLE_STATE],
      [$bee_settings['type_id']],
      'availability_hourly'
    );

    return array_intersect($units_ids, $available_units_ids);
  }

}
