<?php

namespace Drupal\shh_horse_deposit\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add/edit form for the Deposit refund policy config entity.
 */
class DepositRefundPolicyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\shh_horse_deposit\Entity\DepositRefundPolicy $policy */
    $policy = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $policy->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $policy->id(),
      '#machine_name' => [
        'exists' => '\Drupal\shh_horse_deposit\Entity\DepositRefundPolicy::load',
      ],
      '#disabled' => !$policy->isNew(),
    ];
    $form['refund_window_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Refund window (days since deposit paid)'),
      '#description' => $this->t('Within this window: full refund, horse released back to available. Outside this window: refund denied, but the horse is still released back to available either way — the seller should be able to re-list immediately if the buyer is not completing the purchase.'),
      '#default_value' => $policy->getRefundWindowDays(),
      '#min' => 0,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('Saved the %label deposit refund policy.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
