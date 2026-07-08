<?php

namespace Drupal\shh_horse_sale_state;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Staff action: return a sold horse to available (task 0037).
 *
 * The counterpart to HorseSaleCompletionSubscriber's automatic
 * available → sold flip on order placement, for which no path back
 * existed at all (a returned horse, a buyer backing out of a bank
 * transfer, a failed payment after placement, or plain testing all
 * required editing the field by hand, leaving no trace).
 *
 * Deliberately decoupled from refunding — unlike a deposit
 * (0036/0001), the money here is the full purchase price and whether
 * to refund it, partially refund it, or keep it is a per-case
 * staff/accountant decision the platform cannot automate (the Manual
 * gateway means payment state may not even reflect reality). This
 * class only flips the sale state and *identifies* the originating
 * order so staff can handle the money through Commerce's own
 * order/payment UI.
 */
class RelistManager {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Finds the placed order whose sale most recently flipped this horse sold.
   *
   * Most recent first, so a horse that was sold, relisted and sold again
   * points at its *current* sale, not a historical one.
   */
  public function findOriginatingSaleOrder(ProductVariationInterface $variation): ?OrderInterface {
    $storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $item_ids = $storage->getQuery()
      ->condition('type', 'horse')
      ->condition('purchased_entity', $variation->id())
      ->accessCheck(FALSE)
      ->execute();
    if (!$item_ids) {
      return NULL;
    }
    $items = $storage->loadMultiple($item_ids);
    krsort($items);
    foreach ($items as $item) {
      $order = $item->getOrder();
      if ($order && $order->getPlacedTime()) {
        return $order;
      }
    }
    return NULL;
  }

  /**
   * Relists a sold horse: sold → available, money untouched.
   *
   * @return array{relisted: bool, order_id: int|string|null, reason: string}
   *   Whether the horse was relisted, the originating sale order's id
   *   (NULL when none was found), and a machine reason code.
   */
  public function relistSoldHorse(ProductVariationInterface $variation): array {
    if (!$variation->hasField('field_sale_state') || $variation->get('field_sale_state')->value !== 'sold') {
      return ['relisted' => FALSE, 'order_id' => NULL, 'reason' => 'not_sold'];
    }

    $order = $this->findOriginatingSaleOrder($variation);

    $variation->set('field_sale_state', 'available');
    $variation->save();

    if ($order) {
      $this->logger->notice('Horse variation @id relisted (sold → available) by user @uid; originating sale order @order and its payments left untouched — any refund is a manual staff decision through Commerce.', [
        '@id' => $variation->id(),
        '@uid' => $this->currentUser->id(),
        '@order' => $order->id(),
      ]);
      return ['relisted' => TRUE, 'order_id' => $order->id(), 'reason' => 'relisted'];
    }

    $this->logger->warning('Horse variation @id relisted (sold → available) by user @uid with no placed sale order found — the sold state had no valid backing order.', [
      '@id' => $variation->id(),
      '@uid' => $this->currentUser->id(),
    ]);
    return ['relisted' => TRUE, 'order_id' => NULL, 'reason' => 'relisted_no_order'];
  }

}
