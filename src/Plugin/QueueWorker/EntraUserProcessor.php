<?php

namespace Drupal\entrasync\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;

/**
 * Processes Entra users.
 *
 * @QueueWorker(
 *   id = "entra_user_processor",
 *   title = @Translation("Entra User Processor"),
 *   cron = {"time" = 60}
 * )
 */
class EntraUserProcessor extends QueueWorkerBase {

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
        $original_mail_notify = \Drupal::configFactory()->getEditable('user.settings')->get('notify');
        \Drupal::configFactory()->getEditable('user.settings')->set('notify', 0)->save();

        // Save user.
        $user->save();

        // Restore original email notification settings.
        \Drupal::configFactory()->getEditable('user.settings')->set('notify', $original_mail_notify)->save();

        // Custom user fields.
        $user->set('field_fornavn', $data['displayName']);
        $user->set('field_etternavn', $data['givenname']);
        $user->set('field_telefon', $data['mobilephone']);

        // Save user account.
        $user->save();

        \Drupal::logger('entrasync')->notice('Created new user with ID: ' . $user->id());
      }
      catch (\Exception $e) {
        \Drupal::logger('entrasync')->error('User creation failed: ' . $e->getMessage());
      }
    }
    else {
      \Drupal::logger('entrasync')->notice('User already exists with email: ' . $data['email']);
    }
  }

}
