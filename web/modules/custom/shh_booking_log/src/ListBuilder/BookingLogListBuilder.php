<?php

namespace Drupal\shh_booking_log\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Admin listing for the booking lifecycle log, newest first.
 */
class BookingLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('id', 'DESC')
      ->pager($this->limit)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'created' => $this->t('Logged'),
      'facility' => $this->t('Facility'),
      'slot' => $this->t('Slot'),
      'transition' => $this->t('Transition'),
      'actor' => $this->t('Actor'),
      'order_id' => $this->t('Order'),
      'notification' => $this->t('Email sent'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $date_formatter = \Drupal::service('date.formatter');
    $facility = $entity->get('facility')->entity;
    $slot_start = $entity->get('slot_start')->value;
    $slot_end = $entity->get('slot_end')->value;
    $actor_uid = $entity->get('actor')->target_id;
    $actor = $entity->get('actor')->entity;

    // A NULL target_id means the actor was anonymised on account deletion
    // (task 0044). A non-zero uid whose user entity no longer exists is
    // treated the same way — should not normally occur but defended against.
    // System entries (actor_kind = 'system') store uid = 0 and get no
    // parenthetical suffix, matching the original behaviour.
    if ($actor_uid === NULL || ($actor_uid > 0 && $actor === NULL)) {
      $actor_suffix = ' (' . $this->t('Deleted user') . ')';
    }
    elseif ($actor !== NULL && $actor->id()) {
      $actor_suffix = ' (' . $actor->getAccountName() . ')';
    }
    else {
      $actor_suffix = '';
    }

    return [
      'created' => $date_formatter->format($entity->get('created')->value, 'short'),
      'facility' => $facility ? $facility->label() : '-',
      'slot' => $slot_start
        ? $date_formatter->format($slot_start, 'short') . ' – ' . $date_formatter->format($slot_end, 'custom', 'H:i')
        : '-',
      'transition' => ($entity->get('state_from')->value ?: '(new)') . ' → ' . $entity->get('state_to')->value,
      'actor' => $entity->get('actor_kind')->value . $actor_suffix,
      'order_id' => $entity->get('order_id')->value ?: '-',
      'notification' => $entity->get('notification')->value ?: '-',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Append-only log: no per-row operations at all.
   */
  public function getDefaultOperations(EntityInterface $entity) {
    return [];
  }

}
