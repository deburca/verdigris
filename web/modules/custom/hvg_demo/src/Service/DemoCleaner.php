<?php

namespace Drupal\hvg_demo\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes expired demo accounts together with everything they own.
 *
 * HiveLog has no user-deletion handling, and core's user cancel methods
 * leave custom entities behind with a dangling owner, so the data is removed
 * here explicitly before the account itself.
 */
final class DemoCleaner {

  /**
   * Users deleted per cron run, to keep a run short.
   */
  private const BATCH = 25;

  /**
   * Constructs the cleaner.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Deletes demo users older than the configured lifetime.
   *
   * @return int
   *   The number of accounts deleted.
   */
  public function deleteExpired(): int {
    $days = (int) $this->configFactory->get('hvg_demo.settings')
      ->get('lifetime_days');
    if ($days < 1) {
      return 0;
    }
    $users = $this->entityTypeManager->getStorage('user');
    $uids = $users->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', 'demo')
      ->condition('uid', 1, '>')
      ->condition('created', $this->time->getRequestTime() - $days * 86400, '<')
      ->range(0, self::BATCH)
      ->execute();
    foreach ($users->loadMultiple($uids) as $account) {
      $this->deleteOwnedContent((int) $account->id());
      $account->delete();
      $this->logger->info('Deleted expired demo account @uid.', [
        '@uid' => $account->id(),
      ]);
    }
    return count($uids);
  }

  /**
   * Deletes every HiveLog entity and uploaded file the user owns.
   */
  private function deleteOwnedContent(int $uid): void {
    $types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      $owner = $definition->getKey('owner');
      if ($owner && str_starts_with($definition->getProvider(), 'hivelog')) {
        $types[$id] = $owner;
      }
    }
    $types['file'] = 'uid';
    foreach ($types as $id => $owner) {
      $storage = $this->entityTypeManager->getStorage($id);
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($owner, $uid)
        ->execute();
      if ($ids) {
        $storage->delete($storage->loadMultiple($ids));
      }
    }
  }

}
