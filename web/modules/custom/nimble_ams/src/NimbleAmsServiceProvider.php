<?php

namespace Drupal\nimble_ams;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class NimbleAmsServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('maintenance_mode');
    $definition->setClass('Drupal\nimble_ams\NimbleAmsMaintenanceMode');
  }
}
