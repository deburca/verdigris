<?php

namespace Drupal\hvg_landing\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\State\StateInterface;
use Drupal\hvg_landing\LandingPage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Seeds the landing page once, after a config import has installed Beeswax.
 *
 * A deploy hook alone cannot do this on a first install: with
 * `site:install --existing-config` the config import is part of the install
 * and deploy hooks never run, and this module is installed before the Beeswax
 * theme, so its Canvas components do not exist until later in the same import.
 */
final class ImportSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the subscriber.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountSwitcherInterface $accountSwitcher,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::IMPORT => 'onImport'];
  }

  /**
   * Creates the page the first time an import completes with Beeswax active.
   *
   * Runs as the superuser: on a fresh `site:install --existing-config` the
   * import runs as the anonymous user before the imported role permissions
   * (such as "view media") exist, so the page's own access checks would fail.
   * A failure is logged and retried on the next import; it must never abort
   * the import itself.
   */
  public function onImport(ConfigImporterEvent $event): void {
    if ($this->state->get('hvg_landing.seeded')) {
      return;
    }
    $component = $this->entityTypeManager
      ->getStorage('component')
      ->load('sdc.beeswax.cta');
    if (!$component) {
      return;
    }
    $this->accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    try {
      LandingPage::create();
      $this->state->set('hvg_landing.seeded', TRUE);
    }
    catch (\Throwable $e) {
      $this->logger->error('Could not create the landing page: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    finally {
      $this->accountSwitcher->switchBack();
    }
  }

}
