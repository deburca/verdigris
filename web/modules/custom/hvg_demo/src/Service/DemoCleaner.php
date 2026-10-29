<?php

namespace Drupal\hvg_demo\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\hivelog\Delete\HivelogDeleteDependencyRegistry;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes expired demo accounts together with everything they leave behind.
 *
 * The privacy policy promises that a demo account and everything entered in it
 * are deleted after the configured lifetime, so nothing may be left behind:
 * - Everything under the account's apiaries goes with them, whoever created it
 *   (a member's inspection in a demo apiary is as much part of the demo as the
 *   apiary): HiveLog's delete-dependency registry names every parent-to-child
 *   relationship, and the delete form's BLOCK rule is an access check only, so
 *   the whole tree is walked children first and nothing is left dangling.
 * - Everything else the account created, in any apiary (HiveLog entities and
 *   uploaded files by owner), and every membership it holds in other people's
 *   apiaries.
 * - Files that were attached to what was deleted and are used nowhere else.
 * - Rows other modules keep for the account that core's user delete does not
 *   remove: form submissions, sessions, honeypot and autosave records and the
 *   account's log entries.
 */
final class DemoCleaner {

  /**
   * Users deleted per cron run, to keep a run short.
   */
  private const BATCH = 25;

  /**
   * Children deleted per query while walking an entity's dependants.
   */
  private const CHUNK = 50;

  /**
   * Constructs the cleaner.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
    private readonly Connection $database,
    private readonly FileUsageInterface $fileUsage,
    private readonly SessionManagerInterface $sessionManager,
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
      $this->deleteAccount($account);
    }
    return count($uids);
  }

  /**
   * Deletes one demo account and everything it owns or leaves behind.
   *
   * Does not check the account's age or role: callers do.
   */
  public function deleteAccount(UserInterface $account): void {
    $uid = (int) $account->id();
    if ($uid < 2) {
      throw new \InvalidArgumentException('Refusing to delete an account that is not a demo account.');
    }
    $this->deleteHivelogData($uid);
    $this->deleteOwnedFiles($uid);
    $this->deleteOtherRows($uid);
    $account->delete();
    $this->logger->info('Deleted expired demo account @uid.', ['@uid' => $uid]);
  }

  /**
   * Deletes the account's HiveLog records, its apiaries first.
   */
  private function deleteHivelogData(int $uid): void {
    $types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      $owner = $definition->getKey('owner');
      if ($owner && $this->isHivelogType($definition->getProvider())) {
        $types[$id] = $owner;
      }
    }
    // Apiaries first: deleting one takes everything under it, so what is left
    // for the owner sweep is only what the account made elsewhere.
    uksort($types, static fn(string $a, string $b): int => ($b === 'apiary') <=> ($a === 'apiary'));
    foreach ($types as $id => $owner) {
      $storage = $this->entityTypeManager->getStorage($id);
      $ids = $storage->getQuery()->accessCheck(FALSE)->condition($owner, $uid)->execute();
      foreach ($storage->loadMultiple($ids) as $entity) {
        $this->purge($entity);
      }
    }
    // Memberships the account holds in other people's apiaries (the sweep above
    // finds only those it created itself).
    if (isset($types['apiary_membership'])) {
      $storage = $this->entityTypeManager->getStorage('apiary_membership');
      foreach ($storage->loadMultiple($storage->getQuery()->accessCheck(FALSE)->condition('account', $uid)->execute()) as $membership) {
        $this->purge($membership);
      }
    }
  }

  /**
   * Deletes an entity after everything that depends on it, and its files.
   *
   * Walks HiveLog's delete-dependency registry for every kind of dependant,
   * whatever its treatment (BLOCK only stops a person in the UI; CASCADE and
   * DETACH would keep or leave things the demo must not keep), children first.
   */
  private function purge(EntityInterface $entity): void {
    $files = $this->referencedFileIds($entity);
    foreach (HivelogDeleteDependencyRegistry::rowsForParent($entity->getEntityTypeId()) as $row) {
      if (!$this->entityTypeManager->hasDefinition($row['child'])) {
        continue;
      }
      $storage = $this->entityTypeManager->getStorage($row['child']);
      $tried = [];
      while ($ids = $storage->getQuery()->accessCheck(FALSE)->condition($row['field'], $entity->id())->range(0, self::CHUNK)->execute()) {
        $pending = array_filter($ids, static fn($id): bool => !isset($tried[$id]));
        if (!$pending) {
          // Something would not delete; do not loop on it.
          $this->logger->warning('Could not delete @type @ids under @parent @id.', [
            '@type' => $row['child'], '@ids' => implode(',', $ids),
            '@parent' => $entity->getEntityTypeId(), '@id' => $entity->id(),
          ]);
          break;
        }
        $ids = $pending;
        foreach ($storage->loadMultiple($ids) as $child) {
          $this->purge($child);
        }
        $tried += array_fill_keys($ids, TRUE);
      }
    }
    $entity->delete();
    $this->deleteUnusedFiles($files);
  }

  /**
   * The ids of the files an entity's image and file fields point at.
   *
   * @return int[]
   *   File ids.
   */
  private function referencedFileIds(EntityInterface $entity): array {
    $ids = [];
    if (!method_exists($entity, 'getFieldDefinitions')) {
      return $ids;
    }
    foreach ($entity->getFieldDefinitions() as $name => $definition) {
      if (in_array($definition->getType(), ['image', 'file'], TRUE)) {
        foreach ($entity->get($name) as $item) {
          $ids[] = (int) $item->target_id;
        }
      }
    }
    return array_values(array_unique(array_filter($ids)));
  }

  /**
   * Deletes the given files if nothing uses them any more.
   *
   * @param int[] $ids
   *   File ids.
   */
  private function deleteUnusedFiles(array $ids): void {
    if (!$ids) {
      return;
    }
    $storage = $this->entityTypeManager->getStorage('file');
    foreach ($storage->loadMultiple($ids) as $file) {
      if (!$this->fileUsage->listUsage($file)) {
        $file->delete();
      }
    }
  }

  /**
   * Deletes every file the account uploaded (pictures, photos).
   */
  private function deleteOwnedFiles(int $uid): void {
    $storage = $this->entityTypeManager->getStorage('file');
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition('uid', $uid)->execute();
    if ($ids) {
      $storage->delete($storage->loadMultiple($ids));
    }
  }

  /**
   * Removes what other modules keep for the account.
   *
   * Core's user delete takes the account's data and stored settings, but not
   * a webform's submissions, the honeypot and autosave records, the session
   * rows or the log entries (which carry the account's IP address).
   */
  private function deleteOtherRows(int $uid): void {
    if ($this->entityTypeManager->hasDefinition('webform_submission')) {
      $storage = $this->entityTypeManager->getStorage('webform_submission');
      $ids = $storage->getQuery()->accessCheck(FALSE)->condition('uid', $uid)->execute();
      if ($ids) {
        $storage->delete($storage->loadMultiple($ids));
      }
    }
    foreach (['honeypot_user', 'autosave_form_entity_form', 'watchdog'] as $table) {
      if ($this->database->schema()->tableExists($table)) {
        $this->database->delete($table)->condition('uid', $uid)->execute();
      }
    }
    $this->sessionManager->delete($uid);
  }

  /**
   * Whether an entity type's provider is HiveLog or one of its submodules.
   */
  private function isHivelogType(string $provider): bool {
    return str_starts_with($provider, 'hivelog') || in_array($provider, ['nanoprobe', 'collective', 'nexus'], TRUE);
  }

}
