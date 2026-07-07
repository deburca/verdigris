<?php

namespace Drupal\shh_booking_log\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * One booking lifecycle transition: who moved which slot to what state.
 *
 * Append-only: entries are created by BookingLifecycleLogger reacting to
 * bat_event saves and are never edited afterwards — there are deliberately
 * no add/edit form handlers, only the admin collection listing. The BAT
 * event is recorded by plain integer id (not an entity reference) because
 * events can be deleted (that deletion is itself a logged transition) and
 * the trail must outlive them.
 *
 * Uses plain base fields, not the Field API — the platform's established
 * pattern for ledger/record-style data (shh_facility_credits'
 * FacilityCredit, shh_rider_membership's Membership).
 *
 * @ContentEntityType(
 *   id = "shh_booking_log",
 *   label = @Translation("Booking log entry"),
 *   label_collection = @Translation("Booking log"),
 *   handlers = {
 *     "list_builder" = "Drupal\shh_booking_log\ListBuilder\BookingLogListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shh_booking_log",
 *   admin_permission = "access booking log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/reports/booking-log",
 *   },
 * )
 */
class BookingLogEntry extends ContentEntityBase {

  const ACTOR_CUSTOMER = 'customer';
  const ACTOR_STAFF = 'staff';
  const ACTOR_SYSTEM = 'system';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Logged'));

    $fields['event_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('BAT event ID'));

    $fields['facility'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Facility'))
      ->setSetting('target_type', 'node');

    $fields['slot_start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Slot start'));

    $fields['slot_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Slot end'));

    $fields['state_from'] = BaseFieldDefinition::create('string')
      ->setLabel(t('From state'))
      ->setDescription(t('Empty for newly created events.'));

    $fields['state_to'] = BaseFieldDefinition::create('string')
      ->setLabel(t('To state'))
      ->setDescription(t('"deleted" when the BAT event itself was removed.'));

    $fields['actor'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor'))
      ->setSetting('target_type', 'user');

    $fields['actor_kind'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Actor kind'))
      ->setSetting('allowed_values', [
        self::ACTOR_CUSTOMER => 'Customer',
        self::ACTOR_STAFF => 'Staff',
        self::ACTOR_SYSTEM => 'System',
      ]);

    $fields['order_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('Empty for events with no associated order, e.g. staff-created availability blocks.'));

    $fields['notification'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Notification sent'))
      ->setDescription(t('The mail key sent to the rider for this transition, if any.'));

    return $fields;
  }

}
