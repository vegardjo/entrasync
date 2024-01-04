<?php

namespace Drupal\entrasync\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\entrasync\Services\EntraSync;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for manual synchronization operations.
 *
 * Provides a Drupal form to perform various synchronization tasks with the
 * EntraSync service.
 */
class ManualSyncForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The EntraSync service.
   *
   * @var \Drupal\entrasync\Services\EntraSync
   */
  protected $entraSync;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ManualSyncForm object.
   *
   * @param \Drupal\entrasync\Services\EntraSync $entraSync
   *   The EntraSync service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntraSync $entraSync,
                              MessengerInterface $messenger,
                              LoggerInterface $logger,
                              TranslationInterface $string_translation) {
    $this->entraSync = $entraSync;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entrasync.entra_sync'),
      $container->get('messenger'),
      $container->get('logger.factory')->get('entrasync'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entrasync_manual_sync';
  }

  /**
   * Builds the form for manual synchronization.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['get_sync_status'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get syncronisation status'),
      '#submit' => ['::submitForm', '::getStatus'],
    ];

    $form['full_sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Perform full sync'),
      '#description' => $this->t('hey to I deszcribe'),
      '#submit' => ['::submitForm', '::fullSync'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Default stuff here if needed.
  }

  /**
   * Retrieves and displays the current synchronization status.
   *
   * @param array &$form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function getStatus(array &$form, FormStateInterface $form_state) {
    $this->messenger->addStatus($this->entraSync->getStatus());
    $this->logger->notice('yola');
  }

  /**
   * Performs a full synchronization operation.
   *
   * This method triggers the full synchronization process
   * of the EntraSync service.
   */
  public function fullSync() : Void {
    $this->entraSync->fullSync();
  }

}
