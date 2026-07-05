<?php

namespace Drupal\shh_cancellation_policy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Cancellation policy config entity.
 *
 * Implements docs/project-management/decisions/0015-cancellation-refund-policy.md:
 * "Add a `cancellation_policy` config entity referenced by Bookable Facility
 * ... Enforce via a Commerce order-cancel workflow that checks policy +
 * time-to-slot before authorizing refund."
 *
 * @ConfigEntityType(
 *   id = "cancellation_policy",
 *   label = @Translation("Cancellation policy"),
 *   label_collection = @Translation("Cancellation policies"),
 *   label_singular = @Translation("cancellation policy"),
 *   label_plural = @Translation("cancellation policies"),
 *   label_count = @PluralTranslation(
 *     singular = "@count cancellation policy",
 *     plural = "@count cancellation policies",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\shh_cancellation_policy\CancellationPolicyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\shh_cancellation_policy\Form\CancellationPolicyForm",
 *       "edit" = "Drupal\shh_cancellation_policy\Form\CancellationPolicyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "cancellation_policy",
 *   admin_permission = "administer cancellation policies",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "refund_window_hours",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/cancellation-policies/add",
 *     "edit-form" = "/admin/commerce/config/cancellation-policies/manage/{cancellation_policy}",
 *     "delete-form" = "/admin/commerce/config/cancellation-policies/manage/{cancellation_policy}/delete",
 *     "collection" = "/admin/commerce/config/cancellation-policies",
 *   },
 * )
 */
class CancellationPolicy extends ConfigEntityBase {

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
   * The refund window, in hours before the booking's start time.
   *
   * Outside this window: full refund authorized, slot released. Inside this
   * window: refund denied, slot stays booked (see decision 0015's
   * implementation note — this specific "no release either" behavior is
   * flagged as worth confirming with the business, not an assumption to
   * treat as settled).
   *
   * @var int
   */
  protected $refund_window_hours = 24;

  /**
   * Gets the refund window in hours.
   *
   * @return int
   *   The refund window, in hours before the booking's start time.
   */
  public function getRefundWindowHours(): int {
    return (int) $this->refund_window_hours;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'refund_window_hours' => 24,
    ];
  }

}
