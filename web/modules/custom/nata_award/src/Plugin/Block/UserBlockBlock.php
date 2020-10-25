<?php

namespace Drupal\nata_award\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides an user block block.
 *
 * @Block(
 *   id = "nata_award_user_block",
 *   admin_label = @Translation("User Block"),
 *   category = @Translation("NATA Award")
 * )
 */
class UserBlockBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::currentUser();

    if ($user->isAnonymous()) {
      $cUrl = \Drupal\Core\Url::createFromRequest(\Drupal::request());
      $cUrl->setAbsolute(true);
      $url = Url::fromRoute('nimble_ams.sso_login', ['retUrl' => $cUrl->toString()])->toString();
      $build = ['#markup' => '<a href="' . $url . '">Sign In</a>'];
    } else {
      if (\Drupal::service('masquerade')->isMasquerading()) {
        $url = Url::fromRoute('masquerade.unmasquerade')->toString();
        $build = [
          '#markup' => sprintf(t('Hello %s | %s'),
            $user->getDisplayName(), '<a href="' . $url . '">' . t('Unmasquerade') . '</a>'),
        ];
      } else {
        $url = Url::fromRoute('user.logout')->toString();
        $build = [
          '#markup' => sprintf(t('Hello %s | %s'),
            $user->getDisplayName(), '<a href="' . $url . '">' . t('Sign Out') . '</a>'),
        ];
      }
    }

    $build['#cache']['max-age'] = 0;

    return $build;
  }
}
