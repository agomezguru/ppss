<?php

/**
 * @file
 * Content the settings for administering the PPSS form.
 */

namespace Drupal\ppss\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;


class PPSSFormSettings extends ConfigFormBase
{
  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs an AutoParagraphForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PathValidatorInterface $path_validator)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('path.validator'),
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    // Unique ID of the form.
    return 'ppss_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames()
  {
    return [
      'ppss.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Get the internal node type machine name.
    $existingContentTypeOptions = $this->getExistingContentTypes();
    $paymentGateways = ['PayPal' => 'PayPal', 'Stripe' => 'Stripe'];

    // The settings needed was configured inside ppss.settings.yml file.
    $config = $this->config('ppss.settings');

    // General settings.
    $form['allowed_gateways'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Subscriptions can be pay with all selected payments gateways.'),
      '#options' => $paymentGateways,
      '#default_value' => $config->get('allowed_gateways'),
      '#description' => $this->t('Select all the payments gateways you like to use to enable for.'),
      '#required' => true,
    ];

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The content types to enable PPSS button for'),
      '#default_value' => $config->get('content_types'),
      '#options' => $existingContentTypeOptions,
      '#empty_option' => $this->t('- Select an existing content type -'),
      '#description' => $this->t('On the specified node types, an PPSS button
        will be available and can be shown to make purchases.'),
      '#required' => true,
    ];
    
    $form['return_url'] = [
      '#type' => 'details',
      '#title' => $this->t('URL of return pages'),
      '#open' => TRUE,
    ];

    $form['return_url']['success_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success URL'),
      '#default_value' => $config->get('success_url'),
      '#description' => $this->t('What is the return URL when a new successful sale was made? Specify a relative URL.'),
      '#size' => 40,
      '#required' => true,
    ];

    $form['return_url']['error_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error URL'),
      '#default_value' => $config->get('error_url'),
      '#description' => $this->t('What is the return URL when a sale fails? Specify a relative URL.
        Leave blank to display the default front page.'),
      '#size' => 40,
      '#required' => true,
    ];

    $form['last_message'] = [
      '#markup' => $this->t('Remember always flush the cache after save this
        configuration form.'),
    ];

    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate return URL pages path.
    //dump(($form_state->getValue('success_url')))
    if (!$this->pathValidator->isValid($form_state->getValue('success_url'))) {
      $form_state->setErrorByName('success_url', $this->t("Either the path '%path' is invalid or
        you do not have access to it.", ['%path' => $form_state->getValue('success_url')]));
    }
    if (!$this->pathValidator->isValid($form_state->getValue('error_url'))) {
      $form_state->setErrorByName('error_url', $this->t("Either the path '%path' is invalid or
        you do not have access to it.", ['%path' => $form_state->getValue('error_url')]));
    }
    parent::validateForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config_keys = [
      'content_types', 'allowed_gateways','success_url', 'error_url',
    ];
    $ppss_config = $this->config('ppss.settings');
    foreach ($config_keys as $config_key) {
      if ($form_state->hasValue($config_key)) {

        if ($config_key == 'allowed_gateways' || $config_key == 'content_types') {
          $ppss_config->set($config_key, array_filter($form_state->getValue(
            $config_key
          )));
        } else {
          $ppss_config->set($config_key, $form_state->getValue($config_key));
        }
      }
    $ppss_config->save();
    }
    $this->messenger()->addMessage($this->t('The configuration PPSS general options have been saved.'));
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a list of all the content types currently installed.
   *
   * @return array
   *   An array of content types.
   */
  public function getExistingContentTypes()
  {
    $types = [];
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }
    return $types;
  }
}
