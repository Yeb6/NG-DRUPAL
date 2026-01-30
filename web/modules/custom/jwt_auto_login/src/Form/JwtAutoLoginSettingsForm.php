<?php

namespace Drupal\jwt_auto_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for JWT Auto Login settings.
 *
 * Allows site administrators to define role-to-site mappings for auto-login.
 */
class JwtAutoLoginSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jwt_auto_login.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jwt_auto_login_settings_form';
  }

  /**
   * Builds the configuration form for JWT Auto Login settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jwt_auto_login.settings');

    $form['role_mappings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Role to Site Mapping (JSON)'),
      '#default_value' => $config->get('role_mappings') ?? '',
      '#description' => $this->t('Enter JSON like: {"hq_admin": {"target_role": "market_admin", "sites": {"http://ews.usapc": "USAPC Market", "http://ews.usavan": "USAVAN Market"}}}'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Handles the form submission and saves the role-to-site mapping.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $raw_input = $form_state->getValue('role_mappings');

    json_decode($raw_input);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->messenger()->addError($this->t('Invalid JSON format.'));
      return;
    }

    $this->config('jwt_auto_login.settings')
      ->set('role_mappings', $raw_input)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
