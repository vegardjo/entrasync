<?php

namespace Drupal\entrasync\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ms_graph_api\GraphApiGraphFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class EntraSync {

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Microsoft Graph API client.
   *
   * @var Drupal\ms_graph_api\GraphApiGraphFactory
   */
  protected $graphFactory;

  /**
   * The Microsoft Graph API client.
   *
   * @var \Microsoft\Graph\Graph
   */
  protected $graphClient;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

    /**
     * The configuration object.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected $config;

  /**
   * Constructs a new EntraSync object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Microsoft\Graph\GraphFactory $MicrosoftGraphApiFactory
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *
   */
  function __construct(EntityTypeManagerInterface $entityTypeManager,
                       GraphApiGraphFactory $MicrosoftGraphApiFactory,
                       QueueFactory $queueFactory,
                       MessengerInterface $messenger,
                       LoggerChannelFactoryInterface $loggerFactory,
                       ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->graphFactory = $MicrosoftGraphApiFactory;
    $this->queueFactory = $queueFactory;
    $this->messenger = $messenger;
    $this->logger = $loggerFactory->get('entrasync');
    $this->config = $configFactory->get('entrasync.settings');

    // $this->graphClient = $this->graphFactory->buildGraphFromKeyId('ms_graph_api_default_key');
  }

  /**
   * Retrieves the status of user synchronization.
   *
   * @todo Everything
   * @return array An associative array of synchronization status information.
   */
  public function getStatus() {

    // For an administrator overseeing the synchronization between Drupal users and Microsoft Entra (Azure AD), providing insightful statuses can greatly assist in monitoring and managing the system effectively. Here are some useful statuses you might consider:

    // Last Synchronization Time: Date and time of the last successful synchronization. This helps in understanding the currency of the data.

    // Number of Users in Drupal: The current count of users in the Drupal system.

    // Number of Users in Entra (Azure AD): The current count of users in Entra.

    // Number of Users Synced: How many users are successfully synchronized between the two systems.

    // Number of Users Pending Sync: Users existing in one system but not yet synced to the other.

    // Recent Sync Errors/Issues: Any errors or issues encountered during the last sync process, with timestamps.

    // Status of Last Sync: Whether the last sync was successful, partially successful (with some errors), or failed.

    // User Sync Mismatch Details: Specific details about any discrepancies between the user data in Drupal and Entra, such as mismatched user roles or profiles.

    // Duration of the Last Sync Process: How long the last synchronization process took.

    // Manual Intervention Required: Flag or notice if any manual intervention is required for specific cases or errors.

    // Audit Logs Link: A direct link to detailed logs or an audit trail for more in-depth analysis.

    // System Health Status: Overall health status of the sync system, possibly with a simple color-coded indicator (green for healthy, yellow for warnings, red for critical issues).

    // Scheduled Next Sync: If synchronization is done on a schedule, show the next planned sync time.

    // User Actions Required: Any actions that the administrator needs to take, such as approving user access, resolving conflicts, etc.

    // API Call Statistics: Information about API usage, rate limits, and any related errors, if applicable.

    // Version Information: Display the version of the sync software or module being used, useful for troubleshooting and updates.

    // Implementing the Statuses
    // These statuses can be implemented in the getStatus method of your synchronization class or a similar utility. Depending on your system's complexity, you might also need additional methods or services to gather this information. Remember, presenting this data in a clear, concise, and user-friendly manner in the Drupal admin interface is key to its usefulness.

    // $entraUserCount = '33';
    // $drupalUserCount = '53';

    // return [
    //   'entraUserCount' => $entraUserCount,
    //   'drupalUserCount' => $drupalUserCount,
    // ];

  }

  /**
   * Retrieves a list of users from Microsoft Entra.
   *
   * @return array An array of user information from Entra.
   */
  public function getEntraUsersList() {
    /** @var \Microsoft\Graph\Graph $client */
    $client = $this->graphFactory->buildGraphFromKeyId($this->config->get('client_secret'));

    $getCollectionTimeStart = microtime(true);

    try {
      $userCollectionRequest = $client
                                ->createCollectionRequest("GET", "/users")
                                ->setReturnType(\Microsoft\Graph\Model\User::class);

      // Handle pagination
      $allUsers = [];
      while(!$userCollectionRequest->isEnd()) {
        $usersPage = $userCollectionRequest->getPage();
        foreach($usersPage as $user) {
          $allUsers[] = $user;
        }
      }

      // SDK v 2
      // $users = $userCollecton->getValue();

      // getting only the information we need from the user classes
      $destilledEntraUserInfo = [];
      foreach($allUsers as $user) {
        $userDetails = [
          'userprincipalname' => $user->getUserPrincipalName(),
          'displayName' => $user->getDisplayName(),
          'givenname' => $user->getGivenName(),
          'surname' => $user->getSurname(),
          'businessphones' => $user->getBusinessPhones(),
          'mobilephone' => $user->getMobilePhone(),
          'department' => $user->getDepartment(),
          'email' => $user->getMail(),
          'jobtitle' => $user->getJobTitle(),
          'officelocation' => $user->getOfficeLocation(),
          'id' => $user->getId(),
        ];
        $destilledEntraUserInfo[] = $userDetails;
      }

      // adding some logging:
      $numberOfUsersFetched = count($destilledEntraUserInfo);
      $getCollectionTimeStop = microtime(true);
      $getCollectionExecutionTime = sprintf("%.2f", ($getCollectionTimeStop - $getCollectionTimeStart));
      $logMessage = 'Fetching ' . $numberOfUsersFetched . ' users from Entra took ' . $getCollectionExecutionTime . ' seconds';

      $this->logger->notice($logMessage);
      $this->messenger->addMessage($logMessage);

      return $destilledEntraUserInfo;
    }
    catch (\Throwable $e) {
      $this->logger->error($e->getMessage());
      return [];
    }
  }

