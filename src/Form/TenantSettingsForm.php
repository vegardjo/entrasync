<?php

namespace Drupal\entrasync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the Entrasync tenant settings.
 */
class TenantSettingsForm extends ConfigFormBase {

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
    return 'entrasync_tenant_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entrasync.settings');

    $form['graph_key'] = [
      '#type' => 'key_select',
      '#key_filters' => ['type' => 'ms_graph_api'],
      '#title' => $this->t('Secret key'),
      '#default_value' => $config->get('graph_key'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('entrasync.settings')
      ->set('graph_key', $form_state->getValue('graph_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
