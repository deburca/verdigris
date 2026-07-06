<?php

namespace Drupal\shh_facility_credits;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\shh_facility_credits\Entity\FacilityCredit;
use Drupal\shh_facility_credits\Entity\FacilityCreditTransaction;
use Psr\Log\LoggerInterface;

/**
 * Grants and redeems facility credits, and passes a redemption request from
 * the reservation form's submit handlers to the order-item-insert hook that
 * actually applies it (both run within the same request).
 */
class FacilityCreditManager {

  /**
   * Node IDs the current request has been asked to redeem a credit for,
   * set by the reservation form's own (early) submit handler and consumed
   * by hook_commerce_order_item_insert() moments later in the same
   * request — see shh_facility_credits.module.
   *
   * @var array<int, bool>
   */
  protected array $pendingRedemptions = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  public function getBalance(int $uid, int $facility_nid): int {
    $credit = FacilityCredit::loadForRiderAndFacility($uid, $facility_nid);
    return $credit ? $credit->getCreditsRemaining() : 0;
  }

  /**
   * Grants credits to a rider's balance for a facility (find-or-create).
   */
  public function grantCredits(int $uid, int $facility_nid, int $amount, ?OrderItemInterface $order_item = NULL): void {
    $credit = FacilityCredit::loadForRiderAndFacility($uid, $facility_nid);
    if (!$credit) {
      $credit = $this->entityTypeManager->getStorage('shh_facility_credit')->create([
        'uid' => $uid,
        'facility' => $facility_nid,
      ]);
    }
    $credit->grant($amount);
    $credit->save();

    FacilityCreditTransaction::create([
      'facility_credit' => $credit->id(),
      'delta' => $amount,
      'order_item_id' => $order_item?->id(),
      'note' => 'Credit pack purchase',
    ])->save();

    $this->logger->info('Granted @amount credit(s) to uid @uid for facility @nid (order item @item).', [
      '@amount' => $amount,
      '@uid' => $uid,
      '@nid' => $facility_nid,
      '@item' => $order_item?->id() ?? 'n/a',
    ]);
  }

  /**
   * Attempts to redeem one credit. Returns FALSE if none remain.
   */
  public function redeemOne(int $uid, int $facility_nid, ?OrderItemInterface $order_item = NULL): bool {
    $credit = FacilityCredit::loadForRiderAndFacility($uid, $facility_nid);
    if (!$credit || !$credit->redeemOne()) {
      return FALSE;
    }
    $credit->save();

    FacilityCreditTransaction::create([
      'facility_credit' => $credit->id(),
      'delta' => -1,
      'order_item_id' => $order_item?->id(),
      'note' => 'Booking redemption',
    ])->save();

    $this->logger->info('Redeemed 1 credit for uid @uid, facility @nid (order item @item). @remaining remaining.', [
      '@uid' => $uid,
      '@nid' => $facility_nid,
      '@item' => $order_item?->id() ?? 'n/a',
      '@remaining' => $credit->getCreditsRemaining(),
    ]);

    return TRUE;
  }

  /**
   * Marks that the next 'bee' order item created for this facility node,
   * within this same request, should be redeemed against a credit rather
   * than paid for.
   */
  public function markPendingRedemption(NodeInterface $node): void {
    $this->pendingRedemptions[$node->id()] = TRUE;
  }

  /**
   * Consumes (checks and clears) a pending redemption flag for this node.
   */
  public function consumePendingRedemption(NodeInterface $node): bool {
    if (!empty($this->pendingRedemptions[$node->id()])) {
      unset($this->pendingRedemptions[$node->id()]);
      return TRUE;
    }
    return FALSE;
  }

}
