<?php

namespace Drupal\shh_horse_deposit\Form;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shh_horse_deposit\DepositManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Staff action: release a horse from its deposit reservation.
 *
 * The counterpart to the rider-facing CancelDepositForm — that one is
 * self-service (needs a placed order the rider owns); this one is for
 * staff correcting or clearing a reservation regardless of how the
 * horse ended up `reserved-deposit`, including an unpaid deposit or an
 * orphaned state. Delegates to DepositManager::releaseReservation(),
 * which refunds through a real placed order when one exists and
 * otherwise just frees the horse.
 */
class ReleaseReservationForm extends ConfirmFormBase {

  /**
   * The horse product whose reservation is being released.
   */
  protected ProductInterface $product;

  public function __construct(
    protected DepositManager $depositManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_horse_deposit.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_release_reservation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Release %title from its deposit reservation?', [
      '%title' => $this->product->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->product->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $commerce_product = NULL) {
    $this->product = $commerce_product;
    $form = parent::buildForm($form, $form_state);

    $variation = $this->product->getDefaultVariation();
    $state = $variation && $variation->hasField('field_sale_state')
      ? $variation->get('field_sale_state')->value
      : NULL;

    if ($state !== 'reserved-deposit') {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('This horse is not currently reserved by a deposit (state: %state), so there is nothing to release.', [
          '%state' => $state ?? $this->t('unknown'),
        ]) . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $form['summary'] = [
      '#markup' => '<p>' . $this->t('The horse will be returned to <strong>available</strong> and can be sold or re-reserved again. If a placed deposit order still covers it, that deposit is refunded when it is inside its refund window — an unpaid deposit refunds nothing.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variation = $this->product->getDefaultVariation();
    $result = $this->depositManager->releaseReservation($variation);

    if (!$result['released']) {
      $this->messenger()->addWarning($this->t('%title could not be released (it is not currently deposit-reserved).', [
        '%title' => $this->product->label(),
      ]));
    }
    elseif ($result['refunded']) {
      $this->messenger()->addMessage($this->t('%title has been released and is available again; the deposit was refunded.', [
        '%title' => $this->product->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('%title has been released and is available again. No deposit was refunded (none was captured, or it was outside the refund window).', [
        '%title' => $this->product->label(),
      ]));
    }

    $form_state->setRedirectUrl($this->product->toUrl());
  }

}
