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
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The password generator.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, PasswordGeneratorInterface $passwordGenerator) {
    $this->configFactory = $configFactory;
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
    $config = \Drupal::config('entrasync.settings');
    $roles_to_modify = $config->get('modify_entrauser_roles');

    // Check if user already exists by email.
    $users = user_load_by_mail($data['email']);

    if (!$users) {
      try {
        // Create user account.
        $user = User::create();

        // Mandatory settings.
        $user->setPassword(\Drupal::service('password_generator')->generate());
        $user->enforceIsNew();
        $user->setEmail($data['email']);
        $user->setUsername($data['userprincipalname']);

        // Add roles to the new user.
        foreach ($roles_to_modify as $role_id) {
          $user->addRole($role_id);
        }

        // Set the user as active.
        $user->activate();

        // Temporarily disable email notification.
        $original_mail_notify = $this->configFactory->getEditable('user.settings')->get('notify');
        $this->configFactory->getEditable('user.settings')->set('notify', 0)->save();

        // Save user.
        $user->save();

        // Restore original email notification settings.
        $this->configFactory->getEditable('user.settings')->set('notify', $original_mail_notify)->save();

        // Custom user fields.
        $user->set('field_fornavn', $data['displayName']);
        $user->set('field_etternavn', $data['givenname']);
        $user->set('field_telefon', $data['mobilephone']);

        // Save user account.
        $user->save();

        $this->logger->notice('Created new user with ID: ' . $user->id());
      }
      catch (\Exception $e) {
        $this->logger->error('User creation failed: ' . $e->getMessage());
      }
    }
    else {
      $this->logger->notice('User already exists with email: ' . $data['email']);
    }
  }

}
