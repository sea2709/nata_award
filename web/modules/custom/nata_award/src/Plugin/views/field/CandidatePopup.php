<?php
/**
 * @file
 * Definition of Drupal\nata_award\Plugin\views\field\Invitation
 */

namespace Drupal\nata_award\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nata_award\Entity\Invitation;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup nata_award
 *
 * @ViewsField("nata_candidate_popup")
 */
class CandidatePopup extends FieldPluginBase {
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
    if ($values) {

    }
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'text',
      '#title' => $this->t('URL'),
      '#default_value' => $this->options['url'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }
}
