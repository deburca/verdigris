<?php

namespace Drupal\shh_facility_credits\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Facility Credit Transaction entity: an audit log entry for
 * every credit grant (pack purchase) or redemption (booking).
 *
 * @ContentEntityType(
 *   id = "shh_facility_credit_transaction",
 *   label = @Translation("Facility Credit Transaction"),
 *   label_collection = @Translation("Facility Credit Transactions"),
 *   handlers = {
 *     "list_builder" = "Drupal\shh_facility_credits\ListBuilder\FacilityCreditTransactionListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shh_facility_credit_transaction",
 *   admin_permission = "administer facility credits",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/facility-credit-transactions",
 *   },
 * )
 */
class FacilityCreditTransaction extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['facility_credit'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Facility credit balance'))
      ->setSetting('target_type', 'shh_facility_credit')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'entity_reference_label'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['delta'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Change'))
      ->setDescription(t('Positive for a grant (pack purchase), negative for a redemption (booking).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'number_integer'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_item_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Related order item ID'))
      ->setDescription(t('The commerce_order_item that caused this transaction (the pack purchase for a grant, or the booking for a redemption).'))
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'number_integer'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['note'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Note'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'string'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'timestamp'])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
