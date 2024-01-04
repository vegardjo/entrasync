<?php

namespace Drupal\entrasync\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Drupal users.
 *
 * @QueueWorker(
 *   id = "entra_drupal_user_processor",
 *   title = @Translation("Entra Drupal User Processor"),
 *   cron = {"time" = 60}
 * )
 */
class DrupalUserProcessor extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('entrasync')->notice('process log drupaluser: ' . $data['email'] . ' currently doing nada');
    // Process the user data.
    // $data will be an individual item from the queue.
  }

}
