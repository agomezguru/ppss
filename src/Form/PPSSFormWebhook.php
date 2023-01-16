<?php

/**
 * @file
 * Content the settings for administering the PPSS Webhooks form.
 */

namespace Drupal\ppss\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to configure webhook settings.
 */
class PPSSFormWebhook extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'ppss_webhook.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ppss_webhook_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Define a field used to capture the stored auth token value.
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => t('Authorization Token'),
      '#required' => TRUE,
      '#default_value' => $config->get('token'),
      '#description' => t('The expected value in the Authorization header, often an API key or similar secret.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Save the submitted token value.
      ->set('token', $form_state->getValue('token'))
      ->save();

    $this->messenger()->addMessage($this->t('The configuration PPSS webook options have been saved.'));
    parent::submitForm($form, $form_state);
  }

}
