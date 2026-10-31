<?php

namespace Drupal\hvg_landing\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\webform\WebformSubmissionForm;

/**
 * Puts the captcha above the submit button on the public webforms.
 */
final class CaptchaPlacementHooks {

  /**
   * The webforms the captcha is placed on.
   */
  private const WEBFORMS = ['contact_form', 'interest'];

  /**
   * Moves the captcha to just before the webform's actions row.
   *
   * Captcha only recognises an "actions" element of type "actions", and a
   * webform's is of type "webform_actions", so it appends the captcha after the
   * Submit button. A placement map cannot fix this: a webform embedded in a
   * page (the landing page's block) has a form ID of its own, carrying the
   * page's ID, so it is done here, last, on whichever form ID the webform has.
   * The row is at the top level of the form, or among its elements when the
   * webform defines its own (to relabel the button).
   */
  #[Hook('form_alter', order: Order::Last)]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $webform_id = $form_state->getFormObject() instanceof WebformSubmissionForm
      ? $form_state->getFormObject()->getEntity()->getWebform()->id()
      : NULL;
    if (!in_array($webform_id, self::WEBFORMS, TRUE) || !isset($form['captcha'])) {
      return;
    }
    if (isset($form['elements']['actions'])) {
      // Insert by position: the elements carry no weights to sort by.
      $captcha = $form['captcha'];
      unset($form['captcha'], $captcha['#weight']);
      $elements = [];
      foreach ($form['elements'] as $key => $element) {
        if ($key === 'actions') {
          $elements['captcha'] = $captcha;
        }
        $elements[$key] = $element;
      }
      $form['elements'] = $elements;
    }
    elseif (isset($form['actions'])) {
      // Captcha weights itself just below the footer; a webform's actions row
      // has none of its own until the form is built, so rank it just above.
      $captcha_weight = $form['captcha']['#weight'] ?? 0;
      if (($form['actions']['#weight'] ?? 0) <= $captcha_weight) {
        $form['actions']['#weight'] = $captcha_weight + 0.05;
      }
    }
  }

}
