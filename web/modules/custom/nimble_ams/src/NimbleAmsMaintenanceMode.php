<?php


namespace Drupal\nimble_ams;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Site\MaintenanceModeInterface;
use Drupal\Core\Site\MaintenanceMode;

/**
 * Provides the default implementation of the maintenance mode service.
 */
class NimbleAmsMaintenanceMode extends MaintenanceMode implements MaintenanceModeInterface {

  /**
   * {@inheritdoc}
   */
  public function exempt(AccountInterface $account) {
    if ($account->hasPermission('access site in maintenance mode')) {
      return TRUE;
    }
    $current_path = \Drupal::service('path.current')->getPath();
    if ($current_path == '/admin/login') {
      return TRUE;
    }
    // No valid exemption, so user remains blocked.
    return FALSE;
  }
}
