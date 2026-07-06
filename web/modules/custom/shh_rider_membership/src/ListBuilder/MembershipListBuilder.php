<?php

namespace Drupal\shh_rider_membership\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Rider Membership records (staff review/approval queue).
 */
class MembershipListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['rider'] = $this->t('Rider');
    $header['status'] = $this->t('Status');
    $header['submitted'] = $this->t('Submitted');
    $header['approved'] = $this->t('Approved');
    $header['expires'] = $this->t('Expires');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shh_rider_membership\Entity\Membership $entity */
    $rider = $entity->get('uid')->entity;
    $row['rider'] = $rider ? $rider->label() : $this->t('(deleted user)');
    $row['status'] = $entity->get('status')->value;
    $row['submitted'] = $this->formatTimestamp($entity->get('created')->value);
    $row['approved'] = $this->formatTimestamp($entity->get('approved')->value);
    $row['expires'] = $this->formatTimestamp($entity->get('expires')->value);
    return $row + parent::buildRow($entity);
  }

  /**
   * Formats a nullable base-field timestamp for the list table.
   */
  protected function formatTimestamp($timestamp) {
    if (empty($timestamp)) {
      return $this->t('—');
    }
    return \Drupal::service('date.formatter')->format((int) $timestamp, 'short');
  }

}
