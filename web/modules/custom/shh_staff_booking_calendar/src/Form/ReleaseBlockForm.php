<?php

namespace Drupal\shh_staff_booking_calendar\Form;

use Drupal\bat_event\Entity\Event;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Confirm form: remove a staff block (delete the not_available event).
 *
 * Deliberately refuses anything that is not a `*_not_available` event —
 * customer bookings and cart holds have their own lifecycle flows
 * (0015 cancellation, cart expiry) and must not be deletable from the
 * staff calendar. Deleting the event is audited by 0002's booking log
 * (bat_event delete hook, actor classified staff by permission).
 */
class ReleaseBlockForm extends ConfirmFormBase {

  /**
   * The staff block being removed.
   */
  protected Event $event;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_staff_booking_calendar_release_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Event $bat_event = NULL) {
    $state = $bat_event?->get('event_state_reference')->entity;
    if (!$bat_event || !$state || !str_ends_with($state->getMachineName(), '_not_available')) {
      // Not a staff block: bookings/holds are not manageable here.
      throw new NotFoundHttpException();
    }
    $this->event = $bat_event;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $facility = $this->facilityLabel();
    return $this->t('Remove the staff block on %facility, @from – @until?', [
      '%facility' => $facility,
      '@from' => str_replace('T', ' ', substr($this->event->get('event_dates')->value, 0, 16)),
      '@until' => substr($this->event->get('event_dates')->end_value, 11, 5),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The time becomes bookable by riders again immediately.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('shh_staff_booking_calendar.calendar');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $facility = $this->facilityLabel();
    $window = str_replace('T', ' ', substr($this->event->get('event_dates')->value, 0, 16))
      . ' – ' . substr($this->event->get('event_dates')->end_value, 11, 5);
    $this->event->delete();

    $this->logger('shh_staff_booking_calendar')->notice('Staff block removed on %facility (@window) by uid @uid.', [
      '%facility' => $facility,
      '@window' => $window,
      '@uid' => $this->currentUser()->id(),
    ]);
    $this->messenger()->addStatus($this->t('The staff block on %facility (@window) has been removed.', [
      '%facility' => $facility,
      '@window' => $window,
    ]));
    $form_state->setRedirect('shh_staff_booking_calendar.calendar');
  }

  /**
   * The owning facility's label, via the unit→node resolution.
   */
  protected function facilityLabel(): string {
    $unit_id = $this->event->get('event_bat_unit_reference')->target_id;
    if ($unit_id) {
      $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->condition('type', 'bookable_facility')
        ->condition('field_availability_hourly', $unit_id)
        ->range(0, 1)
        ->accessCheck(FALSE)
        ->execute();
      if ($nids) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load(reset($nids));
        return $node->label();
      }
    }
    return (string) $this->t('Unknown facility');
  }

}
