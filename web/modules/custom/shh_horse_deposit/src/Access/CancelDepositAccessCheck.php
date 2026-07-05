<?php

namespace Drupal\shh_horse_deposit\Access;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for the self-service deposit cancellation form.
 */
class CancelDepositAccessCheck {

  /**
   * Checks access.
   */
  public function access(AccountInterface $account, OrderItemInterface $commerce_order_item) {
    if ($commerce_order_item->bundle() !== 'horse_deposit') {
      return AccessResult::forbidden('Not a deposit order item.');
    }

    $order = $commerce_order_item->getOrder();
    if (!$order || !$order->getPlacedTime()) {
      return AccessResult::forbidden('Order has not been placed yet.');
    }

    if ($account->hasPermission('administer commerce_order')) {
      return AccessResult::allowed()->addCacheableDependency($order);
    }

    $is_owner = $order->getCustomerId() && (int) $order->getCustomerId() === (int) $account->id();
    return AccessResult::allowedIf($is_owner)->addCacheableDependency($order);
  }

}
