<?php

namespace Drupal\shh_rider_membership;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\shh_rider_membership\Entity\Membership;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;

/**
 * Computes rider booking eligibility and manages the membership lifecycle.
 *
 * See task 0003 (rider membership/eligibility workflow) in
 * docs/project-management.
 */
class MembershipManager {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected TimeInterface $time,
    protected LoggerInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Whether a rider is currently eligible to book a facility.
   */
  public function isEligible(int $uid): bool {
    $membership = Membership::loadCurrentForRider($uid);
    return $membership !== NULL && $membership->isCurrentlyEligible();
  }

  /**
   * Whether a rider should be offered a self-service waiver-submit link.
   *
   * FALSE for a revoked rider — that's a staff decision to reverse, not
   * something a resubmitted waiver should be able to route around.
   * TRUE for "never submitted" and "expired" (a normal renewal), and also
   * TRUE while pending/active (harmless either way; the caller only
   * shows this when the rider is already known to be ineligible).
   */
  public function canSelfServiceResubmit(int $uid): bool {
    $most_recent = Membership::loadMostRecentForRider($uid);
    return !$most_recent || $most_recent->getStatus() !== Membership::STATUS_REVOKED;
  }

  /**
   * Builds an actionable message for why a rider can't book yet.
   *
   * Or, if they're currently eligible, a simple confirmation — for
   * display on the booking form.
   */
  public function getEligibilityMessage(int $uid): TranslatableMarkup {
    $current = Membership::loadCurrentForRider($uid);

    if ($current && $current->isCurrentlyEligible()) {
      return $this->t('Membership active.');
    }

    if ($current && $current->getStatus() === Membership::STATUS_PENDING) {
      return $this->t('Your liability waiver has been submitted and is awaiting staff approval. You can book once it has been approved.');
    }

    // No current pending/active record — check the most recent one of any
    // status to give a specific reason rather than a generic "submit a
    // waiver" message every rider (even a first-timer) would otherwise see
    // identically.
    $most_recent = Membership::loadMostRecentForRider($uid);
    if ($most_recent && $most_recent->getStatus() === Membership::STATUS_REVOKED) {
      return $this->t('Your membership has been revoked. Please contact Stutteri Hestehøj directly.');
    }
    if ($most_recent && $most_recent->getStatus() === Membership::STATUS_EXPIRED) {
      return $this->t('Your membership has expired. Please submit a new liability waiver to renew it.');
    }

    return $this->t('You need an approved membership (liability waiver on file) before booking a facility. Please submit the waiver form.');
  }

  /**
   * Creates a pending membership from a submitted waiver.
   *
   * Unless the rider already has a pending or active one — avoids
   * duplicate records from a resubmission (e.g. a double-click, or an
   * already-eligible rider filling the form in again out of confusion).
   */
  public function createPendingFromWaiver(WebformSubmissionInterface $submission): ?Membership {
    $uid = (int) $submission->getOwnerId();
    if (!$uid) {
      // Anonymous submission — shouldn't happen given the webform's own
      // access is restricted to authenticated users, but fail closed
      // rather than create an unattributable membership record.
      $this->logger->warning('Ignored an anonymous shh_rider_waiver submission (@sid): a membership record needs an owner.', [
        '@sid' => $submission->id(),
      ]);
      return NULL;
    }

    if (Membership::loadCurrentForRider($uid)) {
      $this->logger->info('Rider @uid already has a pending/active membership; not creating a duplicate from waiver submission @sid.', [
        '@uid' => $uid,
        '@sid' => $submission->id(),
      ]);
      return NULL;
    }

    $membership = Membership::create([
      'uid' => $uid,
      'status' => Membership::STATUS_PENDING,
      'waiver_submission' => $submission->id(),
    ]);
    $membership->save();

    $this->logger->info('Created pending membership @id for rider @uid from waiver submission @sid.', [
      '@id' => $membership->id(),
      '@uid' => $uid,
      '@sid' => $submission->id(),
    ]);

    return $membership;
  }

  /**
   * Approves a membership: sets it active and stamps approval/expiry.
   *
   * Only stamps the approval time and computed expiry date if not
   * already set, so re-saving an already-approved record doesn't push
   * the expiry date out again.
   */
  public function approve(Membership $membership): void {
    $now = $this->time->getRequestTime();
    if (empty($membership->get('approved')->value)) {
      $membership->set('approved', $now);
    }
    if (empty($membership->get('expires')->value)) {
      $validity_days = $this->configFactory->get('shh_rider_membership.settings')->get('validity_days') ?? 365;
      $membership->set('expires', $now + ($validity_days * 86400));
    }
    $membership->set('status', Membership::STATUS_ACTIVE);
  }

  /**
   * Sweeps all active memberships past their expiry date to "expired".
   *
   * Called from hook_cron() — deliberately flips the stored status rather
   * than only computing eligibility dynamically at check-time, so staff
   * reviewing the admin list see an accurate, current status without
   * having to cross-reference the expiry date themselves.
   *
   * @return int
   *   The number of memberships expired.
   */
  public function autoExpireStale(): int {
    $storage = $this->entityTypeManager->getStorage('shh_rider_membership');
    $ids = $storage->getQuery()
      ->condition('status', Membership::STATUS_ACTIVE)
      ->condition('expires', $this->time->getRequestTime(), '<=')
      ->accessCheck(FALSE)
      ->execute();
    if (!$ids) {
      return 0;
    }

    $memberships = $storage->loadMultiple($ids);
    foreach ($memberships as $membership) {
      /** @var \Drupal\shh_rider_membership\Entity\Membership $membership */
      $membership->set('status', Membership::STATUS_EXPIRED);
      $membership->save();
    }

    $this->logger->info('Auto-expired @count membership(s) past their expiry date.', ['@count' => count($memberships)]);
    return count($memberships);
  }

}
