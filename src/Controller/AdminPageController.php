<?php

namespace Drupal\entrasync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\entrasync\Services\EntraSync;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminPageController.
 *
 * Controller for administrative pages of the EntraSync module.
 */
class AdminPageController extends ControllerBase {

  /**
   * The EntraSync service.
   *
   * @var Drupal\entrasync\Services\EntraSync
   */
  protected $entraSync;

  /**
   * The form builder service.
   *
   * @var Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs an AdminPageController object.
   *
   * @param Drupal\entrasync\Services\EntraSync $entraSync
   *   The EntraSync service.
   * @param Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   */
  public function __construct(EntraSync $entraSync, FormBuilderInterface $formBuilder) {
    $this->entraSync = $entraSync;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entrasync.entra_sync'),
      $container->get('form_builder')
    );
  }

  /**
   * Renders the tenant settings form.
   *
   * @return array
   *   A render array representing the tenant settings form.
   */
  public function tenantSettings() : Array {
    return $this->formBuilder->getForm('Drupal\entrasync\Form\TenantSettingsForm');
  }

  /**
   * Renders the synchronization settings form.
   *
   * @return array
   *   A render array representing the synchronization settings form.
   */
  public function syncSettings() : Array {
    return $this->formBuilder->getForm('Drupal\entrasync\Form\SyncSettingsForm');
  }

  /**
   * Handles the manual synchronization process.
   *
   * Fetches lists of Entra and Drupal users, compares them,
   * and initiates the synchronization.
   *
   * @return array
   *   A render array for displaying the synchronization result.
   */
  public function manualSync() {
    return $this->formBuilder->getForm('Drupal\entrasync\Form\ManualSyncForm');
  }

}
