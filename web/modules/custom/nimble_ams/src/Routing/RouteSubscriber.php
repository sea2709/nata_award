<?php

namespace Drupal\nimble_ams\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the route associated with the user profile page (/user, /user/{uid}).
    if ($route = $collection->get('openid_connect.redirect_controller_redirect')) {
      $route->setDefault('_controller', '\Drupal\nimble_ams\Controller\NimbleAmsOpenIDConnectRedirectController::authenticate');
      $options = $route->getOptions();
      $options['no_cache'] = TRUE;
      $route->setOptions($options);
    }
  }

}