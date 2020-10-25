<?php

namespace Drupal\nata_award\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class NominateController extends ControllerBase {
  public function nominateSuccessfully() {
    return [
      [
        '#type' => 'markup',
        '#markup' => '<h2>' . t('Thank you for your nomination') . '</h2>'
      ],
      [
        '#type' => 'markup',
        '#markup' => '<p>' . t('All award recipients will be expected to accept their award (General Session or Hall of Fame induction ceremony at the NATA Convention) during the year of their selection. Deferral to another year due to an emergency situation such as an illness or death in the family will require approval by a majority vote of the Honors & Awards committee.') . '</p>'
      ],
      [
        '#type' => 'markup',
        '#markup' => '<p>' . t('Please ensure that your email address and contact information are up-to-date and on file with the NATA. Please check with your candidate to make sure their information is up-to-date as well. It is the candidate\'s responsibility to check email regularly for updates. If the candidate is selected as a winner, they will receive important information about the award presentation via email only.') . '</p>'
      ],
      [
        '#type' => 'link',
        '#url' => Url::fromRoute('<front>'),
        '#title' => t('<< Back to homepage'),
      ],
    ];
  }
}
