<?php

namespace Drupal\shh_horse_deposit;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for the Deposit refund policy config entity.
 */
class DepositRefundPolicyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['refund_window_days'] = $this->t('Refund window (days)');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\shh_horse_deposit\Entity\DepositRefundPolicy $entity */
    $row['label'] = $entity->label();
    $row['refund_window_days'] = $entity->getRefundWindowDays();
    return $row + parent::buildRow($entity);
  }

}
