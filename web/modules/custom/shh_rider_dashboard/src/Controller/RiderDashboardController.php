<?php

namespace Drupal\shh_rider_dashboard\Controller;

use Drupal\bat_booking\BookingInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the "My bookings, deposits, and credits" rider dashboard.
 *
 * See docs/project-management/tasks/0022-rider-dashboard.md. Cancel links
 * for a booking (0015) or a deposit (0001) previously only ever appeared
 * inline on that one order item's own rendered view — there was no list
 * to find them from at all. This page is purely a discovery aid: every
 * link it shows reuses the exact same routes/access checks
 * (CancelBookingAccessCheck, CancelDepositAccessCheck) already enforced
 * elsewhere, so it doesn't open any access surface those routes don't
 * already gate themselves.
 */
class RiderDashboardController extends ControllerBase {

  public function __construct(
    protected AccessManagerInterface $accessManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('access_manager'),
    );
  }

  /**
   * Builds the dashboard for the given rider.
   */
  public function dashboard(UserInterface $user): array {
    $order_item_storage = $this->entityTypeManager()->getStorage('commerce_order_item');

    $bee_ids = $order_item_storage->getQuery()
      ->condition('type', 'bee')
      ->condition('order_id.entity.uid', $user->id())
      ->condition('order_id.entity.state', 'draft', '<>')
      ->accessCheck(FALSE)
      ->execute();
    $deposit_ids = $order_item_storage->getQuery()
      ->condition('type', 'horse_deposit')
      ->condition('order_id.entity.uid', $user->id())
      ->condition('order_id.entity.state', 'draft', '<>')
      ->accessCheck(FALSE)
      ->execute();

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $bee_items */
    $bee_items = $bee_ids ? $order_item_storage->loadMultiple($bee_ids) : [];
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $deposit_items */
    $deposit_items = $deposit_ids ? $order_item_storage->loadMultiple($deposit_ids) : [];

    [$upcoming, $past] = $this->splitBookingsByDate($bee_items);

    $build = [];
    $build['bookings_upcoming'] = $this->buildBookingsSection(
      $this->t('Upcoming bookings'),
      $upcoming,
    );
    $build['deposits'] = $this->buildDepositsSection($deposit_items);
    $build['credits'] = $this->buildCreditsSection((int) $user->id());
    // Past bookings shown last and separately, per the task's own
    // acceptance criteria — no cancel link, they're already over.
    $build['bookings_past'] = $this->buildBookingsSection(
      $this->t('Past bookings'),
      $past,
      show_cancel: FALSE,
    );

    return $build;
  }

  /**
   * Splits a rider's bookings into upcoming vs. past.
   *
   * By their booking's start date, sorted soonest-first /
   * most-recent-first.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The rider's "bee" order items.
   *
   * @return array{0: array, 1: array}
   *   [upcoming, past], each an array of ['order_item' => ..., 'booking'
   *   => ..., 'start' => DrupalDateTime].
   */
  protected function splitBookingsByDate(array $order_items): array {
    $now = new DrupalDateTime('now');
    $upcoming = [];
    $past = [];

    foreach ($order_items as $order_item) {
      $booking = $order_item->get('field_booking')->entity;
      if (!$booking instanceof BookingInterface || $booking->get('booking_start_date')->isEmpty()) {
        continue;
      }
      $start = new DrupalDateTime($booking->get('booking_start_date')->value);
      $row = ['order_item' => $order_item, 'booking' => $booking, 'start' => $start];
      if ($start >= $now) {
        $upcoming[] = $row;
      }
      else {
        $past[] = $row;
      }
    }

    usort($upcoming, fn ($a, $b) => $a['start'] <=> $b['start']);
    usort($past, fn ($a, $b) => $b['start'] <=> $a['start']);

    return [$upcoming, $past];
  }

  /**
   * Builds a bookings list section (upcoming or past).
   */
  protected function buildBookingsSection(TranslatableMarkup $heading, array $rows, bool $show_cancel = TRUE): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mb-8']],
    ];
    $build['heading'] = ['#markup' => '<h2>' . $heading . '</h2>'];

    if (!$rows) {
      $build['empty'] = ['#markup' => '<p>' . $this->t('None.') . '</p>'];
      return $build;
    }

    $items = [];
    foreach ($rows as $row) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $row['order_item'];
      $node = $order_item->get('field_node')->entity;
      $label = $node ? $node->label() : $order_item->label();
      $line = $this->t('@facility — @date', [
        '@facility' => $label,
        '@date' => $row['start']->format('D, j M Y \a\t H:i'),
      ]);

      $markup = '<span>' . $line . '</span>';
      if ($show_cancel && $this->accessManager->checkNamedRoute('shh_cancellation_policy.cancel_booking', ['commerce_order_item' => $order_item->id()], $this->currentUser())) {
        $cancel_url = Url::fromRoute('shh_cancellation_policy.cancel_booking', ['commerce_order_item' => $order_item->id()]);
        $markup .= ' <a href="' . $cancel_url->toString() . '">' . $this->t('Cancel') . '</a>';
      }
      $items[] = ['#markup' => $markup];
    }

    $build['list'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $build;
  }

  /**
   * Builds the "active deposits" section.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $deposit_items
   *   The rider's "horse_deposit" order items.
   */
  protected function buildDepositsSection(array $deposit_items): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mb-8']],
    ];
    $build['heading'] = ['#markup' => '<h2>' . $this->t('Horse deposits') . '</h2>'];

    // Only currently-reserved deposits — a cancelled/refunded one has
    // nothing actionable left to show here.
    $active = array_filter($deposit_items, function (OrderItemInterface $order_item) {
      $variation = $order_item->getPurchasedEntity();
      return $variation instanceof ProductVariationInterface
        && $variation->hasField('field_sale_state')
        && $variation->get('field_sale_state')->value === 'reserved-deposit';
    });

    if (!$active) {
      $build['empty'] = ['#markup' => '<p>' . $this->t('None.') . '</p>'];
      return $build;
    }

    $items = [];
    foreach ($active as $order_item) {
      $variation = $order_item->getPurchasedEntity();
      $product = $variation instanceof ProductVariationInterface ? $variation->getProduct() : NULL;
      $label = $product ? $product->label() : $order_item->label();
      $markup = '<span>' . $this->t('@horse — deposit paid', ['@horse' => $label]) . '</span>';
      if ($this->accessManager->checkNamedRoute('shh_horse_deposit.cancel_deposit', ['commerce_order_item' => $order_item->id()], $this->currentUser())) {
        $cancel_url = Url::fromRoute('shh_horse_deposit.cancel_deposit', ['commerce_order_item' => $order_item->id()]);
        $markup .= ' <a href="' . $cancel_url->toString() . '">' . $this->t('Cancel deposit') . '</a>';
      }
      $items[] = ['#markup' => $markup];
    }

    $build['list'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $build;
  }

  /**
   * Builds the "facility credit balances" section.
   */
  protected function buildCreditsSection(int $uid): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mb-8']],
    ];
    $build['heading'] = ['#markup' => '<h2>' . $this->t('Facility credits') . '</h2>'];

    $storage = $this->entityTypeManager()->getStorage('shh_facility_credit');
    /** @var \Drupal\shh_facility_credits\Entity\FacilityCredit[] $credits */
    $credits = $storage->loadByProperties(['uid' => $uid]);

    if (!$credits) {
      $build['empty'] = ['#markup' => '<p>' . $this->t('None.') . '</p>'];
      return $build;
    }

    $items = [];
    foreach ($credits as $credit) {
      /** @var \Drupal\shh_facility_credits\Entity\FacilityCredit $credit */
      $facility = $credit->get('facility')->entity;
      if (!$facility) {
        continue;
      }
      $markup = '<span>' . $this->t('@facility: @remaining remaining', [
        '@facility' => $facility->label(),
        '@remaining' => $credit->getCreditsRemaining(),
      ]) . '</span>';
      $buy_more_url = Url::fromRoute('shh_facility_credits.buy_pack', ['node' => $facility->id()]);
      $markup .= ' <a href="' . $buy_more_url->toString() . '">' . $this->t('Buy more') . '</a>';
      $items[] = ['#markup' => $markup];
    }

    $build['list'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $build;
  }

}
