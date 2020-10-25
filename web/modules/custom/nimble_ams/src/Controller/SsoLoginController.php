<?php
namespace Drupal\nimble_ams\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\openid_connect\OpenIDConnectSession;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides sso-login route responses for the Nimble AMS module.
 */
class SsoLoginController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSession
   */
  protected $session;

  /**
   * Drupal\openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The constructor.
   *
   * @param \Drupal\openid_connect\OpenIDConnectSession $session
   *   The OpenID Connect session service.
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\Core\Path\PathValidator $path_validator
   *  The path validator service
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    OpenIDConnectSession $session,
    OpenIDConnectClientManager $plugin_manager,
    OpenIDConnectClaims $claims,
    PathValidator $path_validator,
    RequestStack $request_stack
  ) {
    $this->session = $session;
    $this->pluginManager = $plugin_manager;
    $this->claims = $claims;
    $this->pathValidator = $path_validator;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openid_connect.session'),
      $container->get('plugin.manager.openid_connect_client.processor'),
      $container->get('openid_connect.claims'),
      $container->get('path.validator'),
      $container->get('request_stack')
    );
  }
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function contentCallback() {
    if (!\Drupal::currentUser()->id()) {
      $destination_url = $this->requestStack->getCurrentRequest()->query->get('retUrl');
      if (!empty($destination_url)) {
        $_SESSION['openid_connect_destination'] = $destination_url;
        $_SESSION['openid_connect_destination_is_external'] = TRUE;
//        $_SESSION['openid_connect_destination_is_external'] = FALSE;
      }
      $client_name = 'nimble';
      $configuration = $this->config('openid_connect.settings.' . $client_name)
        ->get('settings');
      $client = $this->pluginManager->createInstance(
        $client_name,
        $configuration
      );
      $scopes = $this->claims->getScopes();
      $_SESSION['openid_connect_op'] = 'login';
      $response = $client->authorize($scopes);
      return $response;
    }
    else {
      $destination_url = $this->requestStack->getCurrentRequest()->query->get('retUrl');
      if (!empty($destination_url)) {
        return new TrustedRedirectResponse($destination_url);
//        return new RedirectResponse($destination_url);
      }
      $config = \Drupal::config('openid_connect.settings.nimble');
      $settings = $config->get('settings');
      if (!empty($settings['sso_login_auth_user_redirect_path'])) {
        $url_object = $this->pathValidator->getUrlIfValid($settings['sso_login_auth_user_redirect_path']);
        $route_name = $url_object->getRouteName();
        if (!empty($route_name)) {
          return $this->redirect($route_name);
        }
      }
      return $this->redirect('user.page');
    }
  }

}
