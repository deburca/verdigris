<?php

namespace Drupal\shh_facility_credits\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
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

  const PACK_SIZE = 10;
  const DISCOUNT_PERCENTAGE = 75;

  protected NodeInterface $node;

  public function __construct(
    protected CartManagerInterface $cartManager,
    protected CartProviderInterface $cartProvider,
    protected AccountInterface $currentUser,
    protected RounderInterface $rounder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('current_user'),
      $container->get('commerce_price.rounder'),
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
      '%count' => self::PACK_SIZE,
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
   * Computes the per-slot price for this facility (per-minute rate ×
   * fixed slot duration — see task 0016's facility fields), or NULL if the
   * facility isn't set up for fixed-length slots.
   */
  protected function getSlotPrice(): ?Price {
    if (!$this->node->hasField('field_slot_duration_minutes') || $this->node->get('field_slot_duration_minutes')->isEmpty()) {
      return NULL;
    }
    if (!$this->node->hasField('field_price') || $this->node->get('field_price')->isEmpty()) {
      return NULL;
    }
    $slot_minutes = (int) $this->node->get('field_slot_duration_minutes')->value;
    $price_item = $this->node->get('field_price')->first();
    $per_minute = $price_item->number;
    $currency_code = $price_item->currency_code;
    $slot_price_number = bcmul((string) $per_minute, (string) $slot_minutes, 6);
    // Round here — the per-minute rate is stored truncated to 6 decimals
    // (e.g. Oval Track's 1.666666, not 1.666667), so multiplying back out
    // without rounding produces 49.99998 DKK instead of exactly 50.00. The
    // actual booking flow (bee_get_unit_price()) rounds its final result
    // too; this form needs to do the same for its own price preview/total.
    return $this->rounder->round(new Price($slot_price_number, $currency_code));
  }

  protected function getPackPrice(Price $slot_price): Price {
    $full_price = $slot_price->multiply((string) self::PACK_SIZE);
    $multiplier = bcdiv((string) (100 - self::DISCOUNT_PERCENTAGE), '100', 6);
    return $this->rounder->round($full_price->multiply($multiplier));
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

    $slot_price = $this->getSlotPrice();
    if (!$slot_price || !$this->node->hasField('field_product') || $this->node->get('field_product')->isEmpty()) {
      $form['warning'] = [
        '#markup' => '<p>' . $this->t('Credit packs are not available for this facility.') . '</p>',
      ];
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $pack_price = $this->getPackPrice($slot_price);
    $full_price = $slot_price->multiply((string) self::PACK_SIZE);
    $form['summary'] = [
      '#markup' => '<p>' . $this->t('@count reservations for %title, normally @full, now @pack (@discount%% off). No expiry — use them anytime this season.', [
        '@count' => self::PACK_SIZE,
        '%title' => $this->node->label(),
        '@full' => $full_price,
        '@pack' => $pack_price,
        '@discount' => self::DISCOUNT_PERCENTAGE,
      ]) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $slot_price = $this->getSlotPrice();
    $pack_price = $this->getPackPrice($slot_price);

    $product = $this->node->get('field_product')->entity;
    $stores = $product->getStores();
    $store = reset($stores);
    $variations = $product->getVariations();
    $variation = reset($variations);

    $order_item = OrderItem::create([
      'title' => $this->t('@count-session credit pack — @title', [
        '@count' => self::PACK_SIZE,
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
