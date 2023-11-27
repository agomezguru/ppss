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


class PPSSFormSettingsPaypal extends ConfigFormBase
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
    // The settings needed was configured inside ppss.settings.yml file.
    $config = $this->config('ppss.settings');

    $form['init_message'] = [
      '#markup' => $this->t('**Before configure this module always execute "composer require
        paypal/rest-api-sdk-php:*" on command line'),
    ];

    // Start fields configuration.
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields names settings'),
      '#open' => FALSE,
    ];

    $form['fields']['description'] = [
      '#markup' => $this->t('You always need to add one field of this type in your
        custom node type.'),
    ];

    $form['fields']['field_price'] = [
      '#title' => $this->t('Price field name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('field_price'),
      '#description' => $this->t("What is the internal Drupal system name of the
        field to store prices. Example: 'field_nvi_price'."),
      '#required' => true,
    ];

    $form['fields']['field_frequency'] = [
      '#title' => $this->t('Frecuency field name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('field_frequency'),
      '#description' => $this->t("What is the internal Drupal system name of the
        field to store frequency in a recurring subscription agreement.
        Example: 'field_nvi_frecuency'."),
      '#required' => true,
    ];

    $form['fields']['field_frequency_interval'] = [
      '#title' => $this->t('Frequency interval field name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('field_frequency_interval'),
      '#description' => $this->t("What is the internal Drupal system name of the
        field to save interval between charges. Example: 'field_frequency_interval'."),
      '#required' => true,
    ];

    $form['fields']['field_description'] = [
      '#title' => $this->t('Description field name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('field_description'),
      '#description' => $this->t("What is the internal Drupal system name of the
        field to store product or service description. Example: 'field_nvi_describe'."),
      '#required' => true,
    ];

    $form['fields']['field_role'] = [
      '#title' => $this->t('User role field name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('field_role'),
      '#description' => $this->t("What is the internal Drupal system name of the
        field to store new user role assigned after purchased a plan. Example: 'field_nvi_role'."),
    ];

    $form['fields']['field_sku'] = [
      '#title' => $this->t('SKU field name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('field_sku'),
      '#description' => $this->t("What is the internal Drupal system name of the
        field to store product or service SKU. Example: 'field_nvi_sku'."),
    ];

    // Start PayPal general settings.
    $form['paypal_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('PayPal Settings'),
      '#open' => true,
    ];

    $form['paypal_settings']['description'] = [
      '#markup' => $this->t('Please refer to @link for your settings.', [
        '@link' => Link::fromTextAndUrl($this->t('PayPal developer'),
          Url::fromUri('https://developer.paypal.com/developer/applications/', [
            'attributes' => [
              'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
    ];

    $form['paypal_settings']['client_id'] = [
      '#type' => 'textfield',
      '#title' => t('PayPal Client ID'),
      '#size' => 100,
      '#default_value' => $config->get('client_id'),
      '#description' => t("Your PayPal client id. It should be similar to:
        AYSq3RDGsmBLJE-otTkBtM-jBRd1TCQwFf9RGfwddNXWz0uFU9ztymylOhRS"),
      '#required' => true,
    ];

    $form['paypal_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => t('PayPal Client Secret'),
      '#size' => 100,
      '#default_value' => $config->get('client_secret'),
      '#description' => t("Your PayPal client secret. If you don't know, please visiting
        https://developer.paypal.com/developer/applications/ for help."),
      '#required' => true,
    ];

    $form['paypal_settings']['sandbox_mode'] = [
      '#title' => $this->t('Enable SandBox Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('sandbox_mode'),
      '#description' => $this->t('Allways use the PayPal sandbox virtual testing
        environment before go to production.'),
    ];

    // Start payment details.
    $form['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Payment configuration details'),
      '#open' => FALSE,
    ];

    $form['details']['currency_code'] = [
      '#title' => $this->t('Currency'),
      '#type' => 'textfield',
      '#default_value' => $config->get('currency_code'),
      '#description' => $this->t('ISO 4217 @link.', [
        '@link' => Link::fromTextAndUrl($this->t('Currency Codes'),
          Url::fromUri('https://www.xe.com/iso4217.php', [
            'attributes' => [
              'onclick' => "target='_blank'",
          ],
        ]))->toString(),
      ]),
      '#required' => true,
    ];

    $form['details']['tax'] = [
      '#title' => $this->t('Tax'),
      '#type' => 'textfield',
      '#default_value' => $config->get('tax'),
      '#description' => $this->t('Default tax to charge in all transactions.'),
      '#required' => true,
    ];

    // Webhooks details.
    $form['webhooks'] = [
      '#type' => 'details',
      '#title' => $this->t('Webhook configuration'),
      '#open' => false,
    ];

    $form['webhooks']['url_webhook'] = [
      '#type' => 'textfield',
      '#disabled' => true,
      '#title' => $this->t('URL'),
      '#description' => $this->t('Endpoint URL.'),
      '#default_value' => Url::fromRoute('ppss.webhook_listener')->setAbsolute()->toString(),
    ];

    $form['webhooks']['webhook_id'] = [
      '#type' => 'textfield',
      '#title' => t('Webhook ID'),
      '#required' => true,
      '#default_value' => $config->get('webhook_id'),
      '#description' => t('The ID of the webhook resource for the destination URL 
        to which PayPal delivers the event notification.'),
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
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config_keys = [
      'client_id', 'client_secret', 'sandbox_mode', 'field_price',
      'field_description', 'field_role', 'field_sku', 'currency_code',
      'field_frequency', 'field_frequency_interval', 'tax',
    ];
    $ppss_config = $this->config('ppss.settings');
    foreach ($config_keys as $config_key) {
      if ($form_state->hasValue($config_key)) {
        $ppss_config->set($config_key, $form_state->getValue($config_key));
      }
    $ppss_config->save();
    }
    $this->messenger()->addMessage($this->t('The configuration PPSS general options have been saved.'));
    parent::submitForm($form, $form_state);
  }
}
