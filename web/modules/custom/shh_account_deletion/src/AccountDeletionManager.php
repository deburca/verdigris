<?php

namespace Drupal\shh_account_deletion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Purges PII and anonymises referencing records when a rider is deleted.
 *
 * Called from hook_user_delete() — the single point through which every
 * account deletion path (admin, self-service, shh_data_retention cron)
 * passes. The user entity is already removed from the database at this
 * point, but all referencing rows (orders, booking log, memberships, etc.)
 * still hold the integer uid in their columns and are fully loadable.
 *
 * Records in two categories:
 *
 * DELETED immediately (personal records that have no operational purpose
 * once the rider is gone):
 *   - shh_rider_membership entities
 *   - shh_facility_credit entities + their shh_facility_credit_transaction
 *     rows (transactions reference the credit entity; delete them first)
 *   - webform_submission entities (liability waivers, contact messages)
 *   - commerce_profile (billing) entities linked to the rider's orders
 *
 * ANONYMISED in place (operational records that must outlive the account):
 *   - shh_booking_log entries: actor field nulled, slot/state data kept.
 *     The list builder renders a NULL actor as "Deleted user".
 *   - commerce_order entities: uid → 0, mail → ''. Order number, items,
 *     and timestamps are retained (BAT booking references, booking log
 *     order_id references, credit-pack transaction order_item_id references
 *     all point at order-item integers, not the user, so they survive).
 */
class AccountDeletionManager {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Runs the full purge/anonymisation for a just-deleted user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity that was just deleted. Its uid is still valid as an
   *   integer for querying referencing rows even though the entity is gone.
   */
  public function purgeForUser(UserInterface $account): void {
    $uid = (int) $account->id();

    // Guard: uid 1 and 0 must never be processed — uid 1 is the admin
    // superuser and uid 0 is the anonymous user, neither of which is a
    // rider account and neither of which should lose its data through
    // this path.
    if ($uid <= 1) {
      return;
    }

    $this->deleteMemberships($uid);
    $this->deleteFacilityCredits($uid);
    $this->deleteWebformSubmissions($uid);
    $this->anonymiseBookingLog($uid);
    $this->anonymiseOrders($uid);

    $this->logger->notice(
      'Account deletion (task 0044): purged and anonymised all PII for uid @uid (@name).',
      ['@uid' => $uid, '@name' => $account->getAccountName()],
    );
  }

  /**
   * Hard-deletes all rider membership records for this uid.
   *
   * All statuses are deleted (pending, active, expired, revoked) — the
   * entire eligibility history is personal data and has no purpose once
   * the account is gone.
   */
  protected function deleteMemberships(int $uid): void {
    $storage = $this->entityTypeManager->getStorage('shh_rider_membership');
    $ids = $storage->getQuery()
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->execute();
    if ($ids) {
      $storage->delete($storage->loadMultiple($ids));
    }
  }

  /**
   * Hard-deletes all facility credit balances and their transaction logs.
   *
   * Transactions (the grant/redemption audit trail) are deleted first
   * because they hold a required entity reference to the credit entity;
   * deleting the credit first would leave orphaned transaction rows.
   */
  protected function deleteFacilityCredits(int $uid): void {
    $credit_storage = $this->entityTypeManager->getStorage('shh_facility_credit');
    $credit_ids = $credit_storage->getQuery()
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->execute();
    if (!$credit_ids) {
      return;
    }

    $tx_storage = $this->entityTypeManager->getStorage('shh_facility_credit_transaction');
    $tx_ids = $tx_storage->getQuery()
      ->condition('facility_credit', array_values($credit_ids), 'IN')
      ->accessCheck(FALSE)
      ->execute();
    if ($tx_ids) {
      $tx_storage->delete($tx_storage->loadMultiple($tx_ids));
    }

    $credit_storage->delete($credit_storage->loadMultiple($credit_ids));
  }

  /**
   * Hard-deletes all webform submissions owned by this uid.
   *
   * Covers every form — liability waivers (shh_rider_waiver) and contact
   * messages alike. There is no allow-list: any submission they own is
   * their personal data and must go.
   */
  protected function deleteWebformSubmissions(int $uid): void {
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    $ids = $storage->getQuery()
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->execute();
    if ($ids) {
      $storage->delete($storage->loadMultiple($ids));
    }
  }

  /**
   * Nulls the actor field on all booking log entries for this uid.
   *
   * The operational data — facility, slot times, state transition,
   * order_id reference, notification sent — is preserved for the audit
   * trail. Only the personal identifier (who did it) is removed.
   * The list builder renders a NULL actor as "Deleted user".
   */
  protected function anonymiseBookingLog(int $uid): void {
    $storage = $this->entityTypeManager->getStorage('shh_booking_log');
    $ids = $storage->getQuery()
      ->condition('actor', $uid)
      ->accessCheck(FALSE)
      ->execute();
    foreach ($storage->loadMultiple($ids) as $entry) {
      $entry->set('actor', NULL);
      $entry->save();
    }
  }

  /**
   * Anonymises commerce orders and deletes their billing profiles.
   *
   * Uid is set to 0 (anonymous) and mail is blanked. The billing profile
   * entity (name, address) is detached first, then deleted. Order number,
   * items, state, and timestamps are untouched so BAT booking references
   * and booking-log order_id columns remain meaningful.
   */
  protected function anonymiseOrders(int $uid): void {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $ids = $order_storage->getQuery()
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->execute();
    foreach ($order_storage->loadMultiple($ids) as $order) {
      // Detach the billing profile before saving so it can be deleted
      // separately without leaving a dangling reference on the order.
      $billing_profile = NULL;
      if ($order->hasField('billing_profile') && !$order->get('billing_profile')->isEmpty()) {
        $billing_profile = $order->get('billing_profile')->entity;
        $order->set('billing_profile', NULL);
      }

      $order->set('uid', 0);
      if ($order->hasField('mail')) {
        $order->set('mail', '');
      }
      $order->save();

      if ($billing_profile) {
        $billing_profile->delete();
      }
    }
  }

}
