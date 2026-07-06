<?php

namespace Drupal\shh_facility_credits\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Facility Credit Transactions (audit trail).
 */
class FacilityCreditTransactionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['facility_credit'] = $this->t('Facility credit');
    $header['delta'] = $this->t('Change');
    $header['order_item_id'] = $this->t('Order item');
    $header['note'] = $this->t('Note');
    $header['created'] = $this->t('Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shh_facility_credits\Entity\FacilityCreditTransaction $entity */
    $facility_credit = $entity->get('facility_credit')->entity;
    $row['facility_credit'] = $facility_credit ? $facility_credit->label() : $this->t('(deleted)');
    $row['delta'] = $entity->get('delta')->value;
    $row['order_item_id'] = $entity->get('order_item_id')->value;
    $row['note'] = $entity->get('note')->value;
    $row['created'] = \Drupal::service('date.formatter')->format((int) $entity->get('created')->value, 'short');
    return $row + parent::buildRow($entity);
  }

}
