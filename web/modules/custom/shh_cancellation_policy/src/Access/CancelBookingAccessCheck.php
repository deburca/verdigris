<?php

namespace Drupal\shh_cancellation_policy\Access;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for the self-service booking cancellation form.
 *
 * The order item's own order must belong to the current user (or the user
 * must have staff-level order administration permission), and it must be a
 * "bee" (booking) order item on a *placed* order — cancelling a draft cart
 * item is just removing it from the cart, not this flow.
 */
class CancelBookingAccessCheck {

  /**
   * Checks access.
   */
  public function access(AccountInterface $account, OrderItemInterface $commerce_order_item) {
    if ($commerce_order_item->bundle() !== 'bee') {
      return AccessResult::forbidden('Not a booking order item.');
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
