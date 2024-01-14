<?php

namespace Drupal\entrasync\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the EntraSync settings form.
 *
 * @package Drupal\entrasync\Form
 */
class SyncSettingsForm extends ConfigFormBase {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entrasync.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entrasync_sync_settings';
  }

  /**
   * Constructs a new SyncSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entrasync.settings');

    // Start of general settings details
    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General setting'),
    ];

    // Start of Entra settings details
    $form['entrauser_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings for incoming users from Entra'),
    ];

    // Start of Drupal settings details
    $form['drupaluser_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings for remaining Drupal users'),
      '#description' => $this->t('Decide what to do with users that are not in Entra, but still in Drupal.
                                  This could be users that are orphaned, or they could be legit user that should be
                                  there. User 1 is excluded'),
      '#description_display' => 'above',
    ];

    $form['general_settings']['retrieve_on_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Import new users on cron'),
      '#default_value' => $config->get('retrieve_on_cron', FALSE),
      '#description' => $this->t('Enable to check for new users each time cron runs. If disabled you will need
                                  to do a <a href=":link">manual syncronisation</a>, or invoke it via drush.',
                                  [':link' => Url::fromRoute('entrasync.manual_sync')->toString()]),
    ];

    // Add a select element for entity type to map to, per now only user is supported.
    $entity_options = ['user' => 'User', 'node' => 'Node'];

    $form['entrauser_settings']['entrauser_entities'] = [
      '#type' => 'radios',
      '#title' => $this->t('Map Entra users to Drupal entity'),
      '#options' => $entity_options,
      '#default_value' => 'user',
      '#multiple' => FALSE,
      '#description' => $this->t('Select which Drupal entities the new users should be added to (per now only user is supported)'),
      '#attributes' => ['disabled' => ['node']],
    ];

    // Info about mandatory mappings
    $mandatory_mappings_message = '<p><strong>Mandatory field mappings</strong>

    <p>The <em>User Principal Name (UPN)</em> is mapped to the
    <em>Drupal user name</em>. This is the id from Entra, and changing it
    will impact the functionality of the module.</p>

    <p>The <em>email</em> field from Entra is often the same as the UPS,
    and is mapped to the Drupal email field.</p>';

    $form['entrauser_settings']['mandatory_mappings_html'] = [
    '#type' => 'markup',
    '#markup' => $this->t($mandatory_mappings_message),
    ];


    // Add field mappings, to map Entra data to Drupal fields of selected entity
    // Fields coming from Entra:
    $entra_fields = [
      // 'userprincipalname' => $this->t('User Principal Name'),
      'displayName' => $this->t('Display Name'),
      'givenname' => $this->t('Given Name'),
      'surname' => $this->t('Surname'),
      'businessphones' => $this->t('Business Phones'),
      'mobilephone' => $this->t('Mobile Phone'),
      'department' => $this->t('Department'),
      // 'email' => $this->t('Email'),
      'jobtitle' => $this->t('Job Title'),
      'officelocation' => $this->t('Office Location'),
      'id' => $this->t('ID')
    ];

    // Fetch Drupal user fields, including custom fields
    $user_fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');

    /**
      * @todo Might be better to go for a whitelist here, in case other modules add fields
      * to the user? Or we might want to support mapping also to that?
    */
    // Exclude certain fields like 'uuid', and include only custom fields.
    $exclude_user_fields = [
      'name',
      'mail',
      'uuid',
      'uid',
      'langcode',
      'created',
      'changed',
      'access',
      'login',
      'status',
      'timezone',
      'roles',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode',
      'init',
      'pass',
      'timezone',
      'default_langcode',
      'path',
    ];

    // Initiate options array
    $drupal_user_field_options = [];

    // Filter the options so we get only fields we want
    foreach ($user_fields as $field_name => $field_definition) {
      if (!in_array($field_name, $exclude_user_fields) && $field_definition->getType() != 'entity_reference') {
        $drupal_user_field_options[$field_name] = $field_definition->getLabel();
      }
    }

    /**
     * @todo Nested form structure not working, need to figure out..
     *
     */
    // Create the actual mapping fields
    foreach ($entra_fields as $entra_field_key => $entra_field_label) {
      $form['entrauser_settings']['user_field_to_' . $entra_field_key] = [
        '#type' => 'select',
        '#title' => $this->t('Map Entra field: @entra_field', ['@entra_field' => $entra_field_label]),
        '#options' => $drupal_user_field_options,
        '#empty_option' => $this->t('- Select a Drupal field -'),
      ];
    }

    // Add a select element for roles to modify on import.
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($roles['authenticated']);
    unset($roles['anonymous']);

    $role_options = [];
    foreach ($roles as $role_id => $role) {
      $role_options[$role_id] = $role->label();
    };

    $form['entrauser_settings']['modify_entrauser_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Modify roles on Entra users'),
      '#options' => $role_options,
      '#default_value' => $config->get('modify_entrauser_roles'),
      '#multiple' => TRUE,
      '#description' => $this->t('Select which roles the new users should or should not have'),
    ];

    // Add a select element to chose initial state of the imported user.
    $user_status_options = ['blocked' => 'Blocked', 'active' => 'Active'];

    $form['entrauser_settings']['entrauser_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Initial state of imported user'),
      '#options' => $user_status_options,
      '#default_value' => 'blocked',
      '#multiple' => FALSE,
      '#description' => $this->t('Select wether the user should be imported as blocked or active. Note that if active welcome e-mails may be sent out.'),
    ];

    // $form['drupaluser_settings']['deyo'] = [
    //   '#type' => 'radios',
    //   '#title' => $this->t('lalal'),
    //   '#options' => $user_status_options,
    //   '#default_value' => 'blocked',
    //   '#multiple' => FALSE,
    //   '#description' => $this->t('Select wether the user should be imported as blocked or active. Note that if active welcome e-mails may be sent out.'),
    // ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /**
     * @todo Validate that an Entra field is not mapped to multiple Drupal fields.
     */
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('entrasync.settings');

    // cron settings handling
    $config->set('retrieve_on_cron', $form_state->getValue('retrieve_on_cron'));

    // Map to which Drupal entity settings handling
    $mapped_drupal_entity = $form_state->getValue('entrauser_entities');
    $mapped_drupal_entity = (array) ($mapped_drupal_entity);
    $config->set('mapped_drupal_entities', $mapped_drupal_entity);

    // Entra fields to Drupal fields handling
    // Extract the various Entra fields from the submitted data
    $user_field_mapping_config = [];
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'user_field_to_') === 0) {
        $entra_field = substr($key, strlen('user_field_to_'));
        $user_field_mapping_config[$entra_field] = $value;
      }
    }
    $config->set('user_field_mapping', $user_field_mapping_config);

    // Role settings handling
    $modify_entrauser_roles = $form_state->getValue('modify_entrauser_roles');
    $modify_drupaluser_roles = $form_state->getValue('modify_drupaluser_roles');
    $modify_entrauser_roles = empty($modify_entrauser_roles) ? [] : $modify_entrauser_roles;
    $modify_drupaluser_roles = empty($modify_drupaluser_roles) ? [] : $modify_drupaluser_roles;

    $config->set('modify_entrauser_roles', array_keys($modify_entrauser_roles));

    // Initial user state handling (blocked or active)
    $entrauser_status = $form_state->getValue('entrauser_status');
    $config->set('entrauser_status', $entrauser_status);

    // Save all set config
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
