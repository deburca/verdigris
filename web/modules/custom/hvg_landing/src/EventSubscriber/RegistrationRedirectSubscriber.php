<?php

namespace Drupal\hvg_landing\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sends a visitor from the closed registration page to the log-in page.
 *
 * Visitor registration is administrators-only here, so /user/register is
 * access denied. Gin Login draws that refusal in its own two-column layout
 * without its stylesheet (a page-wide logo and wallpaper), and the person
 * wanted to join, not to read a refusal: the log-in page says what they can
 * do.
 *
 * Drupal checks route access while matching the route, so the refusal arrives
 * as an exception, not as a matched route: this listens for that exception.
 */
final class RegistrationRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the subscriber.
   */
  public function __construct(
    private readonly AccountInterface $currentUser,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::EXCEPTION => ['onException', 100]];
  }

  /**
   * Redirects an anonymous visitor refused on /user/register when it is closed.
   */
  public function onException(ExceptionEvent $event): void {
    if (!$event->isMainRequest() || !$event->getThrowable() instanceof AccessDeniedHttpException) {
      return;
    }
    if (!str_ends_with($event->getRequest()->getPathInfo(), '/user/register')) {
      return;
    }
    if ($this->currentUser->isAnonymous() && $this->configFactory->get('user.settings')->get('register') === 'admin_only') {
      $event->setResponse(new RedirectResponse(Url::fromRoute('user.login')->toString()));
    }
  }

}