  /**
   * Retrieves a list of Drupal users.
   *
   * @return array An array of Drupal user information.
   */
  public function getDrupalUsersList() : Array {
    // getting all drupal user objects
    $userStorage = $this->entityTypeManager->getStorage('user');
    $query = $userStorage->getQuery();
    $uids = $query
      ->accessCheck(TRUE)
      ->condition('uid', 1, '!=')
      ->execute();

    /** @var \Drupal\user\UserInterface $users */
    $users = $userStorage->loadMultiple($uids);

    // destilling the objects to only get an array of emails.
    $drupalUserList = [];
    foreach($users as $user) {
      $userDetails = [
        'email' => $user->getEmail(),
      ];
      $drupalUserList[] = $userDetails;
    }
    return $drupalUserList;
  }

  /**
   * Compares user lists from Entra and Drupal to identify unique and common users.
   *
   * @param array $entraUsers An array of users from Entra.
   * @param array $drupalUsers An array of Drupal users.
   *
   * @return array An associative array categorizing users.
   *
   * @todo Add a count per array and return and/or log these values as well.
   */
  public function compareUserLists(Array $entraUsers, Array $drupalUsers) : Array {
    $drupalEmails = array_column($drupalUsers, 'email');

    $onlyInEntra = [];
    $onlyInDrupal = [];
    $inBoth = [];

    foreach ($entraUsers as $entraUser) {
      if (in_array($entraUser['userprincipalname'], $drupalEmails)) {
        $inBoth[] = $entraUser; // User is in both Entra and Drupal.
      } else {
        $onlyInEntra[] = $entraUser; // User is only in Entra.
      }
    }

    foreach ($drupalUsers as $drupalUser) {
      if (!in_array($drupalUser['email'], array_column($entraUsers, 'userprincipalname'))) {
        $onlyInDrupal[] = $drupalUser; // User is only in Drupal.
      }
    }

    // Return the categorized user objects.
    return [
      'onlyInEntra' => $onlyInEntra,
      'onlyInDrupal' => $onlyInDrupal,
      'inBoth' => $inBoth
    ];
}

  /**
   * Delegating users present only in Entra to a separate queue
   *
   * @param array $entraUsers An array of Entra users.
   */
  public function processUsersOnlyInEntra(Array $entraUsers) {

    $queue = $this->queueFactory->get('entra_user_processor');

    foreach ($entraUsers as $user) {
      $this->logger->notice('Adding Entra user to queue: ' . $user['userprincipalname']);
      $queue->createItem($user);
    }
  }

  /**
   * Delegating users present only in Drupal to a separate queue
   *
   * @param array $drupalUsers An array of Drupal users.
   */
  public function processUsersOnlyInDrupal(Array $drupalUsers) {

    $queue = $this->queueFactory->get('entra_drupal_user_processor');
    // Add each user to the queue
    foreach ($drupalUsers as $user) {
      $this->logger->notice('Adding Drupal user to queue: ' . $user['email']);
      $queue->createItem($user);
    }
  }

  public function processCommonUsers(Array $commonEmails) {}

  /**
   * Prepares data for synchronization.
   *
   * @return array An array containing data for synchronization.
   */
  public function prepareSyncData() : Array {
    return $this->compareUserLists($this->getEntraUsersList(), $this->getDrupalUsersList());
  }

  public function fullSync() {
    // Handle the syncing of users.
    $onlyInEntra = $this->prepareSyncData()['onlyInEntra'];
    $onlyInDrupal = $this->prepareSyncData()['onlyInDrupal'];

    // perform both syncs.
    $this->processUsersOnlyInEntra($onlyInEntra);
    $this->processUsersOnlyInDrupal($onlyInDrupal);
  }
}