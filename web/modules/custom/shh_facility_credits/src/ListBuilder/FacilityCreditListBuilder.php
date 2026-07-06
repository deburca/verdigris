<?php

namespace Drupal\shh_facility_credits\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Facility Credit balances (staff admin visibility).
 */
class FacilityCreditListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['rider'] = $this->t('Rider');
    $header['facility'] = $this->t('Facility');
    $header['remaining'] = $this->t('Remaining');
    $header['total'] = $this->t('Total ever granted');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shh_facility_credits\Entity\FacilityCredit $entity */
    $rider = $entity->get('uid')->entity;
    $facility = $entity->get('facility')->entity;
    $row['rider'] = $rider ? $rider->label() : $this->t('(deleted user)');
    $row['facility'] = $facility ? $facility->label() : $this->t('(deleted facility)');
    $row['remaining'] = $entity->getCreditsRemaining();
    $row['total'] = $entity->getCreditsTotal();
    return $row + parent::buildRow($entity);
  }

}
