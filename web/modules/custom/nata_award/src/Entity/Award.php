<?php

namespace Drupal\nata_award\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Award entity.
 *
 * @ingroup nata_award
 *
 * @ContentEntityType(
 *   id = "nata_award",
 *   label = @Translation("Award"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nata_award\Entity\Controller\AwardListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *    "form" = {
 *       "default" = "Drupal\nata_award\Form\AwardForm",
 *       "delete" = "Drupal\nata_award\Form\AwardDeleteForm",
 *     },
 *     "access" = "Drupal\nata_award\AwardAccessControlHandler",
 *   },
 *   admin_permission = "administer nata_award",
 *   links = {
 *     "collection" = "/admin/nata_award",
 *     "edit-form" = "/nata_award/{nata_award}/edit",
 *     "add-form" = "/nata_award/add",
 *     "delete-form" = "/nata_award/{nata_award}/delete",
 *     "canonical" = "/nata_award/{nata_award}"
 *   },
 *   base_table = "nata_award",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   field_ui_base_route = "nata_award.award_settings",
 * )
 */

class Award extends ContentEntityBase implements ContentEntityInterface {
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Award entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Award entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 256,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setRequired(TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['years_of_membership'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Years of membership'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['years_of_certification'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Years of certification'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['forms'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Forms'))
      ->setSetting('target_type', 'webform')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['advocate_form'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Advocate Form'))
      ->setSetting('target_type', 'webform')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['advocacy_help_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Advocacy Help Text'))
      ->setSettings([
        'text_processing' => 1,
        'display_summary' => 0
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['n_required_advocate_form'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Required advocate form'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['open_ended_form'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Open-ended Questions Form'))
      ->setSetting('target_type', 'webform')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nimble_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nimble Type Id'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 64,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
      ]);

    $fields['past_winners_csv_file'] = BaseFieldDefinition::create('file')
      ->setLabel('Past Winners CSV File')
      ->setSettings([
        'uri_scheme' => 'public',
        'file_directory' => 'past_winners',
        'file_extensions' => 'csv',
      ])
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'file',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'file',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  public function label()
  {
    return $this->name->value;
  }
}
