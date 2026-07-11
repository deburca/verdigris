<?php

namespace Drupal\shh_staff_booking_calendar\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Staff form: block a facility's time without touching Commerce.
 *
 * Creates orderless `bee_hourly_not_available` BAT events directly (one
 * per unit of the facility, so multi-unit facilities block fully) —
 * task 0004's "admin-sourced event" path. Times are offered in the
 * platform's 30-minute steps over the 08:00–20:00 booking day (0016's
 * slot rules) purely for UI tidiness; the created event is an ordinary
 * BAT event. Windows already holding customer bookings or cart holds
 * are rejected — cancelling a customer's booking has its own
 * policy-aware flow (0015) and is deliberately not doable from here.
 */
class BlockSlotForm extends FormBase {

  const NOT_AVAILABLE_STATE = 'bee_hourly_not_available';
  const DAY_START = 8;
  const DAY_END = 20;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_staff_booking_calendar_block_slot';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'bookable_facility',
      'status' => 1,
    ]);
    foreach ($nodes as $node) {
      if ($node->hasField('field_availability_hourly') && !$node->get('field_availability_hourly')->isEmpty()) {
        $options[$node->id()] = $node->label();
      }
    }

    $form['facility'] = [
      '#type' => 'select',
      '#title' => $this->t('Facility'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
      '#required' => TRUE,
      '#default_value' => date('Y-m-d'),
    ];

    $times = [];
    for ($hour = self::DAY_START; $hour <= self::DAY_END; $hour++) {
      foreach (['00', '30'] as $minutes) {
        if ($hour === self::DAY_END && $minutes === '30') {
          continue;
        }
        $time = sprintf('%02d:%s', $hour, $minutes);
        $times[$time] = $time;
      }
    }
    $form['start'] = [
      '#type' => 'select',
      '#title' => $this->t('From'),
      '#options' => array_slice($times, 0, -1, TRUE),
      '#required' => TRUE,
    ];
    $form['end'] = [
      '#type' => 'select',
      '#title' => $this->t('Until'),
      '#options' => array_slice($times, 1, NULL, TRUE),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Block this time'),
        '#button_type' => 'primary',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $start = $this->window($form_state)['start'];
    $end = $this->window($form_state)['end'];
    if ($end <= $start) {
      $form_state->setErrorByName('end', $this->t('The end time must be after the start time.'));
      return;
    }

    $node = $this->entityTypeManager->getStorage('node')->load($form_state->getValue('facility'));
    if (!$node) {
      $form_state->setErrorByName('facility', $this->t('Unknown facility.'));
      return;
    }

    // Reject windows already occupied by anything other than plain
    // availability: an overlapping customer booking or hold must be
    // resolved through its own flow first, and a fully overlapping
    // staff block would just duplicate rows.
    $unit_ids = array_column($node->get('field_availability_hourly')->getValue(), 'target_id');
    $storage = $this->entityTypeManager->getStorage('bat_event');
    $ids = $storage->getQuery()
      ->condition('type', 'availability_hourly')
      ->condition('event_bat_unit_reference', $unit_ids, 'IN')
      ->condition('event_dates.value', $end->format('Y-m-d\TH:i:s'), '<')
      ->condition('event_dates.end_value', $start->format('Y-m-d\TH:i:s'), '>')
      ->accessCheck(FALSE)
      ->execute();
    foreach ($storage->loadMultiple($ids) as $event) {
      $state = $event->get('event_state_reference')->entity;
      $machine_name = $state ? $state->getMachineName() : '';
      if (str_ends_with($machine_name, '_available')) {
        continue;
      }
      $kind = str_ends_with($machine_name, '_not_available')
        ? $this->t('an existing staff block')
        : (str_ends_with($machine_name, '_on_hold') ? $this->t('a cart hold') : $this->t('a customer booking'));
      $form_state->setErrorByName('start', $this->t('@facility already has @kind between @from and @until — resolve that first.', [
        '@facility' => $node->label(),
        '@kind' => $kind,
        '@from' => substr($event->get('event_dates')->value, 11, 5),
        '@until' => substr($event->get('event_dates')->end_value, 11, 5),
      ]));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $window = $this->window($form_state);
    $node = $this->entityTypeManager->getStorage('node')->load($form_state->getValue('facility'));
    $not_available = bat_event_load_state_by_machine_name(self::NOT_AVAILABLE_STATE);

    $created = [];
    foreach ($node->get('field_availability_hourly')->getValue() as $value) {
      $event = bat_event_create(['type' => 'availability_hourly']);
      $event->set('event_dates', [
        'value' => $window['start']->format('Y-m-d\TH:i:00'),
        'end_value' => $window['end']->format('Y-m-d\TH:i:00'),
      ]);
      $event->set('event_state_reference', $not_available->id());
      $event->set('event_bat_unit_reference', $value['target_id']);
      $event->save();
      $created[] = $event->id();
    }

    $this->logger('shh_staff_booking_calendar')->notice('Staff block created on %facility (@from – @until), event(s) [@ids], by uid @uid.', [
      '%facility' => $node->label(),
      '@from' => $window['start']->format('Y-m-d H:i'),
      '@until' => $window['end']->format('Y-m-d H:i'),
      '@ids' => implode(',', $created),
      '@uid' => $this->currentUser()->id(),
    ]);
    $this->messenger()->addStatus($this->t('%facility is now blocked from @from until @until.', [
      '%facility' => $node->label(),
      '@from' => $window['start']->format('H:i'),
      '@until' => $window['end']->format('H:i'),
    ]));
    $form_state->setRedirect('shh_staff_booking_calendar.calendar');
  }

  /**
   * The submitted window as DateTimeImmutable start/end.
   */
  protected function window(FormStateInterface $form_state): array {
    $date = $form_state->getValue('date');
    return [
      'start' => new \DateTimeImmutable($date . ' ' . $form_state->getValue('start')),
      'end' => new \DateTimeImmutable($date . ' ' . $form_state->getValue('end')),
    ];
  }

}
