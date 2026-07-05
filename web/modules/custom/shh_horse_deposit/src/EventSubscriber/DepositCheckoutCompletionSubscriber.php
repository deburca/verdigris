<?php

namespace Drupal\shh_horse_deposit\EventSubscriber;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\shh_horse_deposit\DepositManager;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Marks a horse variation reserved-deposit when its deposit order is placed.
 */
class DepositCheckoutCompletionSubscriber implements EventSubscriberInterface {

  public function __construct(protected DepositManager $depositManager) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return ['commerce_order.place.pre_transition' => 'markDeposits'];
  }

  /**
   * Marks each horse_deposit order item's variation reserved-deposit.
   */
  public function markDeposits(WorkflowTransitionEvent $event): void {
    $order = $event->getEntity();
    foreach ($order->getItems() as $order_item) {
      if ($order_item->bundle() !== 'horse_deposit') {
        continue;
      }
      $variation = $order_item->getPurchasedEntity();
      if ($variation instanceof ProductVariationInterface) {
        $this->depositManager->markReservedDeposit($variation);
      }
    }
  }

}
