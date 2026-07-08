<?php

namespace Drupal\shh_horse_sale_state\Form;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\shh_horse_sale_state\RelistManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Staff action: relist a sold horse for sale.
 *
 * Mirrors shh_horse_deposit's ReleaseReservationForm (0036), but for the
 * `sold` state — and, per task 0037, relisting is deliberately decoupled
 * from refunding: this form identifies the originating sale order and its
 * payments so staff can see what sale they are undoing, then only flips
 * the sale state. All money handling stays in Commerce's own admin UI.
 */
class RelistSoldHorseForm extends ConfirmFormBase {

  /**
   * The horse product being relisted.
   */
  protected ProductInterface $product;

  public function __construct(
    protected RelistManager $relistManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_horse_sale_state.relist_manager'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_relist_sold_horse_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Relist %title for sale?', [
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

    if ($state !== 'sold') {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('This horse is not currently sold (state: %state), so there is nothing to relist.', [
          '%state' => $state ?? $this->t('unknown'),
        ]) . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $form['summary'] = [
      '#markup' => '<p>' . $this->t('The horse will be returned to <strong>available</strong> and can be sold or reserved again. <strong>No refund is attempted</strong>: the sale order and its payments are left exactly as they are — whether and how much to refund is a manual decision, made through the order’s own payment administration.') . '</p>',
    ];

    $order = $this->relistManager->findOriginatingSaleOrder($variation);
    if ($order) {
      $items = [];
      $items[] = $this->t('Order <a href=":url">#@number</a>, placed @date, total @total', [
        ':url' => Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $order->id()])->toString(),
        '@number' => $order->getOrderNumber() ?: $order->id(),
        '@date' => $this->dateFormatter->format($order->getPlacedTime(), 'short'),
        '@total' => (string) $order->getTotalPrice(),
      ]);
      /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      foreach ($payment_storage->loadMultipleByOrder($order) as $payment) {
        $items[] = $this->t('Payment @amount — state: @state', [
          '@amount' => (string) $payment->getAmount(),
          '@state' => $payment->getState()->getLabel(),
        ]);
      }
      $items[] = $this->t('<a href=":url">Manage this order’s payments</a> (refunds, voids) after relisting.', [
        ':url' => Url::fromRoute('entity.commerce_payment.collection', ['commerce_order' => $order->id()])->toString(),
      ]);
      $form['originating_sale'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('The sale being undone'),
        '#items' => $items,
      ];
    }
    else {
      $form['originating_sale'] = [
        '#markup' => '<p>' . $this->t('No placed sale order was found for this horse — the sold state appears to have been set out-of-band. Relisting will be logged as such.') . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variation = $this->product->getDefaultVariation();
    $result = $this->relistManager->relistSoldHorse($variation);

    if (!$result['relisted']) {
      $this->messenger()->addWarning($this->t('%title could not be relisted (it is not currently sold).', [
        '%title' => $this->product->label(),
      ]));
    }
    elseif ($result['order_id']) {
      $this->messenger()->addMessage($this->t('%title is available for sale again. Sale order <a href=":url">#@order</a> and its payments were not changed — handle any refund there.', [
        '%title' => $this->product->label(),
        ':url' => Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $result['order_id']])->toString(),
        '@order' => $result['order_id'],
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('%title is available for sale again. No placed sale order was found; the out-of-band state has been logged.', [
        '%title' => $this->product->label(),
      ]));
    }

    $form_state->setRedirectUrl($this->product->toUrl());
  }

}
