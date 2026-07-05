<?php

namespace Drupal\shh_cancellation_policy\Form;

use Drupal\bat_booking\Entity\Booking;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Self-service booking cancellation, enforcing the facility's
 * cancellation_policy.
 *
 * Implements docs/project-management/decisions/0015-cancellation-refund-policy.md's
 * "Commerce order-cancel workflow that checks policy + time-to-slot before
 * authorizing refund" and "cancellation reverts booked → available only if
 * policy check passes."
 */
class CancelBookingForm extends ConfirmFormBase {

  /**
   * The order item being cancelled.
   */
  protected OrderItemInterface $orderItem;

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_cancel_booking_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Cancel this booking?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * Loads the facility node + cancellation policy for the given order item.
   *
   * @return array{0: ?NodeInterface, 1: ?\Drupal\shh_cancellation_policy\Entity\CancellationPolicy}
   */
  protected function loadNodeAndPolicy(OrderItemInterface $order_item): array {
    $node = $order_item->get('field_node')->entity;
    if (!$node instanceof NodeInterface) {
      return [NULL, NULL];
    }
    $policy = NULL;
    if ($node->hasField('field_cancellation_policy') && !$node->get('field_cancellation_policy')->isEmpty()) {
      $policy = $node->get('field_cancellation_policy')->entity;
    }
    return [$node, $policy];
  }

  /**
   * Gets the earliest still-booked BAT event's start time for this booking.
   */
  protected function getEarliestBookedEventStart(Booking $booking): ?\DateTime {
    $earliest = NULL;
    foreach ($booking->get('booking_event_reference')->referencedEntities() as $event) {
      $state = $event->get('event_state_reference')->entity;
      if (!$state || $state->getMachineName() !== 'bee_hourly_booked') {
        continue;
      }
      $start = $event->getStartDate();
      if ($earliest === NULL || $start < $earliest) {
        $earliest = $start;
      }
    }
    return $earliest;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $commerce_order_item = NULL) {
    $this->orderItem = $commerce_order_item;
    $form = parent::buildForm($form, $form_state);

    $booking = $this->orderItem->get('field_booking')->entity;
    [$node, $policy] = $this->loadNodeAndPolicy($this->orderItem);

    if (!$booking instanceof Booking || !$node || !$policy) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('This facility has no cancellation policy configured. Please contact staff directly to cancel this booking.') . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $earliest_start = $this->getEarliestBookedEventStart($booking);
    if (!$earliest_start) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('This booking has no active (booked) time slots to cancel.') . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $hours_until_start = ($earliest_start->getTimestamp() - \Drupal::time()->getRequestTime()) / 3600;
    $window = $policy->getRefundWindowHours();
    $refund_eligible = $hours_until_start >= $window;

    $form['summary'] = [
      '#markup' => '<p>' . ($refund_eligible
        ? $this->t('This booking starts in @hours hours, outside the %policy policy\'s @window-hour window — cancelling now will release the slot and authorize a full refund.', [
          '@hours' => round($hours_until_start, 1),
          '%policy' => $policy->label(),
          '@window' => $window,
        ])
        : $this->t('This booking starts in @hours hours, inside the %policy policy\'s @window-hour window — cancelling now will <strong>not</strong> be refunded, and the slot will remain booked.', [
          '@hours' => round($hours_until_start, 1),
          '%policy' => $policy->label(),
          '@window' => $window,
        ])
      ) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = \Drupal::service('shh_cancellation_policy.manager')->cancelBooking($this->orderItem);

    if ($result['authorized']) {
      $this->messenger()->addMessage($this->t('Your booking has been cancelled and refunded. The slot has been released.'));
    }
    else {
      // Per the cancellation policy's implementation note: inside the
      // no-refund window, the cancellation request is denied outright — no
      // refund, no state change, the slot stays booked exactly as before.
      // This "flat denial" (rather than a cancelled-but-unrefunded limbo
      // state) is a deliberate simplification worth confirming matches
      // business intent.
      $this->messenger()->addWarning($this->t('This booking is inside the cancellation policy\'s no-refund window, so it cannot be self-service cancelled. Contact staff directly if you still need to cancel.'));
    }

    $form_state->setRedirectUrl(Url::fromRoute('<front>'));
  }

}
