<?php

namespace Drupal\shh_horse_deposit\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\shh_horse_deposit\DepositManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * "Pay a deposit" form for a horse product — an alternative to outright
 * purchase via the standard Commerce AddToCartForm.
 *
 * Mirrors the pattern bee.module's AddReservationForm already established
 * on this platform for a purchase flow that isn't a plain product add-to-
 * cart: its own form, its own order item type, an explicit (here,
 * percentage-computed) unit price rather than the variation's own price.
 */
class PayDepositForm extends ConfirmFormBase {

  protected ProductInterface $product;

  public function __construct(
    protected DepositManager $depositManager,
    protected CartManagerInterface $cartManager,
    protected CartProviderInterface $cartProvider,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_horse_deposit.manager'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_pay_deposit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Pay a deposit to reserve %title?', ['%title' => $this->product->label()]);
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
    if (!$variation || !$this->depositManager->isDepositable($variation)) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('This horse is not currently available for a deposit reservation.') . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $deposit_amount = $this->depositManager->computeDepositAmount($variation);
    $form['summary'] = [
      '#markup' => '<p>' . $this->t('Deposit amount: <strong>@amount</strong> (full price: @price). The remaining balance is arranged directly with Stutteri Hestehøj once your deposit is confirmed.', [
        '@amount' => $deposit_amount,
        '@price' => $variation->getPrice(),
      ]) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variation = $this->product->getDefaultVariation();
    $deposit_amount = $this->depositManager->computeDepositAmount($variation);

    $stores = $this->product->getStores();
    $store = reset($stores);

    $order_item = OrderItem::create([
      'title' => $this->t('Deposit — @title', ['@title' => $this->product->label()])->render(),
      'type' => 'horse_deposit',
      'purchased_entity' => $variation->id(),
      'quantity' => 1,
      'unit_price' => $deposit_amount,
    ]);
    $order_item->setUnitPrice($deposit_amount, TRUE);
    $order_item->save();

    $cart = $this->cartProvider->getCart('horse_sale', $store) ?: $this->cartProvider->createCart('horse_sale', $store);
    $this->cartManager->addOrderItem($cart, $order_item);

    $form_state->setRedirectUrl(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]));
  }

}
