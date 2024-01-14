<?php

namespace Drupal\entrasync\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Entra users.
 *
 * @QueueWorker(
 *   id = "entra_user_processor",
 *   title = @Translation("Entra User Processor"),
 *   cron = {"time" = 60}
 * )
 */
class EntraUserProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config.
   *
   * @var use Drupal\Core\Config\ConfigFactoryInterface;

   */
  protected $config;


  /**
   * The password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, PasswordGeneratorInterface $passwordGenerator) {
    $this->configFactory = $configFactory;
    $this->config = $configFactory->get('entrasync.settings');
    $this->logger = $loggerFactory->get('entrasync');
    $this->passwordGenerator = $passwordGenerator;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('password_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Retrieve the configuration for roles.
    $roles_to_modify = $this->config->get('modify_entrauser_roles');

    // Check if user already exists by email.
    $users = user_load_by_mail($data['email']);

    if (!$users) {
      try {
        // Create user account.
        $user = User::create();

        // Set mandatory fields.
        $user->setPassword($this->passwordGenerator->generate());
        $user->enforceIsNew();
        $user->setEmail($data['email']);
        $user->setUsername($data['userprincipalname']);

        // Set custom fields
        $user_field_mapping = $this->config->get('user_field_mapping');
        foreach ($user_field_mapping as $entra_field => $drupal_field) {
          if (isset($data[$entra_field]) && $data[$entra_field] !== '') {
            // Tmp: Flatten array values to a comma-separated string, this is true for the businessPhones data
            $field_value = is_array($data[$entra_field]) ? implode(', ', $data[$entra_field]) : $data[$entra_field];
            if ($user->hasField($drupal_field)) {
              $user->set($drupal_field, $field_value);
            } else {
              $this->logger->error('The field ' . $drupal_field . ' does not exist on the user entity.');
            }
          }
        }

        // Add roles to the new user.
        foreach ($roles_to_modify as $role_id) {
          $user->addRole($role_id);
        }

        /**
        * @todo This has a setting that is not respected yet
        */
        // Set the user as active.
        $user->activate();

        /**
        * @todo This should be a conditional setting
        */
        // Temporarily disable email notification.
        $original_mail_notify = $this->configFactory->getEditable('user.settings')->get('notify');
        $this->configFactory->getEditable('user.settings')->set('notify', 0)->save();

        // Save user.
        $user->save();

        // Restore original email notification settings.
        $this->configFactory->getEditable('user.settings')->set('notify', $original_mail_notify)->save();

        // Save user account.
        $user->save();

        $this->logger->notice('Created new user with ID: ' . $user->id());
      }
      catch (\Exception $e) {
        $this->logger->error('User creation failed: ' . $e->getMessage());
      }
    }
    else {
    /**
      * @todo This is not logging anything for some reason
      */
      $this->logger->notice('User already exists with email: ' . $data['email']);
    }
  }

}
