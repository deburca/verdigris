<?php

namespace Drupal\shh_horse_deposit\Form;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Self-service deposit cancellation, enforcing deposit_refund_policy.
 *
 * Unlike a facility booking cancellation (0015), the horse is *always*
 * released back to available on cancellation — only whether the deposit
 * itself is refunded depends on the policy window. See
 * \Drupal\shh_horse_deposit\DepositManager::cancelDeposit().
 */
class CancelDepositForm extends ConfirmFormBase {

  protected OrderItemInterface $orderItem;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_cancel_deposit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Cancel this deposit reservation?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $commerce_order_item = NULL) {
    $this->orderItem = $commerce_order_item;
    $form = parent::buildForm($form, $form_state);

    $variation = $this->orderItem->getPurchasedEntity();
    $policy = ($variation && $variation->hasField('field_deposit_policy') && !$variation->get('field_deposit_policy')->isEmpty())
      ? $variation->get('field_deposit_policy')->entity
      : NULL;

    if (!$policy) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('This horse has no deposit refund policy configured. The reservation will be released, but please contact staff directly regarding any refund.') . '</p>',
      ];
      return $form;
    }

    $order = $this->orderItem->getOrder();
    $days_since = (\Drupal::time()->getRequestTime() - $order->getPlacedTime()) / 86400;
    $refund_eligible = $days_since <= $policy->getRefundWindowDays();

    $form['summary'] = [
      '#markup' => '<p>' . ($refund_eligible
        ? $this->t('It has been @days days since your deposit, within the %policy policy\'s @window-day window — cancelling now will release the horse and refund your deposit in full.', [
          '@days' => round($days_since, 1),
          '%policy' => $policy->label(),
          '@window' => $policy->getRefundWindowDays(),
        ])
        : $this->t('It has been @days days since your deposit, outside the %policy policy\'s @window-day window — cancelling now will release the horse, but your deposit will <strong>not</strong> be refunded.', [
          '@days' => round($days_since, 1),
          '%policy' => $policy->label(),
          '@window' => $policy->getRefundWindowDays(),
        ])
      ) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = \Drupal::service('shh_horse_deposit.manager')->cancelDeposit($this->orderItem);

    if (!$result['released']) {
      $this->messenger()->addWarning($this->t('This deposit could not be cancelled automatically. Please contact staff.'));
    }
    elseif ($result['refunded']) {
      $this->messenger()->addMessage($this->t('Your deposit reservation has been cancelled and refunded. The horse is available again.'));
    }
    else {
      $this->messenger()->addWarning($this->t('Your deposit reservation has been cancelled and the horse released, but per the deposit refund policy your deposit was not refunded.'));
    }

    $form_state->setRedirectUrl(Url::fromRoute('<front>'));
  }

}
