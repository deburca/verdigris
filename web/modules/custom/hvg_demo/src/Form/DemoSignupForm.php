<?php

namespace Drupal\hvg_demo\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets a visitor create a short-lived demo account with a chosen password.
 */
final class DemoSignupForm extends FormBase implements ContainerInjectionInterface {

  /**
   * Flood event name.
   */
  private const FLOOD_EVENT = 'hvg_demo.signup';

  /**
   * Constructs the form.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FloodInterface $flood,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('flood'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'hvg_demo_signup';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $days = (int) $this->config('hvg_demo.settings')->get('lifetime_days');
    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Pick a username and password to try HiveLog with your own private apiaries. Demo accounts and everything you enter are deleted automatically after @days days. No email address is collected, so a forgotten password cannot be recovered; just create a new demo account.', ['@days' => $days]) . '</p>',
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#maxlength' => 60,
      '#attributes' => ['autocomplete' => 'username'],
    ];
    $form['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create demo account'),
    ];
    if (function_exists('honeypot_add_form_protection')) {
      honeypot_add_form_protection($form, $form_state, [
        'honeypot',
        'time_restriction',
      ]);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $limit = (int) $this->config('hvg_demo.settings')->get('signups_per_hour');
    if (!$this->flood->isAllowed(self::FLOOD_EVENT, $limit, 3600)) {
      $form_state->setErrorByName('name', $this->t('Too many demo accounts were created from your address. Please try again later.'));
      return;
    }
    $name = trim($form_state->getValue('name'));
    if ($this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name])) {
      $form_state->setErrorByName('name', $this->t('That username is taken.'));
    }
    if (strlen((string) $form_state->getValue('pass')) < 8) {
      $form_state->setErrorByName('pass', $this->t('The password must be at least 8 characters.'));
    }
    if (!$form_state->getErrors()) {
      $account = $this->entityTypeManager->getStorage('user')->create([
        'name' => $name,
        'pass' => $form_state->getValue('pass'),
        'status' => 1,
        'roles' => ['demo', 'hivelog_user'],
      ]);
      foreach ($account->validate() as $violation) {
        if ($violation->getPropertyPath() === 'name') {
          $form_state->setErrorByName('name', $violation->getMessage());
        }
      }
      $form_state->set('account', $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $account = $form_state->get('account');
    $account->save();
    $this->flood->register(self::FLOOD_EVENT, 3600);
    user_login_finalize($account);
    $form_state->setRedirectUrl(Url::fromUserInput('/hivelog'));
  }

}
