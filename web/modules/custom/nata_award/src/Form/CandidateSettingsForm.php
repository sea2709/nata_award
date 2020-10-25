<?php

namespace Drupal\nata_award\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CandidateSettingsForm.
 *
 * @ingroup nata_award
 */
class CandidateSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'nata_award_candidate_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['candidate_settings']['#markup'] = 'Settings form for NATA Candidate. Manage field settings here.';
    return $form;
  }

}
