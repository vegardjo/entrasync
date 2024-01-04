<?php

namespace Drupal\entrasync\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Common (Entra and Drupal) users.
 *
 * @QueueWorker(
 *   id = "entra_common_user_processor",
 *   title = @Translation("Entra and Drupal common user processor"),
 *   cron = {"time" = 60}
 * )
 */
class CommonUserProcessor extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('entrasync')->error('process log common: ' . $data);
    // Process the user data.
    // $data will be an individual item from the queue.
  }

}
