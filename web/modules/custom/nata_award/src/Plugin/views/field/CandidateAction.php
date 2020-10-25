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
 * @ViewsField("candidate_action")
 */
class CandidateAction extends FieldPluginBase {

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
    $options['node_type'] = array('default' => 'nata_candidate');

    return $options;
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $candidate = $values->_entity;

    switch ($candidate->status->value) {
      case Candidate::NOMINATED:
        $acceptUri = Url::fromRoute('nata_award.candidate_update_status',
            ['candidate' => $candidate->id(), 'status' => Candidate::ACCEPTED, 'destination' => Url::fromRoute('entity.nata_candidate.canonical',
              ['nata_candidate' => $candidate->id()])->toString()])->toString();
        $declineUri = Url::fromRoute('nata_award.candidate_update_status',
          ['candidate' => $candidate->id(), 'status' => Candidate::DECLINED, 'destination' => \Drupal::request()->getRequestUri()])->toString();
        return [[
          '#type' => 'markup',
          '#markup' => '<a class="button" href="' . $acceptUri . '">' . t('Accept') . '</a>'
        ], [
          '#type' => 'markup',
          '#markup' => '<a class="button button-alt" href="' . $declineUri . '">' . t('Decline') . '</a>'
        ]];
      case Candidate::ACCEPTED:
      case Candidate::SUBMITTED:
        $uri = Url::fromRoute('entity.nata_candidate.canonical',
          ['nata_candidate' => $candidate->id()])->toString();
        return [
          '#type' => 'markup',
          '#markup' => '<a class="button" href="' . $uri . '">' . t('Select') . '</a>'
        ];
      case Candidate::DECLINED:
        return '';
    }
  }
}
