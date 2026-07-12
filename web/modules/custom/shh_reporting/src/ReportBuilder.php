<?php

namespace Drupal\shh_reporting;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Computes the facility-utilization and revenue report data (task 0008).
 *
 * Utilization counts *slot time occupied*, from the BAT events that are
 * the platform's source of truth for occupancy: `*_booked` events
 * (customer bookings — cancelled ones are released back to available by
 * 0015, so they correctly stop counting) and, reported separately,
 * `*_not_available` staff blocks (0004). Duplicate events on the same
 * unit and window (0012 creates one event per rider of capacity) are
 * deduped: a slot is occupied once no matter how many riders share it.
 *
 * Revenue is grouped by order item type over placed, non-canceled
 * orders. Item lines are gross (VAT-inclusive, before order-level
 * adjustments); order-level adjustments (e.g. 0017's bundle discount)
 * cannot be attributed to a single item type and are reported as their
 * own lines, so the grand total always reconciles with Commerce's own
 * order totals.
 */
class ReportBuilder {

  use StringTranslationTrait;

  /**
   * The bookable day per 0016: 08:00–20:00.
   */
  const OPEN_HOURS_PER_DAY = 12;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Utilization rows per facility per period.
   *
   * @param \DateTimeImmutable $from
   *   Range start (inclusive, midnight).
   * @param \DateTimeImmutable $to_exclusive
   *   Range end (exclusive, midnight).
   * @param string $granularity
   *   'week' (ISO, Monday-start) or 'month'.
   *
   * @return array[]
   *   One row per period × facility: period label, facility label,
   *   open/booked/blocked hours, utilization ratio (0..1).
   */
  public function utilization(\DateTimeImmutable $from, \DateTimeImmutable $to_exclusive, string $granularity): array {
    $unit_map = shh_common_bat_unit_facility_map();
    $facilities = [];
    foreach ($unit_map as $unit_id => $info) {
      $facilities[$info['nid']]['label'] = $info['label'];
      $facilities[$info['nid']]['units'][] = $unit_id;
    }
    ksort($facilities);

    $occupied = $this->occupiedWindows($from, $to_exclusive, $unit_map);

    $rows = [];
    foreach ($this->periods($from, $to_exclusive, $granularity) as $period) {
      foreach ($facilities as $nid => $facility) {
        $days = (int) $period['start']->diff($period['end'])->days;
        $open_seconds = $days * self::OPEN_HOURS_PER_DAY * 3600 * count($facility['units']);
        $booked = $this->overlapSeconds($occupied[$nid]['booked'] ?? [], $period['start'], $period['end']);
        $blocked = $this->overlapSeconds($occupied[$nid]['blocked'] ?? [], $period['start'], $period['end']);
        $rows[] = [
          'period' => $period['label'],
          'facility' => $facility['label'],
          'open_hours' => $open_seconds / 3600,
          'booked_hours' => $booked / 3600,
          'blocked_hours' => $blocked / 3600,
          'utilization' => $open_seconds ? $booked / $open_seconds : 0,
        ];
      }
    }
    return $rows;
  }

  /**
   * Revenue rows by order item type, plus adjustment and total lines.
   *
   * @return array
   *   'items': rows of type label / order count / item count / gross;
   *   'adjustments': label => sum (order-level, non-included only);
   *   'orders_total': sum of order totals; 'order_count': orders seen.
   */
  public function revenue(\DateTimeImmutable $from, \DateTimeImmutable $to_exclusive): array {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $ids = $order_storage->getQuery()
      ->condition('placed', $from->getTimestamp(), '>=')
      ->condition('placed', $to_exclusive->getTimestamp(), '<')
      ->condition('state', 'canceled', '<>')
      ->accessCheck(FALSE)
      ->execute();

    $item_type_labels = [];
    foreach ($this->entityTypeManager->getStorage('commerce_order_item_type')->loadMultiple() as $type) {
      $item_type_labels[$type->id()] = $type->label();
    }

    $items = [];
    $adjustments = [];
    $orders_total = '0';
    $order_count = 0;
    foreach ($order_storage->loadMultiple($ids) as $order) {
      $order_count++;
      $total = $order->getTotalPrice();
      if ($total) {
        $orders_total = bcadd($orders_total, $total->getNumber(), 2);
      }
      foreach ($order->getItems() as $item) {
        $bundle = $item->bundle();
        $items[$bundle]['label'] = $item_type_labels[$bundle] ?? $bundle;
        $items[$bundle]['orders'][$order->id()] = TRUE;
        $items[$bundle]['count'] = ($items[$bundle]['count'] ?? 0) + (int) $item->getQuantity();
        $item_total = $item->getTotalPrice();
        $items[$bundle]['gross'] = bcadd($items[$bundle]['gross'] ?? '0', $item_total ? $item_total->getNumber() : '0', 2);
      }
      foreach ($order->getAdjustments() as $adjustment) {
        if ($adjustment->isIncluded()) {
          continue;
        }
        $label = (string) $adjustment->getLabel();
        $adjustments[$label] = bcadd($adjustments[$label] ?? '0', $adjustment->getAmount()->getNumber(), 2);
      }
    }
    ksort($items);

    return [
      'items' => array_map(fn ($row) => [
        'label' => $row['label'],
        'order_count' => count($row['orders']),
        'item_count' => $row['count'],
        'gross' => $row['gross'],
      ], $items),
      'adjustments' => $adjustments,
      'orders_total' => $orders_total,
      'order_count' => $order_count,
    ];
  }

  /**
   * Deduped occupied windows per facility, split booked/blocked.
   *
   * @return array
   *   nid => ['booked' => [[start, end], ...], 'blocked' => [...]],
   *   timestamps, deduped per (unit, window, kind).
   */
  protected function occupiedWindows(\DateTimeImmutable $from, \DateTimeImmutable $to_exclusive, array $unit_map): array {
    $storage = $this->entityTypeManager->getStorage('bat_event');
    $ids = $storage->getQuery()
      ->condition('type', 'availability_hourly')
      ->condition('event_dates.value', $to_exclusive->format('Y-m-d\TH:i:s'), '<')
      ->condition('event_dates.end_value', $from->format('Y-m-d\TH:i:s'), '>')
      ->accessCheck(FALSE)
      ->execute();

    $windows = [];
    $seen = [];
    foreach ($storage->loadMultiple($ids) as $event) {
      $state = $event->get('event_state_reference')->entity;
      $machine_name = $state ? $state->getMachineName() : '';
      if (str_ends_with($machine_name, '_booked')) {
        $kind = 'booked';
      }
      elseif (str_ends_with($machine_name, '_not_available')) {
        $kind = 'blocked';
      }
      else {
        continue;
      }
      $unit_id = (int) $event->get('event_bat_unit_reference')->target_id;
      $nid = $unit_map[$unit_id]['nid'] ?? NULL;
      if (!$nid) {
        continue;
      }
      $start = new \DateTimeImmutable($event->get('event_dates')->value);
      $end = new \DateTimeImmutable($event->get('event_dates')->end_value);
      $key = "$unit_id:$kind:" . $start->getTimestamp() . ':' . $end->getTimestamp();
      if (isset($seen[$key])) {
        continue;
      }
      $seen[$key] = TRUE;
      $windows[$nid][$kind][] = [$start->getTimestamp(), $end->getTimestamp()];
    }
    return $windows;
  }

  /**
   * Total seconds of windows overlapping [$start, $end).
   */
  protected function overlapSeconds(array $windows, \DateTimeImmutable $start, \DateTimeImmutable $end): int {
    $range_start = $start->getTimestamp();
    $range_end = $end->getTimestamp();
    $seconds = 0;
    foreach ($windows as [$w_start, $w_end]) {
      $overlap = min($w_end, $range_end) - max($w_start, $range_start);
      if ($overlap > 0) {
        $seconds += $overlap;
      }
    }
    return $seconds;
  }

  /**
   * Period list (clipped to the range) for a granularity.
   *
   * @return array[]
   *   Rows of 'label', 'start', 'end' (both DateTimeImmutable; end
   *   exclusive).
   */
  protected function periods(\DateTimeImmutable $from, \DateTimeImmutable $to_exclusive, string $granularity): array {
    $periods = [];
    if ($granularity === 'month') {
      $cursor = $from->modify('first day of this month')->setTime(0, 0);
      while ($cursor < $to_exclusive) {
        $next = $cursor->modify('first day of next month');
        $periods[] = [
          'label' => $cursor->format('F Y'),
          'start' => max($cursor, $from),
          'end' => min($next, $to_exclusive),
        ];
        $cursor = $next;
      }
    }
    else {
      $cursor = $from->modify('monday this week')->setTime(0, 0);
      while ($cursor < $to_exclusive) {
        $next = $cursor->modify('+1 week');
        $periods[] = [
          'label' => $cursor->format('o-\WW') . ' (' . $cursor->format('j M') . ' – ' . $next->modify('-1 day')->format('j M') . ')',
          'start' => max($cursor, $from),
          'end' => min($next, $to_exclusive),
        ];
        $cursor = $next;
      }
    }
    return $periods;
  }

}
