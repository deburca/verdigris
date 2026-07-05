<?php

namespace Drupal\shh_cancellation_policy;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for the Cancellation policy config entity.
 */
class CancellationPolicyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['refund_window_hours'] = $this->t('Refund window (hours)');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shh_cancellation_policy\Entity\CancellationPolicy $entity */
    $row['label'] = $entity->label();
    $row['refund_window_hours'] = $entity->getRefundWindowHours();
    return $row + parent::buildRow($entity);
  }

}
