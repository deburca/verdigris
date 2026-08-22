<?php

namespace Drupal\shh_facility_credits\EventSubscriber;

use Drupal\shh_facility_credits\FacilityCreditManager;
use Drupal\shh_facility_credits\FacilityPricingHelper;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Grants facility credits when a credit pack order is placed.
 */
class CreditPackCheckoutCompletionSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected FacilityCreditManager $creditManager,
    protected FacilityPricingHelper $pricingHelper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return ['commerce_order.place.pre_transition' => 'grantCredits'];
  }

  /**
   * Grants credits for each 'facility_credit_pack' order item.
   */
  public function grantCredits(WorkflowTransitionEvent $event): void {
    $order = $event->getEntity();
    foreach ($order->getItems() as $order_item) {
      if ($order_item->bundle() !== 'facility_credit_pack') {
        continue;
      }
      $facility_nid = $order_item->getData('shh_facility_credit_pack_node');
      if (!$facility_nid) {
        continue;
      }
      $uid = (int) $order->getCustomerId();
      if (!$uid) {
        // Credit packs require a logged-in buyer (enforced in
        // BuyCreditPackForm) — defensive, shouldn't happen.
        continue;
      }
      $this->creditManager->grantCredits($uid, (int) $facility_nid, $this->pricingHelper->getPackSize(), $order_item);
    }
  }

}
