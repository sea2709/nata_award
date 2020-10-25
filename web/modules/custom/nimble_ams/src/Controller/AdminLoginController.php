<?php
namespace Drupal\nimble_ams\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Form\UserLoginForm;

/**
 * Provides admin/login route responses for the Nimble AMS module.
 */
class AdminLoginController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function contentCallback() {
    $markup = '';
    if (!\Drupal::currentUser()->id()) {
      $form = \Drupal::formBuilder()->getForm(UserLoginForm::class) ;
      $render = \Drupal::service('renderer');
      $markup = $render->renderPlain($form);
    }
    $element = array(
      '#markup' => $markup,
    );
    return $element;
  }

}
