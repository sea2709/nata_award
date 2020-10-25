<?php

namespace Drupal\nata_award\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Render\Markup;

/**
 * Defines the Invitation entity.
 *
 * @ingroup nata_award
 *
 * @ContentEntityType(
 *   id = "nata_invitation",
 *   label = @Translation("Invitation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nata_award\Entity\Controller\InvitationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\nata_award\Form\InvitationForm",
 *       "delete" = "Drupal\nata_award\Form\InvitationDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer nata_award",
 *   links = {
 *     "collection" = "/admin/nata_invitation",
 *     "edit-form" = "/nata_award/{nata_invitation}/edit",
 *     "add-form" = "/nata_award/nata_invitation/add",
 *     "delete-form" = "/nata_award/nata_invitation/{nata_invitation}/delete",
 *   },
 *   base_table = "nata_invitation",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Invitation extends ContentEntityBase implements ContentEntityInterface
{
  const INVITED = 'invited';
  const ACCEPTED = 'accepted';
  const DECLINED = 'declined';
  const SUBMITTED = 'submitted';
  const RELATIONSHIP_OPTIONS = array(
    'patient_client_athlete' => 'Patient / Client / Athlete',
    'mentee_student_intern' => 'Mentee / Student / Intern',
    'colleague_coworker_supervisor_administrator' => 'Colleague / Co-worker / Supervisor / Administrator',
    'team_doctor_physical_therapist_chiropractor' => 'Health Care Professional (Team doctor/ Physical Therapist / Chiropractor / etc.)',
    'nata_hall_of_fame_member' => 'NATA Hall of Fame Member'
  );

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Invitation entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Invitation entity.'))
      ->setReadOnly(TRUE);

    $fields['candidate'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Candidate'))
      ->setSetting('target_type', 'nata_candidate')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ]
      ]);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ]
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string'
      ]);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield'
      ])
      ->addConstraint('UserMailRequired');

    $fields['relationship'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Relationship'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::RELATIONSHIP_OPTIONS)
      ->setDisplayOptions('form', [
        'type' => 'options_select'
      ]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::INVITED)
      ;

    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  public function sendEmailInvitation()
  {
    $nataAwardCfg = \Drupal::config('nata_award.settings');
    $mailManager = \Drupal::service('plugin.manager.mail');
    $tokenService = \Drupal::token();

    $module = 'nata_award';
    $key = 'request_candidate_advocate_form';
    $to = $this->email->value;
    $params['subject'] = $nataAwardCfg->get('advocate_email_subject');
    $params['body'] = Markup::create($tokenService->replace($nataAwardCfg->get('advocate_email_body'), ['nata_invitation' => $this]));
    $params['nata_invitation'] = $this;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;

    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    $messenger = \Drupal::messenger();
    if ($result['result'] !== TRUE) {
      $messenger->addError(t('There was a problem sending email.'));
    } else {
      $messenger->addMessage(t('The email has been sent.'));
    }
  }
}
