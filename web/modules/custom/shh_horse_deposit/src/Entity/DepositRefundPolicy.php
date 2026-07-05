<?php

namespace Drupal\shh_horse_deposit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Deposit refund policy config entity.
 *
 * Deliberately a *separate* entity type from 0015's `cancellation_policy` —
 * that one is slot-time-based ("hours before the booking starts"); a horse
 * deposit isn't tied to a slot at all. This one is a cooling-off window
 * measured from the deposit *payment* date: refundable within N days of
 * paying the deposit, non-refundable after (opposite temporal direction
 * from the booking policy — here, *more time elapsed* means *less*
 * refundable, not less time-to-event).
 *
 * @ConfigEntityType(
 *   id = "deposit_refund_policy",
 *   label = @Translation("Deposit refund policy"),
 *   label_collection = @Translation("Deposit refund policies"),
 *   label_singular = @Translation("deposit refund policy"),
 *   label_plural = @Translation("deposit refund policies"),
 *   label_count = @PluralTranslation(
 *     singular = "@count deposit refund policy",
 *     plural = "@count deposit refund policies",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\shh_horse_deposit\DepositRefundPolicyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\shh_horse_deposit\Form\DepositRefundPolicyForm",
 *       "edit" = "Drupal\shh_horse_deposit\Form\DepositRefundPolicyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "deposit_refund_policy",
 *   admin_permission = "administer deposit refund policies",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "refund_window_days",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/deposit-refund-policies/add",
 *     "edit-form" = "/admin/commerce/config/deposit-refund-policies/manage/{deposit_refund_policy}",
 *     "delete-form" = "/admin/commerce/config/deposit-refund-policies/manage/{deposit_refund_policy}/delete",
 *     "collection" = "/admin/commerce/config/deposit-refund-policies",
 *   },
 * )
 */
class DepositRefundPolicy extends ConfigEntityBase {

  /**
   * The policy ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The policy label.
   *
   * @var string
   */
  protected $label;

  /**
   * The refund window, in days since the deposit was paid.
   *
   * Within this window: full refund authorized, horse released back to
   * available. Outside this window: refund denied — but unlike a facility
   * booking hold, the horse is still released back to `available`
   * regardless (the seller wants it back on the market immediately if the
   * buyer isn't completing the purchase; there's no "disincentivize late
   * cancellation of scarce inventory" reason to keep it off-market the way
   * there is for an hourly slot).
   *
   * @var int
   */
  protected $refund_window_days = 7;

  /**
   * Gets the refund window in days.
   */
  public function getRefundWindowDays(): int {
    return (int) $this->refund_window_days;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'refund_window_days' => 7,
    ];
  }

}
