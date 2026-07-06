<?php

namespace Drupal\shh_rider_membership\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shh_rider_membership\Entity\Membership;
use Drupal\shh_rider_membership\MembershipManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Staff form to review a waiver and approve/revoke a rider's membership.
 *
 * A plain status dropdown is enough UI for the client's needs here —
 * setting status to Active runs it through MembershipManager::approve()
 * so the approval timestamp and computed expiry date are stamped
 * automatically instead of requiring staff to calculate and enter them
 * by hand.
 */
class MembershipForm extends ContentEntityForm {

  /**
   * The membership manager.
   *
   * Deliberately not added as a constructor-promoted parameter:
   * ContentEntityForm's own constructor signature varies across core
   * minor versions, so overriding __construct() here risks silently
   * breaking on upgrade. Setting the extra service directly on the
   * instance returned by parent::create() avoids depending on that
   * signature at all.
   */
  protected MembershipManager $membershipManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->membershipManager = $container->get('shh_rider_membership.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\shh_rider_membership\Entity\Membership $entity */
    $entity = $this->entity;

    if ($entity->getStatus() === Membership::STATUS_ACTIVE) {
      // Runs whether this save is what just changed the status to Active,
      // or a later edit to an already-active record — approve() only
      // stamps approved/expires the first time (see its own docblock), so
      // this is safe to call unconditionally here.
      $this->membershipManager->approve($entity);
    }

    $result = parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('Saved membership for %rider (status: %status).', [
      '%rider' => $entity->get('uid')->entity?->label() ?? $this->t('(unknown rider)'),
      '%status' => $entity->getStatus(),
    ]));
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
