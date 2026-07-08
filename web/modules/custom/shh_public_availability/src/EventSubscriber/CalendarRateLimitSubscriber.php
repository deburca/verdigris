<?php

namespace Drupal\shh_public_availability\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rate-limits the public BAT calendar-events endpoint (task 0007).
 *
 * /bat_api/rest/calendar-events is deliberately anonymous (decision 0017,
 * task 0021) but uncacheable (max-age 0) and does real availability
 * calculation per request, which leaves it open to scraping and scripted
 * slot-sniping. Core's flood service caps how often a single client can
 * hit it: per user id when authenticated, per client IP when anonymous.
 *
 * The sibling /bat_api/rest/calendar-units endpoint needs no limiting —
 * anonymous has no permission for it (403).
 */
class CalendarRateLimitSubscriber implements EventSubscriberInterface {

  /**
   * Flood event name for calendar-events requests.
   */
  public const FLOOD_NAME = 'shh_public_availability.calendar_events';

  /**
   * Default requests allowed per window when config sets no threshold.
   *
   * A real visitor's FullCalendar widget fires one request per month
   * navigation; 60/minute is far above any human browsing pattern while
   * still shutting down tight polling loops.
   */
  protected const DEFAULT_THRESHOLD = 60;

  /**
   * Default flood window in seconds when config sets no window.
   */
  protected const DEFAULT_WINDOW = 60;

  public function __construct(
    protected FloodInterface $flood,
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After routing (priority 32) so the route name is resolved, before
    // any controller work so a limited request costs nothing.
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

  /**
   * Rejects over-limit calendar-events requests with a 429.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $request = $event->getRequest();
    if ($request->attributes->get('_route') !== 'rest.bat_api_events_resource.GET') {
      return;
    }
    if ($this->currentUser->hasPermission('bypass availability calendar rate limiting')) {
      return;
    }

    // Overridable via shh_public_availability.settings without a deploy;
    // defaults live in code (same pattern as shh_horse_deposit's
    // deposit_percentage) so no install-time config is needed.
    $config = $this->configFactory->get('shh_public_availability.settings');
    $threshold = $config->get('rate_limit_threshold') ?? self::DEFAULT_THRESHOLD;
    $window = $config->get('rate_limit_window') ?? self::DEFAULT_WINDOW;

    // Authenticated clients get their own bucket (a logged-in scraper
    // must not ride on a shared office/NAT IP's allowance, nor exhaust
    // it for others); anonymous clients are keyed by IP.
    $identifier = $this->currentUser->isAuthenticated()
      ? 'user:' . $this->currentUser->id()
      : 'ip:' . $request->getClientIp();

    if (!$this->flood->isAllowed(self::FLOOD_NAME, $threshold, $window, $identifier)) {
      $event->setResponse(new JsonResponse(
        ['message' => 'Too many availability requests; please retry later.'],
        429,
        ['Retry-After' => (string) $window, 'Cache-Control' => 'no-store'],
      ));
      return;
    }
    $this->flood->register(self::FLOOD_NAME, $window, $identifier);
  }

}
