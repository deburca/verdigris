<?php

namespace Drupal\shh_facility_credits\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\shh_facility_credits\FacilityPricingHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * "Buy a credit pack" form for a Bookable Facility — a prepaid bundle of N
 * reservations at a discount, redeemed one at a time later (see
 * docs/project-management/tasks/0018-facility-credit-packs.md).
 *
 * Same pattern as PayDepositForm (shh_horse_deposit) and the reservation
 * flow itself: a dedicated form with an explicit computed price, not the
 * generic Commerce AddToCartForm.
 */
class BuyCreditPackForm extends ConfirmFormBase {

  protected NodeInterface $node;

  public function __construct(
    protected CartManagerInterface $cartManager,
    protected CartProviderInterface $cartProvider,
    protected AccountInterface $currentUser,
    protected FacilityPricingHelper $pricingHelper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('current_user'),
      $container->get('shh_facility_credits.pricing_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shh_buy_credit_pack_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Buy a %count-session credit pack for %title?', [
      '%count' => $this->pricingHelper->getPackSize(),
      '%title' => $this->node->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->node->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $this->node = $node;
    $form = parent::buildForm($form, $form_state);

    if ($this->currentUser->isAnonymous()) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('Please log in to buy a credit pack.') . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $slot_price = $this->pricingHelper->getSlotPrice($this->node);
    if (!$slot_price || !$this->node->hasField('field_product') || $this->node->get('field_product')->isEmpty()) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('Credit packs are not available for this facility.') . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $pack_price = $this->pricingHelper->getPackPrice($slot_price);
    $full_price = $slot_price->multiply((string) $this->pricingHelper->getPackSize());
    $form['summary'] = [
      '#markup' => '<p>' . $this->t('@count reservations for %title, normally @full, now @pack (@discount%% off). No expiry — use them anytime this season.', [
        '@count' => $this->pricingHelper->getPackSize(),
        '%title' => $this->node->label(),
        '@full' => $full_price,
        '@pack' => $pack_price,
        '@discount' => $this->pricingHelper->getDiscountPercentage(),
      ]) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $slot_price = $this->pricingHelper->getSlotPrice($this->node);
    $pack_price = $this->pricingHelper->getPackPrice($slot_price);

    $product = $this->node->get('field_product')->entity;
    $stores = $product->getStores();
    $store = reset($stores);
    $variations = $product->getVariations();
    $variation = reset($variations);

    $order_item = OrderItem::create([
      'title' => $this->t('@count-session credit pack — @title', [
        '@count' => $this->pricingHelper->getPackSize(),
        '@title' => $this->node->label(),
      ])->render(),
      'type' => 'facility_credit_pack',
      'purchased_entity' => $variation->id(),
      'quantity' => 1,
      'unit_price' => $pack_price,
    ]);
    // Use the order item's built-in 'data' map field to record which
    // facility this pack is for — no need for a dedicated Field API field
    // (this project has hit repeated Field API config-schema/cache
    // pitfalls this session; 'data' is a plain base field on every
    // commerce_order_item bundle already).
    $order_item->setData('shh_facility_credit_pack_node', $this->node->id());
    $order_item->setUnitPrice($pack_price, TRUE);
    $order_item->save();

    $cart = $this->cartProvider->getCart('default', $store) ?: $this->cartProvider->createCart('default', $store);
    $this->cartManager->addOrderItem($cart, $order_item);

    $form_state->setRedirectUrl(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]));
  }

}
