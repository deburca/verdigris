<?php

namespace Drupal\shh_horse_sale_state\EventSubscriber;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Marks a horse variation "sold" when its full-price order is placed.
 *
 * Structurally mirrors
 * \Drupal\shh_horse_deposit\EventSubscriber\DepositCheckoutCompletionSubscriber
 * (same `commerce_order.place.pre_transition` event), but for the plain
 * `horse` order item type used by the standard full-price purchase path,
 * rather than `horse_deposit`. See
 * docs/project-management/tasks/0024-horse-sale-state-enforcement.md.
 */
class HorseSaleCompletionSubscriber implements EventSubscriberInterface {

  public function __construct(protected LoggerInterface $logger) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return ['commerce_order.place.pre_transition' => 'markSold'];
  }

  /**
   * Marks each "horse" order item's variation sold.
   */
  public function markSold(WorkflowTransitionEvent $event): void {
    $order = $event->getEntity();
    foreach ($order->getItems() as $order_item) {
      if ($order_item->bundle() !== 'horse') {
        continue;
      }
      $variation = $order_item->getPurchasedEntity();
      if (!$variation instanceof ProductVariationInterface || !$variation->hasField('field_sale_state')) {
        continue;
      }
      if ($variation->get('field_sale_state')->value === 'sold') {
        // Defensive: shouldn't happen since the availability checker blocks
        // re-purchase of an already-sold horse, but avoid a redundant save
        // if it somehow does.
        continue;
      }
      $variation->set('field_sale_state', 'sold');
      $variation->save();
      $this->logger->info('Variation @id marked sold (order @order placed).', [
        '@id' => $variation->id(),
        '@order' => $order->id(),
      ]);
    }
  }

}
