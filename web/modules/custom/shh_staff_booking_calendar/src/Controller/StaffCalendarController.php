<?php

namespace Drupal\shh_staff_booking_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Combined staff booking calendar across all bookable facilities.
 *
 * See docs/project-management/tasks/0004-staff-admin-booking-calendar.md.
 * Deliberate deviation from decision 0016 recorded there: no
 * `event_source` field was ever built, and none is needed — BAT event
 * states already carry the distinction (`booked` = customer via
 * checkout, `not_available` = orderless staff block, `on_hold` = cart),
 * and 0002's booking log records the acting party per transition.
 * Staff blocks are `not_available` (bee's own blocking semantic), NOT
 * order-less `booked` events, preserving the platform invariant that
 * `booked` implies a Commerce order behind it.
 */
class StaffCalendarController extends ControllerBase {

  /**
   * Colors keyed by classification; also used by the page legend.
   */
  const COLORS = [
    'booked' => '#1d4ed8',
    'on_hold' => '#b45309',
    'blocked' => '#4b5563',
  ];

  /**
   * Builds the calendar page.
   */
  public function calendar(): array {
    $build = [];

    $build['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['shh-staff-calendar-actions']],
      'block' => [
        '#type' => 'link',
        '#title' => $this->t('Block facility time'),
        '#url' => Url::fromRoute('shh_staff_booking_calendar.block'),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ],
    ];

    $legend_items = [];
    foreach ([
      'booked' => $this->t('Customer booking'),
      'on_hold' => $this->t('Cart hold (unpaid, expires)'),
      'blocked' => $this->t('Staff block — click to remove'),
    ] as $key => $label) {
      $legend_items[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['shh-staff-calendar-legend-item'],
          'style' => '--shh-legend-color: ' . self::COLORS[$key],
        ],
        '#value' => $label,
      ];
    }
    $build['legend'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['shh-staff-calendar-legend']],
      'items' => $legend_items,
    ];

    $build['calendar'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'shh-staff-calendar'],
    ];

    $build['#attached']['library'][] = 'shh_staff_booking_calendar/staff_calendar';
    $build['#attached']['drupalSettings']['shhStaffBookingCalendar'] = [
      'eventsUrl' => Url::fromRoute('shh_staff_booking_calendar.events')->toString(),
    ];

    return $build;
  }

  /**
   * JSON events feed for the calendar (FullCalendar `events` URL).
   *
   * FullCalendar appends `start` and `end` ISO-8601 query parameters for
   * the visible range. `available` events are skipped — the calendar
   * shows what occupies time, not bee's baseline availability records.
   */
  public function events(Request $request): JsonResponse {
    try {
      $start = new \DateTimeImmutable($request->query->get('start', 'now'));
      $end = new \DateTimeImmutable($request->query->get('end', '+1 month'));
    }
    catch (\Exception) {
      throw new BadRequestHttpException('Invalid start/end.');
    }

    $facility_by_unit = $this->facilityByUnit();

    $storage = $this->entityTypeManager()->getStorage('bat_event');
    $ids = $storage->getQuery()
      ->condition('type', 'availability_hourly')
      ->condition('event_dates.value', $end->format('Y-m-d\TH:i:s'), '<')
      ->condition('event_dates.end_value', $start->format('Y-m-d\TH:i:s'), '>')
      ->accessCheck(FALSE)
      ->execute();

    $events = [];
    foreach ($storage->loadMultiple($ids) as $event) {
      $state = $event->get('event_state_reference')->entity;
      $machine_name = $state ? $state->getMachineName() : '';
      $shown = str_ends_with($machine_name, '_booked')
        || str_ends_with($machine_name, '_on_hold')
        || str_ends_with($machine_name, '_not_available');
      if (!$shown) {
        continue;
      }
      $unit_id = (int) $event->get('event_bat_unit_reference')->target_id;
      $facility = $facility_by_unit[$unit_id]['label'] ?? $this->t('Unknown facility');

      $item = [
        'id' => $event->id(),
        'start' => $event->get('event_dates')->value,
        'end' => $event->get('event_dates')->end_value,
      ];
      if (str_ends_with($machine_name, '_booked')) {
        $order = $this->findOrder($event);
        $item['title'] = $order
          ? $this->t('@facility — booked (@number)', [
            '@facility' => $facility,
            '@number' => $order->getOrderNumber() ?: $order->id(),
          ])
          : $this->t('@facility — booked (no order)', ['@facility' => $facility]);
        $item['color'] = self::COLORS['booked'];
        if ($order) {
          $item['url'] = Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $order->id()])->toString();
        }
      }
      elseif (str_ends_with($machine_name, '_on_hold')) {
        $item['title'] = $this->t('@facility — cart hold', ['@facility' => $facility]);
        $item['color'] = self::COLORS['on_hold'];
      }
      else {
        $item['title'] = $this->t('@facility — staff block', ['@facility' => $facility]);
        $item['color'] = self::COLORS['blocked'];
        $item['url'] = Url::fromRoute('shh_staff_booking_calendar.release', ['bat_event' => $event->id()])->toString();
      }
      $events[] = $item;
    }

    $response = new JsonResponse($events);
    $response->setPrivate();
    $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
    return $response;
  }

  /**
   * Maps BAT unit id => ['label' => facility label, 'nid' => node id].
   *
   * The unit entities' own labels are stale (unit 1 still carries the
   * pre-rename "Outdoor Arena 1" name), so facility names come from the
   * owning nodes — the same unit→node resolution 0002's logger uses.
   */
  protected function facilityByUnit(): array {
    $map = [];
    $nodes = $this->entityTypeManager()->getStorage('node')->loadByProperties([
      'type' => 'bookable_facility',
    ]);
    foreach ($nodes as $node) {
      foreach (['field_availability_hourly', 'field_availability_daily'] as $field) {
        if (!$node->hasField($field)) {
          continue;
        }
        foreach ($node->get($field)->getValue() as $value) {
          $map[(int) $value['target_id']] = [
            'label' => $node->label(),
            'nid' => (int) $node->id(),
          ];
        }
      }
    }
    return $map;
  }

  /**
   * The commerce order behind a booked event, via bat_booking, if any.
   *
   * Same chain as 0002's BookingLifecycleLogger::findOrder(): event →
   * bat_booking (booking_event_reference) → order item (field_booking)
   * → order. Orderless booked events are possible (staff edits through
   * bee's own per-facility screen) and render as "no order".
   */
  protected function findOrder($event) {
    $booking_ids = $this->entityTypeManager()->getStorage('bat_booking')->getQuery()
      ->condition('booking_event_reference', $event->id())
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    if (!$booking_ids) {
      return NULL;
    }
    $item_ids = $this->entityTypeManager()->getStorage('commerce_order_item')->getQuery()
      ->condition('field_booking', reset($booking_ids))
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    if (!$item_ids) {
      return NULL;
    }
    $item = $this->entityTypeManager()->getStorage('commerce_order_item')->load(reset($item_ids));
    return $item?->getOrder();
  }

}
