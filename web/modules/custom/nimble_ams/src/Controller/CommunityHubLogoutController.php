<?php
namespace Drupal\nimble_ams\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides community-hub-logout route response.
 */
class CommunityHubLogoutController extends ControllerBase {

  /**
   * Logs user out and redirects to community hub logout page
   */
  public function contentCallback() {
    user_logout();
    $config = \Drupal::config('openid_connect.settings.nimble');
    $settings = $config->get('settings');

    if (!empty($settings['endpoint'])) {
      $logout_url = $settings['endpoint'] . '/secur/logout.jsp';
      $response = new TrustedRedirectResponse($logout_url);
      $response->send();
      return $response;
    }
    else {
      return $this->redirect('user.page');
    }
  }
}
