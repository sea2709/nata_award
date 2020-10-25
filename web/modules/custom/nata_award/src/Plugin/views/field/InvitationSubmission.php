<?php
namespace Drupal\nata_award\Plugin\views\field;

use Drupal\nata_award\Entity\Invitation;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup nata_award
 *
 * @ViewsField("invitation_submission")
 */
class InvitationSubmission extends FieldPluginBase {
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

        return $link->toRenderable();
      }
    }
  }
}
