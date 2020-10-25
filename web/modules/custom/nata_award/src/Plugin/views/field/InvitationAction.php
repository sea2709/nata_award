<?php
/**
 * @file
 * Definition of Drupal\nata_award\Plugin\views\field\Invitation
 */

namespace Drupal\nata_award\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\nata_award\Entity\Invitation;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup nata_award
 *
 * @ViewsField("invitation_action")
 */
class InvitationAction extends FieldPluginBase {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['node_type'] = array('default' => 'nata_invitation');

    return $options;
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $build = [];

    $invitation = $values->_entity;
    if ($invitation->status->value == Invitation::SUBMITTED) {
      $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
      $webformSubmissions = $storage->loadByProperties([
        'entity_type' => $invitation->getEntityTypeId(),
        'entity_id' => $invitation->id(),
      ]);
      if (!empty($webformSubmissions)) {
        $sub = current($webformSubmissions);
        $link = $sub->toLink(t('View submission'), 'canonical', ['absolute' => TRUE]);

        $build[] = $link->toRenderable();
        $build['#prefix'] = '<div>';
        $build['#suffix'] = '</div>';
      }
    }

    if ($invitation->status->value == Invitation::INVITED) {
      $build[] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('nata_award.nata_invitation.resend',
          ['invitation' => $invitation->id(), 'destination' => \Drupal::request()->getRequestUri()]),
        '#title' => t('Resend'),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
      $build[] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('nata_award.nata_invitation.delete',
          ['invitation' => $invitation->id(), 'destination' => \Drupal::request()->getRequestUri()]),
        '#title' => t('Delete'),
        '#prefix' => '<div>',
        '#suffix' => '</div>'
      ];
    }

    if ($invitation->status->value != Invitation::SUBMITTED) {
      $build[] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('nata_award.candidate.fill_out_advocate_form',
          [
            'candidate' => $invitation->candidate->target_id,
            'invitation' => $invitation->id(),
            'email' => $invitation->email->value,
            'token' => $invitation->token->value,
          ]),
        '#attributes' => ['target' => '_blank'],
        '#title' => t('Fill Out Form'),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    return $build;
  }
}
