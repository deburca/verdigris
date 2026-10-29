<?php

namespace Drupal\hvg_landing\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Draws a person's own account pages in the site theme, whoever they are.
 *
 * Two-factor setup (tfa), the scheduled-content list (scheduler) and a
 * person's form submissions (webform) are end-user pages here, like the
 * profile, but each module sends an administrator to the admin theme (Gin).
 * Runs before those negotiators, so an administrator sees the same Beeswax
 * pages as everyone else.
 */
final class UserPagesThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Routes drawn in the site theme.
   */
  private const ROUTES = [
    'tfa.overview',
    'tfa.disable',
    'tfa.plugin.reset',
    'tfa.validation.setup',
    'entity.webform_submission.user',
  ];

  /**
   * Constructs the negotiator.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    $name = (string) $route_match->getRouteName();
    if (in_array($name, self::ROUTES, TRUE)) {
      return TRUE;
    }
    // The scheduler views' user pages (content, media), as scheduler itself
    // lists them.
    if ($this->moduleHandler->moduleExists('scheduler')) {
      return in_array($name, \Drupal::service('scheduler.manager')->getUserPageViewRoutes(), TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match): ?string {
    return $this->configFactory->get('system.theme')->get('default');
  }

}
