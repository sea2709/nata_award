<?php

namespace Drupal\nata_award\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Candidate entity.
 *
 * @ingroup nata_award
 *
 * @ContentEntityType(
 *   id = "nata_candidate",
 *   label = @Translation("Candidate"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nata_award\Entity\Controller\CandidateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\nata_award\CandidateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *    "form" = {
 *       "default" = "Drupal\nata_award\Form\CandidateForm",
 *       "delete" = "Drupal\nata_award\Form\CandidateDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer nata_award",
 *   links = {
 *     "collection" = "/admin/nata_candidate",
 *     "edit-form" = "/nata_award/{nata_candidate}/edit",
 *     "add-form" = "/nata_award/nata_candidate/add",
 *     "delete-form" = "/nata_award/nata_candidate/{nata_candidate}/delete",
 *     "canonical" = "/candidate/{nata_candidate}"
 *   },
 *   base_table = "nata_candidate",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name"
 *   },
 *   field_ui_base_route = "nata_award.candidate_settings",
 * )
 */

class Candidate extends ContentEntityBase implements ContentEntityInterface {
  const NOMINATED = 'nominated';
  const ACCEPTED = 'accepted';
  const DECLINED = 'declined';
  const SUBMITTED = 'submitted';

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Candidate entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Candidate entity.'))
      ->setReadOnly(TRUE);

    $fields['year'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Year'))
      ->setSetting('target_type', 'nata_year')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select'
      ]);

    $fields['award'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Award'))
      ->setSetting('target_type', 'nata_award')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
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
      ]);

    $fields['nimble_account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nimble Account ID'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield'
      ]);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::NOMINATED => t('Nominated'),
        self::ACCEPTED => t('Accepted'),
        self::DECLINED => t('Declined'),
        self::SUBMITTED => t('Submitted')
      ])
      ;

    $fields['sponsored_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sponsored by'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sponsor_statement'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Sponsor Statement'))
      ->setRequired(TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea'
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}
