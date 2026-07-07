<?php

namespace Drupal\shh_booking_log;

use Drupal\bat_event\Entity\Event;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\shh_booking_log\Entity\BookingLogEntry;
use Psr\Log\LoggerInterface;

/**
 * Records booking lifecycle transitions and notifies the rider.
 *
 * Hooked at the bat_event storage level (insert/update/delete) rather than
 * into any one caller, because every booking path on this platform ends in
 * a bat_event state change: shh_booking_hold's cart-add holds, promotions
 * and releases (0012), shh_cancellation_policy's cancellations (0015),
 * Commerce Cart cron's expiry-driven releases, bee's own checkout
 * finalization, and — the case that rules out order-level hooks entirely —
 * staff creating events directly through bee's availability management UI
 * (0016), where no order exists at all.
 */
class BookingLifecycleLogger {

  const STATE_ON_HOLD = 'bee_hourly_on_hold';
  const STATE_BOOKED = 'bee_hourly_booked';
  const STATE_AVAILABLE = 'bee_hourly_available';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected MailManagerInterface $mailManager,
    protected LanguageManagerInterface $languageManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Logs one transition; the single entry point for all three hooks.
   *
   * Never lets a logging failure break the booking operation that
   * triggered it — an audit trail that can abort a checkout is worse
   * than a gap in the trail (the gap is itself logged to watchdog).
   */
  public function logTransition(Event $event, ?string $from, ?string $to): void {
    try {
      $this->doLogTransition($event, $from, $to);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to log booking transition for event @id: @message', [
        '@id' => $event->id(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Reads the current state machine name off a BAT event.
   */
  public function getStateName(Event $event): ?string {
    $state = $event->get('event_state_reference')->entity;
    return $state ? $state->getMachineName() : NULL;
  }

  protected function doLogTransition(Event $event, ?string $from, ?string $to): void {
    $order = $this->findOrder($event);
    $actor_kind = $this->classifyActor($order);

    $entry = BookingLogEntry::create([
      'event_id' => (int) $event->id(),
      'facility' => $this->findFacilityId($event),
      'slot_start' => $event->getStartDate()->getTimestamp(),
      'slot_end' => $event->getEndDate()->getTimestamp(),
      'state_from' => $from,
      'state_to' => $to,
      'actor' => $this->currentUser->id(),
      'actor_kind' => $actor_kind,
      'order_id' => $order?->id(),
    ]);

    $mail_key = $this->pickNotification($from, $to, $actor_kind);
    if ($mail_key && $order && ($email = $order->getEmail())) {
      $facility = $entry->get('facility')->entity;
      $this->mailManager->mail('shh_booking_log', $mail_key, $email,
        $this->languageManager->getDefaultLanguage()->getId(), [
          'facility_label' => $facility ? $facility->label() : 'the facility',
          'slot_start' => $event->getStartDate(),
          'slot_end' => $event->getEndDate(),
          'order_number' => $order->getOrderNumber() ?: $order->id(),
        ]);
      $entry->set('notification', $mail_key);
    }

    $entry->save();
  }

  /**
   * Which rider email (if any) this transition warrants.
   *
   * - held -> booked: confirmation. Sent in addition to Commerce's order
   *   receipt because the receipt line ("1 x Oval track") carries no slot
   *   date or time at all — the rider otherwise has no written record of
   *   *when* their booking is.
   * - booked -> available: the booking was cancelled (self-service 0015,
   *   or staff releasing it) — confirm that to the rider either way.
   * - held -> available by the system: the unpaid cart hold expired
   *   (Commerce Cart cron). A rider who removed the item from their own
   *   cart gets no email — they did it themselves, seconds ago.
   */
  protected function pickNotification(?string $from, ?string $to, string $actor_kind): ?string {
    if ($from === self::STATE_ON_HOLD && $to === self::STATE_BOOKED) {
      return 'booking_confirmed';
    }
    if ($from === self::STATE_BOOKED && $to === self::STATE_AVAILABLE) {
      return 'booking_cancelled';
    }
    if ($from === self::STATE_ON_HOLD && $to === self::STATE_AVAILABLE
      && $actor_kind === BookingLogEntry::ACTOR_SYSTEM) {
      return 'hold_expired';
    }
    return NULL;
  }

  /**
   * customer / staff / system, from the acting session and order owner.
   */
  protected function classifyActor($order): string {
    $uid = (int) $this->currentUser->id();
    if ($uid === 0) {
      // Anonymous sessions cannot reach any booking-mutating path
      // (booking requires authentication per decision 0017), so uid 0
      // here means cron/CLI: cart-expiry cleanup, drush, etc.
      return BookingLogEntry::ACTOR_SYSTEM;
    }
    if ($order) {
      return $uid === (int) $order->getCustomerId()
        ? BookingLogEntry::ACTOR_CUSTOMER
        : BookingLogEntry::ACTOR_STAFF;
    }
    // No order resolved. Either staff mutating availability directly
    // through bee's management UI (0016 — genuinely orderless), or a
    // rider's own cart-add hold: shh_booking_hold saves the event before
    // wiring booking_event_reference, so the event→booking→order chain
    // is not queryable yet at insert time. Classify by what the actor is
    // allowed to do instead — bee gates its management UI on this
    // permission (bookable_facility is the only BEE-enabled bundle).
    return $this->currentUser->hasPermission('manage availability for all bookable_facility nodes')
      ? BookingLogEntry::ACTOR_STAFF
      : BookingLogEntry::ACTOR_CUSTOMER;
  }

  /**
   * The bookable-facility node this event's unit belongs to, if any.
   */
  protected function findFacilityId(Event $event): ?int {
    $unit_id = $event->get('event_bat_unit_reference')->target_id;
    if (!$unit_id) {
      return NULL;
    }
    foreach (['field_availability_hourly', 'field_availability_daily'] as $field) {
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition($field, $unit_id)
        ->range(0, 1)
        ->accessCheck(FALSE)
        ->execute();
      if ($nids) {
        return (int) reset($nids);
      }
    }
    return NULL;
  }

  /**
   * The commerce order behind this event, via bat_booking, if any.
   *
   * Staff-created availability events (0016) have no booking/order —
   * that absence is expected and logged as such, not an error. Runs
   * during predelete cascades too (cart expiry), where the order item
   * and order still exist and remain loadable.
   */
  protected function findOrder(Event $event) {
    $booking_ids = $this->entityTypeManager->getStorage('bat_booking')->getQuery()
      ->condition('booking_event_reference', $event->id())
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    if (!$booking_ids) {
      return NULL;
    }
    $item_ids = $this->entityTypeManager->getStorage('commerce_order_item')->getQuery()
      ->condition('field_booking', reset($booking_ids))
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    if (!$item_ids) {
      return NULL;
    }
    $item = $this->entityTypeManager->getStorage('commerce_order_item')->load(reset($item_ids));
    return $item?->getOrder();
  }

}
