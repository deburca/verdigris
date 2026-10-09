<?php

namespace Drupal\hvg_landing\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\hvg_landing\LandingPage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Seeds the landing page once, after a config import has installed Beeswax.
 *
 * A deploy hook alone cannot do this on a first deploy: the config import
 * installs this module before the Beeswax theme (and so before its Canvas
 * components exist), and a freshly installed module's deploy hooks are
 * baselined rather than run.
 */
final class ImportSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the subscriber.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::IMPORT => 'onImport'];
  }

  /**
   * Creates the page the first time an import completes with Beeswax active.
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
    LandingPage::create();
    $this->state->set('hvg_landing.seeded', TRUE);
  }

}
