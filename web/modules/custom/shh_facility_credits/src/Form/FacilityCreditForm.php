<?php

namespace Drupal\shh_facility_credits\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for a Facility Credit balance — lets staff manually adjust a
 * rider's remaining credits (e.g. a goodwill credit, or correcting an
 * error), per the client's request for basic admin visibility/management.
 */
class FacilityCreditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('Saved credit balance for %rider / %facility.', [
      '%rider' => $this->entity->get('uid')->entity?->label() ?? $this->t('(unknown rider)'),
      '%facility' => $this->entity->get('facility')->entity?->label() ?? $this->t('(unknown facility)'),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
