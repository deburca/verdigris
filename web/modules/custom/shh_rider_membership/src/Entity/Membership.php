<?php

namespace Drupal\shh_rider_membership\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Rider Membership entity: one rider's eligibility record.
 *
 * Lifecycle: pending (waiver just submitted) -> active (staff approved,
 * expires set) -> expired (validity window passed, flipped automatically
 * by cron) or revoked (staff action, e.g. a policy violation). A rider can
 * submit a new waiver after expiry or revocation, creating a fresh entity
 * — memberships are not edited back to "pending", so there's a full
 * history of every waiver a rider has ever submitted, not just their
 * current status.
 *
 * Uses plain base fields, not the Field API — same rationale as
 * shh_facility_credits' FacilityCredit entity (this project's established
 * pattern for simple ledger/record-style data, avoiding the repeated
 * Field API config-schema/cache pitfalls hit in tasks 0011/0016/0017).
 *
 * @ContentEntityType(
 *   id = "shh_rider_membership",
 *   label = @Translation("Rider Membership"),
 *   label_collection = @Translation("Rider Memberships"),
 *   handlers = {
 *     "list_builder" = "Drupal\shh_rider_membership\ListBuilder\MembershipListBuilder",
 *     "form" = {
 *       "default" = "Drupal\shh_rider_membership\Form\MembershipForm",
 *       "add" = "Drupal\shh_rider_membership\Form\MembershipForm",
 *       "edit" = "Drupal\shh_rider_membership\Form\MembershipForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shh_rider_membership",
 *   admin_permission = "administer rider memberships",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/people/rider-memberships",
 *     "add-form" = "/admin/people/rider-memberships/add",
 *     "edit-form" = "/admin/people/rider-memberships/{shh_rider_membership}/edit",
 *     "delete-form" = "/admin/people/rider-memberships/{shh_rider_membership}/delete",
 *   },
 * )
 */
class Membership extends ContentEntityBase {

  use EntityChangedTrait;

  const STATUS_PENDING = 'pending';
  const STATUS_ACTIVE = 'active';
  const STATUS_EXPIRED = 'expired';
  const STATUS_REVOKED = 'revoked';

  /**
   * Finds a rider's current pending-or-active membership, if any.
   *
   * There is deliberately no uniqueness constraint enforced at the storage
   * level (plain base fields, no Field API) — MembershipManager is
   * responsible for not creating a second pending/active record while one
   * already exists; this loader reflects that same "current" definition.
   */
  public static function loadCurrentForRider(int $uid): ?self {
    $storage = \Drupal::entityTypeManager()->getStorage('shh_rider_membership');
    $ids = $storage->getQuery()
      ->condition('uid', $uid)
      ->condition('status', [self::STATUS_PENDING, self::STATUS_ACTIVE], 'IN')
      ->sort('id', 'DESC')
      ->accessCheck(FALSE)
      ->execute();
    if (!$ids) {
      return NULL;
    }
    $result = $storage->load(reset($ids));
    return $result instanceof self ? $result : NULL;
  }

  /**
   * Finds a rider's most recent membership of any status, if any.
   *
   * Used to give a specific message (expired vs. revoked vs. never
   * submitted) when there's no current pending/active record.
   */
  public static function loadMostRecentForRider(int $uid): ?self {
    $storage = \Drupal::entityTypeManager()->getStorage('shh_rider_membership');
    $ids = $storage->getQuery()
      ->condition('uid', $uid)
      ->sort('id', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    if (!$ids) {
      return NULL;
    }
    $result = $storage->load(reset($ids));
    return $result instanceof self ? $result : NULL;
  }

  /**
   * Returns the rider (user) ID this membership belongs to.
   */
  public function getRiderId(): int {
    return (int) $this->get('uid')->target_id;
  }

  /**
   * Returns the current status (pending/active/expired/revoked).
   */
  public function getStatus(): string {
    return (string) $this->get('status')->value;
  }

  /**
   * Whether this specific record currently grants booking eligibility.
   *
   * Distinct from "status === active" alone: an active record whose
   * expiry has passed but hasn't been swept by cron yet must not be
   * treated as eligible just because the stale status hasn't caught up.
   */
  public function isCurrentlyEligible(): bool {
    if ($this->getStatus() !== self::STATUS_ACTIVE) {
      return FALSE;
    }
    $expires = $this->get('expires')->value;
    return empty($expires) || (int) $expires > \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rider'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'author'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_REVOKED => 'Revoked',
      ])
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'list_default'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['waiver_submission'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Waiver submission'))
      ->setSetting('target_type', 'webform_submission')
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'entity_reference_label'])
      ->setDisplayConfigurable('view', TRUE);

    // Deliberately not shown on the edit form at all (not just left
    // optional): MembershipManager::approve() is the only thing that
    // should ever set these two fields. An empty datetime_timestamp
    // widget does not submit NULL — it silently defaults to the current
    // request time — which defeated approve()'s "only set if empty"
    // logic the first time this was built (both fields ended up equal
    // to the save timestamp instead of expires being approved +
    // validity_days). Kept view-only so staff can still see the dates.
    $fields['approved'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Approved on'))
      ->setDescription(t('Set automatically when status is changed to Active, if not already set.'))
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'timestamp'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires on'))
      ->setDescription(t('Set automatically (approval date + the configured validity period) when status is changed to Active, if not already set.'))
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'timestamp'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Staff notes'))
      ->setDescription(t('E.g. reason for revocation. Not shown to the rider.'))
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'type' => 'basic_string'])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Submitted'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
