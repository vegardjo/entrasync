<?php

/*
 * Service not used.
 *
 * Service has been removed in favor of integration
 * with https://www.drupal.org/project/ms_graph_api
 *
 * Kept as module per now doesn't support the v2 SDK
 * https://www.drupal.org/project/ms_graph_api/issues/3411827
 *
 */

namespace Drupal\entrasync\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;

/**
 * Class MicrosoftGraphApiClient.
 *
 * Provides a service for interacting with the Microsoft Graph API.
 * It initializes the necessary credentials and returns a configured
 * GraphServiceClient.
 *
 * @todo Introduce admin page, configuration, and use Key module for credential management.
 */
class MicrosoftGraphApiClient {

  /**
   * The client ID for Microsoft Graph API.
   *
   * @var string
   */
  protected $clientId;

  /**
   * The tenant ID for Microsoft Graph API.
   *
   * @var string
   */
  protected $tenantId;

  /**
   * The client secret for Microsoft Graph API.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * Constructs a new MicrosoftGraphApiClient object.
   *
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(KeyRepositoryInterface $key_repository,
                              ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('entrasync.settings');

    $this->clientId = $config->get('client_id');
    $this->tenantId = $config->get('tenant_id');

    // Retrieve the name of the chosen key from conf, and retrieve
    // the actual key from it.
    $key_name = $config->get('client_secret');
    $this->clientSecret = $key_repository->getKey($key_name)->getKeyValue();
  }

  /**
   * Gets the configured GraphServiceClient.
   *
   * This method initializes and returns a GraphServiceClient
   * with the necessary credentials and scopes for Microsoft Graph API.
   *
   * @return \Microsoft\Graph\GraphServiceClient
   *   The configured GraphServiceClient.
   */
  public function getClient() {
    $tokenRequestContext = new ClientCredentialContext(
      $this->tenantId,
      $this->clientId,
      $this->clientSecret
    );

    $scopes = ['https://graph.microsoft.com/.default'];
    return new GraphServiceClient($tokenRequestContext, $scopes);
  }

}
