<?php

namespace Drupal\nimble_ams\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\openid_connect\OpenIDConnectStateToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;

/**
 * Class NimbleAmsOpenIDConnectRedirectController.
 *
 * @package Drupal\nimble_ams\Controller
 */
class NimbleAmsOpenIDConnectRedirectController extends ControllerBase implements AccessInterface {

  /**
   * Drupal\openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The OpenID Connect service.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIDConnect;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    OpenIDConnectClientManager $plugin_manager,
    OpenIDConnect $openid_connect,
    RequestStack $request_stack,
    LoggerChannelFactoryInterface $logger_factory,
    AccountInterface $current_user
  ) {
    $this->pluginManager = $plugin_manager;
    $this->openIDConnect = $openid_connect;
    $this->requestStack = $request_stack;
    $this->loggerFactory = $logger_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.openid_connect_client.processor'),
      $container->get('openid_connect.openid_connect'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('current_user')
    );
  }

  /**
   * Access callback: Redirect page.
   *
   * @return bool
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public function access() {
    // Confirm anti-forgery state token. This round-trip verification helps to
    // ensure that the user, not a malicious script, is making the request.
    $query = $this->requestStack->getCurrentRequest()->query;
    $state_token = $query->get('state');
    if ($state_token && OpenIDConnectStateToken::confirm($state_token)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Redirect.
   *
   * @param string $client_name
   *   The client name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse || \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response starting the authentication request.
   */
  public function authenticate($client_name) {
    $messenger = \Drupal::messenger();

    $query = $this->requestStack->getCurrentRequest()->query;

    // Delete the state token, since it's already been confirmed.
    unset($_SESSION['openid_connect_state']);

    // Get parameters from the session, and then clean up.
    $parameters = [
      'destination' => 'user',
      'op' => 'login',
      'connect_uid' => NULL,
    ];
    foreach ($parameters as $key => $default) {
      if (isset($_SESSION['openid_connect_' . $key])) {
        $parameters[$key] = $_SESSION['openid_connect_' . $key];
        unset($_SESSION['openid_connect_' . $key]);
      }
    }
    $destination = $parameters['destination'];

    $configuration = $this->config('openid_connect.settings.' . $client_name)
      ->get('settings');
    $client = $this->pluginManager->createInstance(
      $client_name,
      $configuration
    );
    if (!$query->get('error') && (!$client || !$query->get('code'))) {
      // In case we don't have an error, but the client could not be loaded or
      // there is no state token specified, the URI is probably being visited
      // outside of the login flow.
      throw new NotFoundHttpException();
    }

    $provider_param = ['@provider' => $client->getPluginDefinition()['label']];

    if ($query->get('error')) {
      if (in_array($query->get('error'), [
        'interaction_required',
        'login_required',
        'account_selection_required',
        'consent_required',
      ])) {
        // If we have an one of the above errors, that means the user hasn't
        // granted the authorization for the claims.
        drupal_set_message($this->t('Logging in with @provider has been canceled.', $provider_param), 'warning');
      }
      else {
        // Any other error should be logged. E.g. invalid scope.
        $variables = [
          '@error' => $query->get('error'),
          '@details' => $query->get('error_description') ? $query->get('error_description') : $this->t('Unknown error.'),
        ];
        $message = 'Authorization failed: @error. Details: @details';
        $this->loggerFactory->get('openid_connect_' . $client_name)->error($message, $variables);
        drupal_set_message($this->t('Could not authenticate with @provider.', $provider_param), 'error');
      }
    }
    else {
      // Process the login or connect operations.
      $tokens = $client->retrieveTokens($query->get('code'));
      if ($tokens) {
        if ($parameters['op'] === 'login') {
          $success = $this->openIDConnect->completeAuthorization($client, $tokens, $destination);

          if (!$success) {
            // Check Drupal user register settings before saving.
            $register = $this->config('user.settings')->get('register');
            // Respect possible override from OpenID-Connect settings.
            $register_override = $this->config('openid_connect.settings')
              ->get('override_registration_settings');
            if ($register === USER_REGISTER_ADMINISTRATORS_ONLY && $register_override) {
              $register = USER_REGISTER_VISITORS;
            }

            switch ($register) {
              case USER_REGISTER_ADMINISTRATORS_ONLY:
              case USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL:
                // Skip creating an error message, as completeAuthorization
                // already added according messages.
                break;

              default:
                $messenger->addError($this->t('Logging in with @provider could not be completed due to an error.', $provider_param), 'error');
                break;
            }
          }
        }
        elseif ($parameters['op'] === 'connect' && $parameters['connect_uid'] === $this->currentUser->id()) {
          $success = $this->openIDConnect->connectCurrentUser($client, $tokens);
          if ($success) {
            drupal_set_message($this->t('Account successfully connected with @provider.', $provider_param));
          }
          else {
            drupal_set_message($this->t('Connecting with @provider could not be completed due to an error.', $provider_param), 'error');
          }
        }
      }
    }

    $nimble_config = \Drupal::config('openid_connect.settings.nimble');
    $nimble_settings = $nimble_config->get('settings');

    if (!empty($_SESSION['openid_connect_destination_is_external'])) {
      unset($_SESSION['openid_connect_destination_is_external']);
      $redirect_url_parsed = parse_url($destination);
      if (!empty($nimble_settings['sso_login_redirect_whitelist']) && !empty($redirect_url_parsed['host']) && in_array($redirect_url_parsed['host'], $nimble_settings['sso_login_redirect_whitelist'])) {
        $response = $this->createExternalRedirectResponse($destination);
        return $response;
      }
      else {
        $destination = '/user';
        $redirect = Url::fromUri('internal:/' . ltrim($destination, '/'))->toString();
        return new RedirectResponse($redirect);
      }
    }

    // figure out if user is member, if so, redirect to member redirect path
    $user = User::load(\Drupal::currentUser()->id());
    if (!empty($nimble_settings['roles_member_role']) && $user->hasRole($nimble_settings['roles_member_role'])) {
      if (!empty($nimble_settings['auth_user_redirect_path'])) {
        $destination = $nimble_settings['auth_user_redirect_path'];
      }
    }
    else {
      // else redirect to non-member redirect path
      if (!empty($nimble_settings['auth_non_member_user_redirect_path'])) {
        $destination = $nimble_settings['auth_non_member_user_redirect_path'];
      }
    }

    if (empty($destination)) {
      $destination = '/user';
    }

    // It's possible to set 'options' in the redirect destination.
    if (is_array($destination)) {
      $query = !empty($destination[1]['query']) ? '?' . $destination[1]['query'] : '';
      $redirect = Url::fromUri('internal:/' . ltrim($destination[0], '/') . $query)->toString();
    }
    else {
      $redirect = Url::fromUri('internal:/' . ltrim($destination, '/'))->toString();
    }

    return new RedirectResponse($redirect);

  }

  /**
   * @param $url
   *
   * @return
   */
  private function createExternalRedirectResponse($url) {
    $response = new Response('', 302, []);
    $response->targetUrl = $url;
    $response->setContent(sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url=%1$s" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8')));
    $response->headers->set('Location', $url);
    return $response;
  }
}
