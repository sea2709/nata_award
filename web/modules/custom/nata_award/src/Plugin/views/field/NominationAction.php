<?php

/**
 * @file
 * Definition of Drupal\nata_award\Plugin\views\field\CandidateAction
 */

namespace Drupal\nata_award\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\nata_award\Entity\Candidate;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup nata_award
 *
 * @ViewsField("nomination_action")
 */
class NominationAction extends FieldPluginBase {

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
    $options['node_type'] = array('default' => 'nata_nomination');

    return $options;
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $nomination = $values->_entity;
    $candidate = current($nomination->candidate->referencedEntities());

    if (!empty($candidate) && ($candidate->status->value == Candidate::SUBMITTED || $candidate->status->value == Candidate::ACCEPTED)) {
      $uri = Url::fromRoute('entity.nata_candidate.canonical',
        ['nata_candidate' => $candidate->id()])->toString();
      return [
        '#type' => 'markup',
        '#markup' => '<a class="button" href="' . $uri . '">' . t('Select') . '</a>'
      ];
    }
  }
}
