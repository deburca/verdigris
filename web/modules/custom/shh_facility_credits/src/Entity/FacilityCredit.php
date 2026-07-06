<?php

namespace Drupal\shh_facility_credits\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Facility Credit entity: a rider's prepaid credit balance for
 * one specific facility.
 *
 * One entity per (rider, facility) pair — multiple credit pack purchases
 * for the same facility top up the same balance rather than creating
 * separate records, so staff only ever see one row per rider per facility.
 * No expiry (confirmed with the client) — credits remain valid
 * indefinitely once purchased.
 *
 * Uses plain base fields (like BAT's own Booking/Event/Unit entities),
 * deliberately not the Field API — this project has hit repeated Field API
 * config-schema/cache pitfalls this session (0011, 0016, 0017), and a
 * simple ledger entity like this doesn't need Field UI manageability.
 *
 * @ContentEntityType(
 *   id = "shh_facility_credit",
 *   label = @Translation("Facility Credit"),
 *   label_collection = @Translation("Facility Credits"),
 *   handlers = {
 *     "list_builder" = "Drupal\shh_facility_credits\ListBuilder\FacilityCreditListBuilder",
 *     "form" = {
 *       "default" = "Drupal\shh_facility_credits\Form\FacilityCreditForm",
 *       "edit" = "Drupal\shh_facility_credits\Form\FacilityCreditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shh_facility_credit",
 *   admin_permission = "administer facility credits",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/facility-credits",
 *     "edit-form" = "/admin/commerce/facility-credits/{shh_facility_credit}/edit",
 *     "delete-form" = "/admin/commerce/facility-credits/{shh_facility_credit}/delete",
 *   },
 * )
 */
class FacilityCredit extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Finds (or indicates none exists for) a rider's balance on a facility.
   */
  public static function loadForRiderAndFacility(int $uid, int $facility_nid): ?self {
    $storage = \Drupal::entityTypeManager()->getStorage('shh_facility_credit');
    $results = $storage->loadByProperties([
      'uid' => $uid,
      'facility' => $facility_nid,
    ]);
    $result = reset($results);
    return $result ?: NULL;
  }

  public function getRiderId(): int {
    return (int) $this->get('uid')->target_id;
  }

  public function getFacilityId(): int {
    return (int) $this->get('facility')->target_id;
  }

  public function getCreditsRemaining(): int {
    return (int) $this->get('credits_remaining')->value;
  }

  public function getCreditsTotal(): int {
    return (int) $this->get('credits_total')->value;
  }

  public function grant(int $amount): void {
    $this->set('credits_remaining', $this->getCreditsRemaining() + $amount);
    $this->set('credits_total', $this->getCreditsTotal() + $amount);
  }

  /**
   * Attempts to spend one credit. Returns FALSE if none remain.
   */
  public function redeemOne(): bool {
    if ($this->getCreditsRemaining() < 1) {
      return FALSE;
    }
    $this->set('credits_remaining', $this->getCreditsRemaining() - 1);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rider'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'author'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['facility'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Facility'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['bookable_facility' => 'bookable_facility']])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'entity_reference_label'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['credits_total'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total credits ever granted'))
      ->setDescription(t('Lifetime total across all credit pack purchases for this rider+facility — audit/history only, not the spendable balance.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'number_integer'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['credits_remaining'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Credits remaining'))
      ->setDescription(t('The spendable balance. No expiry — valid indefinitely once purchased.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'number_integer'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
