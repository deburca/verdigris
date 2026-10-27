<?php

namespace Drupal\hvg_landing\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Attaches the Beeswax skin to the Gin Login pages.
 */
final class LoginSkin {

  /**
   * The routes Gin Login renders in its own two-column layout.
   */
  private const ROUTES = [
    'user.login',
    'user.register',
    'user.pass',
    'user.reset.form',
    'user.logout.confirm',
  ];

  /**
   * Constructs the hook class.
   */
  public function __construct(private readonly RouteMatchInterface $routeMatch) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#cache']['contexts'][] = 'route';
    if (in_array($this->routeMatch->getRouteName(), self::ROUTES, TRUE)) {
      $attachments['#attached']['library'][] = 'hvg_landing/login';
    }
  }

}
