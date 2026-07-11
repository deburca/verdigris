<?php

namespace Drupal\shh_data_retention;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Per-category GDPR retention purges (task 0006).
 *
 * Every category's window comes from shh_data_retention.settings and
 * is NULL until the client/adviser confirms it — a NULL window means
 * "no purge, say so on the status page". Orders/invoices are
 * deliberately not a category: the Danish Bookkeeping Act's 5-year
 * retention is a keep-rule owned by the accountant, and automated
 * deletion of accounting records is exactly the kind of surprise this
 * module exists to prevent.
 */
class RetentionManager {

  use StringTranslationTrait;

  const STATE_KEY = 'shh_data_retention.runs';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected Connection $database,
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected TimeInterface $time,
  ) {}

  /**
   * Category definitions: label + what the window is anchored to.
   *
   * @return array[]
   *   Keyed by category machine name.
   */
  public function categories(): array {
    return [
      'waiver_submissions' => [
        'label' => $this->t('Signed liability waivers'),
        'anchor' => $this->t("days after the rider's last visit (latest booked slot, falling back to latest facility order, then the waiver's own date)"),
      ],
      'contact_messages' => [
        'label' => $this->t('Contact-form messages'),
        'anchor' => $this->t('days after the message was sent'),
      ],
      'membership_records' => [
        'label' => $this->t('Expired/revoked membership records'),
        'anchor' => $this->t('days after the membership expired or was revoked'),
      ],
      'closed_accounts' => [
        'label' => $this->t('Blocked rider accounts'),
        'anchor' => $this->t('days after the account was blocked / last seen (staff accounts are never purged)'),
      ],
      'booking_log' => [
        'label' => $this->t('Booking audit log entries'),
        'anchor' => $this->t('days after the booked slot ended'),
      ],
    ];
  }

  /**
   * The configured window in days for a category, or NULL (disabled).
   */
  public function window(string $category): ?int {
    $value = $this->configFactory->get('shh_data_retention.settings')->get("categories.$category");
    return is_numeric($value) && (int) $value > 0 ? (int) $value : NULL;
  }

  /**
   * Runs every configured category's purge; records per-category state.
   *
   * @return array
   *   Deleted counts keyed by category (configured categories only).
   */
  public function runAll(): array {
    $results = [];
    $runs = $this->state->get(self::STATE_KEY, []);
    foreach (array_keys($this->categories()) as $category) {
      $days = $this->window($category);
      if ($days === NULL) {
        continue;
      }
      $deleted = $this->purge($category, $days);
      $results[$category] = $deleted;
      $runs[$category] = ['time' => $this->time->getRequestTime(), 'deleted' => $deleted];
      if ($deleted) {
        $this->logger->notice('Retention purge: removed @count @category item(s) older than @days days.', [
          '@count' => $deleted,
          '@category' => $category,
          '@days' => $days,
        ]);
      }
    }
    $this->state->set(self::STATE_KEY, $runs);
    return $results;
  }

  /**
   * Purges one category. Returns the number of items deleted.
   */
  public function purge(string $category, int $days): int {
    $cutoff = $this->time->getRequestTime() - $days * 86400;
    return match ($category) {
      'waiver_submissions' => $this->purgeWaiverSubmissions($cutoff),
      'contact_messages' => $this->purgeContactMessages($cutoff),
      'membership_records' => $this->purgeMembershipRecords($cutoff),
      'closed_accounts' => $this->purgeClosedAccounts($cutoff),
      'booking_log' => $this->purgeBookingLog($cutoff),
      default => throw new \InvalidArgumentException("Unknown retention category: $category"),
    };
  }

  /**
   * Counts what a category's purge would remove right now.
   *
   * NULL when the category has no confirmed window (nothing to count
   * against). Used by the status page.
   */
  public function eligibleCount(string $category): ?int {
    $days = $this->window($category);
    if ($days === NULL) {
      return NULL;
    }
    $cutoff = $this->time->getRequestTime() - $days * 86400;
    return match ($category) {
      'waiver_submissions' => count($this->eligibleWaiverSubmissions($cutoff)),
      'contact_messages' => count($this->submissionIds('contact', $cutoff)),
      'membership_records' => count($this->eligibleMembershipIds($cutoff)),
      'closed_accounts' => count($this->eligibleAccountIds($cutoff)),
      'booking_log' => count($this->eligibleLogIds($cutoff)),
      default => NULL,
    };
  }

  /**
   * Waivers: anchored to the rider's last visit, not the signature date.
   *
   * Claims exposure runs from the last time the rider actually rode —
   * the insurer question in the task/privacy draft. Last visit = the
   * rider's latest booked slot end (0002's log), falling back to their
   * latest placed facility order, then the submission's own created
   * date (a rider who signed but never booked).
   */
  protected function purgeWaiverSubmissions(int $cutoff): int {
    return $this->deleteEntities('webform_submission', $this->eligibleWaiverSubmissions($cutoff));
  }

  /**
   * Waiver submissions whose owner's last visit predates the cutoff.
   */
  protected function eligibleWaiverSubmissions(int $cutoff): array {
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    $ids = $storage->getQuery()
      ->condition('webform_id', 'shh_rider_waiver')
      ->accessCheck(FALSE)
      ->execute();
    $eligible = [];
    foreach ($storage->loadMultiple($ids) as $submission) {
      $uid = (int) $submission->getOwnerId();
      $last_visit = $this->lastVisit($uid) ?? (int) $submission->getCreatedTime();
      if ($last_visit < $cutoff) {
        $eligible[] = $submission->id();
      }
    }
    return $eligible;
  }

  /**
   * The rider's last visit as a timestamp, or NULL if none is on file.
   */
  protected function lastVisit(int $uid): ?int {
    if ($uid) {
      $slot_end = $this->database->query(
        'SELECT MAX(slot_end) FROM {shh_booking_log} WHERE actor = :uid',
        [':uid' => $uid],
      )->fetchField();
      if ($slot_end) {
        return (int) $slot_end;
      }
      $placed = $this->database->query(
        'SELECT MAX(placed) FROM {commerce_order} WHERE uid = :uid AND placed IS NOT NULL',
        [':uid' => $uid],
      )->fetchField();
      if ($placed) {
        return (int) $placed;
      }
    }
    return NULL;
  }

  /**
   * Contact messages: plain age-based purge of contact submissions.
   */
  protected function purgeContactMessages(int $cutoff): int {
    return $this->deleteEntities('webform_submission', $this->submissionIds('contact', $cutoff));
  }

  /**
   * Webform submission ids of a form older than the cutoff.
   */
  protected function submissionIds(string $webform_id, int $cutoff): array {
    return array_values($this->entityTypeManager->getStorage('webform_submission')->getQuery()
      ->condition('webform_id', $webform_id)
      ->condition('created', $cutoff, '<')
      ->accessCheck(FALSE)
      ->execute());
  }

  /**
   * Membership records: expired/revoked ones past the window.
   *
   * Anchored to the expiry timestamp when there is one, else the
   * record's creation (a revoked membership that never had an expiry).
   * Pending/active memberships are never touched.
   */
  protected function purgeMembershipRecords(int $cutoff): int {
    return $this->deleteEntities('shh_rider_membership', $this->eligibleMembershipIds($cutoff));
  }

  /**
   * Expired/revoked membership ids past the cutoff.
   */
  protected function eligibleMembershipIds(int $cutoff): array {
    $storage = $this->entityTypeManager->getStorage('shh_rider_membership');
    $ids = $storage->getQuery()
      ->condition('status', ['expired', 'revoked'], 'IN')
      ->accessCheck(FALSE)
      ->execute();
    $eligible = [];
    foreach ($storage->loadMultiple($ids) as $membership) {
      $anchor = (int) ($membership->get('expires')->value ?: $membership->get('created')->value);
      if ($anchor && $anchor < $cutoff) {
        $eligible[] = $membership->id();
      }
    }
    return $eligible;
  }

  /**
   * Blocked rider accounts past the grace period.
   *
   * Rider accounts only: uid 1 and anyone with a role beyond
   * "authenticated" (staff) are never eligible. Anchor = the latest of
   * last access and created (a blocked applicant who never logged in
   * ages from registration). The account entity is deleted; records
   * governed by other retention rules (orders under the Bookkeeping
   * Act, waivers under their own window) are deliberately NOT cascaded
   * here — each category owns its own clock.
   */
  protected function purgeClosedAccounts(int $cutoff): int {
    $storage = $this->entityTypeManager->getStorage('user');
    $count = 0;
    foreach ($storage->loadMultiple($this->eligibleAccountIds($cutoff)) as $account) {
      $account->delete();
      $count++;
    }
    return $count;
  }

  /**
   * Blocked, non-staff, past-grace user ids.
   */
  protected function eligibleAccountIds(int $cutoff): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $ids = $storage->getQuery()
      ->condition('status', 0)
      ->condition('uid', 1, '>')
      ->accessCheck(FALSE)
      ->execute();
    $eligible = [];
    foreach ($storage->loadMultiple($ids) as $account) {
      if (array_diff($account->getRoles(), ['authenticated', 'anonymous'])) {
        continue;
      }
      $anchor = max((int) $account->getLastAccessedTime(), (int) $account->getCreatedTime());
      if ($anchor < $cutoff) {
        $eligible[] = $account->id();
      }
    }
    return $eligible;
  }

  /**
   * Booking-log entries whose slot ended before the cutoff.
   */
  protected function purgeBookingLog(int $cutoff): int {
    return $this->deleteEntities('shh_booking_log', $this->eligibleLogIds($cutoff));
  }

  /**
   * Booking-log entry ids past the cutoff.
   */
  protected function eligibleLogIds(int $cutoff): array {
    return array_values($this->entityTypeManager->getStorage('shh_booking_log')->getQuery()
      ->condition('slot_end', $cutoff, '<')
      ->condition('slot_end', 0, '>')
      ->accessCheck(FALSE)
      ->execute());
  }

  /**
   * Deletes entities by id; returns how many.
   */
  protected function deleteEntities(string $entity_type, array $ids): int {
    if (!$ids) {
      return 0;
    }
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $storage->delete($storage->loadMultiple($ids));
    return count($ids);
  }

}
