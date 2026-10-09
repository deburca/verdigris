<?php

namespace Drupal\hvg_demo\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sends demo users to HiveLog after logging in.
 */
final class DemoLogin {

  /**
   * Constructs the hook class.
   */
  public function __construct(private readonly RequestStack $requestStack) {}

  /**
   * Implements hook_user_login().
   *
   * Runs after the Dashboard module's own login redirect, which would
   * otherwise win and send demo users to a dashboard they cannot use.
   */
  #[Hook('user_login', order: Order::Last)]
  public function userLogin(AccountInterface $account): void {
    if (in_array('demo', $account->getRoles(), TRUE)) {
      $this->requestStack->getCurrentRequest()->query->set('destination', '/hivelog');
    }
  }

}
