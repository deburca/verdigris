<?php

namespace Drupal\hvg_landing\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Titles on a person's own account pages.
 */
final class UserPagesHooks {

  /**
   * Constructs the hook class.
   */
  public function __construct(private readonly RouteMatchInterface $routeMatch) {}

  /**
   * Names a person's form submissions page for what it is.
   *
   * Webform titles it with the person's name, which the breadcrumb ("admin >
   * Submissions") then repeats above an unexplained name; the route's own
   * title ("Submissions") loses to the one webform sets at render time.
   */
  #[Hook('preprocess_page_title')]
  public function preprocessPageTitle(array &$variables): void {
    if ($this->routeMatch->getRouteName() === 'entity.webform_submission.user') {
      $variables['title'] = t('Submissions');
    }
  }

}
