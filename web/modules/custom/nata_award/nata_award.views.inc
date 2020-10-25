<?php

/**
 * Implements hook_views_data_alter().
 */
function nata_award_views_data_alter(array &$data) {
  $data['nata_candidate']['candidate_action'] = array(
    'title' => t('Candidate action'),
    'field' => array(
      'title' => t('Candidate action'),
      'help' => t('Candidate action'),
      'id' => 'candidate_action',
    ),
  );

  $data['nata_nomination']['nomination_action'] = array(
    'title' => t('Nomination action'),
    'field' => array(
      'title' => t('Nomination action'),
      'help' => t('Nomination action'),
      'id' => 'nomination_action',
    ),
  );

  $data['nata_invitation']['invitation_submission'] = array(
    'title' => t('Invitation submission'),
    'field' => array(
      'title' => t('Invitation submission'),
      'help' => t('Invitation submission'),
      'id' => 'invitation_submission',
    ),
  );

  $data['nata_invitation']['invitation_action'] = array(
    'title' => t('Invitation action'),
    'field' => array(
      'title' => t('Invitation action'),
      'help' => t('Invitation action'),
      'id' => 'invitation_action',
    ),
  );

  $data['nata_candidate']['candidate_popup'] = array(
    'title' => t('Invitation action'),
    'field' => array(
      'title' => t('Invitation action'),
      'help' => t('Invitation action'),
      'id' => 'invitation_action',
    ),
  );
}
