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
      'contact_messages' => [
        'label' => $this->t('Contact-form messages'),
        'anchor' => $this->t('days after the message was sent'),
      ],
      'stale_registrations' => [
        'label' => $this->t('Unapproved registration applications'),
        'anchor' => $this->t('days after applying, for accounts still awaiting approval that have never been used to log in (staff accounts, and any rider who has ever logged in — including suspended ones — are never purged)'),
      ],
    ];
  }

  /**
   * Data deliberately kept with no automatic purge, and why.
   *
   * Task 0047's decisions, surfaced on the status page so "no purge"
   * reads as a decision rather than a gap.
   *
   * @return array[]
   *   Rows of 'label' + 'reason'.
   */
  public function retainedByDesign(): array {
    return [
      [
        'label' => $this->t('Booking audit log entries'),
        'reason' => $this->t('Kept indefinitely as an operational audit trail. When a rider account is deleted, its log entries are anonymised in place by shh_account_deletion (task 0044) — the actor is nulled and the entry renders as "Deleted user" — so surviving entries hold no personal data and a time-based purge would only destroy audit value.'),
      ],
      [
        'label' => $this->t('Commerce orders'),
        'reason' => $this->t('Kept as workflow records; anonymised (uid 0, e-mail blanked, billing profile deleted) when the account is deleted (task 0044). Invoicing and the Danish Bookkeeping Act’s 5-year retention are handled in the external accounting system, not here.'),
      ],
      [
        'label' => $this->t('Active rider accounts and their records'),
        'reason' => $this->t('Kept for as long as the rider has an account. Deletion is immediate and complete when it happens (task 0044), so there is no “closed account” state to age out — which is why no grace-period purge exists.'),
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
      'contact_messages' => $this->purgeContactMessages($cutoff),
      'stale_registrations' => $this->purgeStaleRegistrations($cutoff),
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
      'contact_messages' => count($this->submissionIds('contact', $cutoff)),
      'stale_registrations' => count($this->eligibleAccountIds($cutoff)),
      default => NULL,
    };
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
   * Registration applications that were never approved or used.
   *
   * Task 0047 reworked this category (it began life as
   * "closed_accounts", a premise task 0044 voided: account closure is
   * now immediate and complete, so no closed account ever survives to
   * age out). What genuinely accumulates PII here is the *blocked*
   * account: on this site "blocked" is the state 0026's registration
   * policy puts every new applicant in until staff approve them, plus
   * the accounts 0034's guard blocks at guest checkout. An application
   * nobody ever approved would otherwise hold a name and e-mail
   * forever.
   *
   * Two structural guards, both deliberate:
   * - **Never-logged-in only** (`access == 0`). A *suspended* rider is
   *   also `status = 0`, and auto-deleting one would be wrong twice
   *   over: it frees their e-mail to re-register (defeating the
   *   suspension) and anonymises their orders. A staff block is a
   *   "keep out" record, not a retention subject.
   * - Staff (any role beyond authenticated) and uid 1 are never
   *   eligible.
   *
   * Deleting the account runs shh_account_deletion's hook_user_delete
   * (task 0044), so memberships, credits and webform submissions go
   * with it and orders/booking-log entries are anonymised in place —
   * a full, correct cleanup, not an orphaning delete.
   */
  protected function purgeStaleRegistrations(int $cutoff): int {
    $storage = $this->entityTypeManager->getStorage('user');
    $count = 0;
    foreach ($storage->loadMultiple($this->eligibleAccountIds($cutoff)) as $account) {
      $this->logger->notice('Retention: deleting unapproved registration @name (uid @uid, applied @date, never logged in).', [
        '@name' => $account->getAccountName(),
        '@uid' => $account->id(),
        '@date' => date('Y-m-d', (int) $account->getCreatedTime()),
      ]);
      $account->delete();
      $count++;
    }
    return $count;
  }

  /**
   * Blocked, never-logged-in, non-staff, past-window user ids.
   */
  protected function eligibleAccountIds(int $cutoff): array {
    $storage = $this->entityTypeManager->getStorage('user');
    $ids = $storage->getQuery()
      ->condition('status', 0)
      ->condition('uid', 1, '>')
      ->condition('access', 0)
      ->condition('login', 0)
      ->accessCheck(FALSE)
      ->execute();
    $eligible = [];
    foreach ($storage->loadMultiple($ids) as $account) {
      if (array_diff($account->getRoles(), ['authenticated', 'anonymous'])) {
        continue;
      }
      if ((int) $account->getCreatedTime() < $cutoff) {
        $eligible[] = $account->id();
      }
    }
    return $eligible;
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
