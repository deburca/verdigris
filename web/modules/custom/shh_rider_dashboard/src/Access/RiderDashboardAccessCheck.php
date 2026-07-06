<?php

namespace Drupal\shh_rider_dashboard\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Access check for /user/{user}/bookings.
 *
 * Same ownership pattern already used by the individual cancel routes
 * (shh_cancellation_policy's CancelBookingAccessCheck, shh_horse_deposit's
 * CancelDepositAccessCheck) — this page is a discovery aid surfacing
 * data those routes already gate, not a new access surface (task 0022's
 * own acceptance criteria says this explicitly), so the rule here is
 * deliberately the same shape: the account owner, or staff.
 */
class RiderDashboardAccessCheck {

  /**
   * Checks access to a rider's own bookings/deposits/credits dashboard.
   */
  public function access(AccountInterface $account, UserInterface $user) {
    if ($account->hasPermission('administer commerce_order')) {
      return AccessResult::allowed()->addCacheContexts(['user.permissions']);
    }
    $is_owner = $account->isAuthenticated() && (int) $account->id() === (int) $user->id();
    return AccessResult::allowedIf($is_owner)->addCacheContexts(['user']);
  }

}
