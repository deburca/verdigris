<?php

namespace Drupal\hvg_landing\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Keeps the account pages in the site theme instead of the admin theme.
 *
 * Core flags a person's edit-account and cancel-account pages as admin routes,
 * so they are drawn in Gin. For this site they are end-user pages, like the
 * profile page, and belong in Beeswax. Clears the flag; runs after every other
 * module's alter, so nothing sets it back.
 */
final class UserPagesRouteSubscriber extends RouteSubscriberBase {

  /**
   * Account routes core draws in the admin theme.
   */
  private const ROUTES = [
    'entity.user.edit_form',
    'entity.user.cancel_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach (self::ROUTES as $name) {
      $collection->get($name)?->setOption('_admin_route', FALSE);
    }
    // Core titles the edit page with the person's name, which the breadcrumb
    // then repeats ("paddy > paddy"); name the page for what it is.
    if ($route = $collection->get('entity.user.edit_form')) {
      $defaults = $route->getDefaults();
      unset($defaults['_title_callback']);
      $defaults['_title'] = 'Edit account';
      $route->setDefaults($defaults);
    }
    // The log-out confirmation has no route title, so the breadcrumb named it
    // after the last path segment ("confirm"). The page's own heading, the
    // form's question, still wins as the H1.
    if ($route = $collection->get('user.logout.confirm')) {
      $route->setDefault('_title', 'Log out');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events['routing.route_alter'] = ['onAlterRoutes', -1000];
    return $events;
  }

}
