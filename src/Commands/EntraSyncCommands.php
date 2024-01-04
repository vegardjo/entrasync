<?php

namespace Drupal\entrasync\Commands;

use Drush\Commands\DrushCommands;

/**
 * Defines Drush commands for the Azure Sync module.
 */
class EntraSyncCommands extends DrushCommands {

  /**
   * Test Microsoft Graph SDK.
   *
   * @command entrasync:test_graph_sdk
   * @aliases entra-test
   * @usage entrasync:test_graph_sdk
   *   Test the Microsoft Graph SDK.
   */
  public function testGraphSdk() {
    $result = 'todo';
    $this->output()->writeln($result);
  }

}
