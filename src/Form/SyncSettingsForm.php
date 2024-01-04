<?php

namespace Drupal\entrasync\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entrasync.settings');

    $form['retrieve_on_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Retrieve on Cron'),
      '#default_value' => $config->get('retrieve_on_cron', FALSE),
      '#description' => $this->t('Enable to check for new users each time cron runs. If disabled you will need to do a <a href="/admin/config/services/entrasync/sync">manual syncronisation</a>, or invoke it via drush.'),
    ];

    // Retrieve all roles.
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    // Exclude the 'authenticated' role.
    unset($roles['authenticated']);
    unset($roles['anonymous']);

    // Create options array for the select element.
    $role_options = [];
    foreach ($roles as $role_id => $role) {
      $role_options[$role_id] = $role->label();
    };

    // Add a select element for 'modify_entrauser_roles'.
    $form['modify_entrauser_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Modify roles on Entra users'),
      '#options' => $role_options,
      '#default_value' => $config->get('modify_entrauser_roles'),
      '#multiple' => TRUE,
      '#description' => $this->t('Select which roles the new users should or should not have'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('entrasync.settings')
      ->set('retrieve_on_cron', $form_state->getValue('retrieve_on_cron'))
      ->save();

    // Retrieve the selected values for your custom roles
    // configurations from the form state.
    $modify_entrauser_roles = $form_state->getValue('modify_entrauser_roles');
    $modify_drupaluser_roles = $form_state->getValue('modify_drupaluser_roles');

    dsm($modify_entrauser_roles, 'her er arr');

    // Ensure these are saved as arrays, even if empty.
    $modify_entrauser_roles = empty($modify_entrauser_roles) ? [] : array_flip($modify_entrauser_roles);
    $modify_drupaluser_roles = empty($modify_drupaluser_roles) ? [] : array_flip($modify_drupaluser_roles);

    dsm($modify_entrauser_roles, 'kh');

    // Save the configurations.
    $this->config('entrasync.settings')
      ->set('modify_entrauser_roles', array_keys($modify_entrauser_roles))
      ->set('modify_drupaluser_roles', array_keys($modify_drupaluser_roles))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
