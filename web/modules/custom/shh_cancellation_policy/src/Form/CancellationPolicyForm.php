<?php

namespace Drupal\shh_cancellation_policy\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add/edit form for the Cancellation policy config entity.
 */
class CancellationPolicyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\shh_cancellation_policy\Entity\CancellationPolicy $policy */
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
        'exists' => '\Drupal\shh_cancellation_policy\Entity\CancellationPolicy::load',
      ],
      '#disabled' => !$policy->isNew(),
    ];
    $form['refund_window_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Refund window (hours before booking start)'),
      '#description' => $this->t('Outside this window: cancellation gets a full refund and the slot is released. Inside this window: cancellation is denied a refund, and the slot is <strong>not</strong> released (worth confirming this specific "no release either" rule matches business intent before relying on it).'),
      '#default_value' => $policy->getRefundWindowHours(),
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
    $this->messenger()->addMessage($this->t('Saved the %label cancellation policy.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
