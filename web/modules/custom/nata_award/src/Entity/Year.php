<?php

namespace Drupal\nata_award\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Year entity.
 *
 * @ingroup year
 *
 * @ContentEntityType(
 *   id = "nata_year",
 *   label = @Translation("Year"),
 *   handlers = {
 *     "list_builder" = "Drupal\nata_award\Entity\Controller\YearListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *    "form" = {
 *       "default" = "Drupal\nata_award\Form\YearForm",
*        "delete" = "Drupal\nata_award\Form\YearDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer nata_award",
 *   links = {
 *     "collection" = "/admin/nata_year",
 *     "edit-form" = "/nata_year/{nata_year}/edit",
 *     "add-form" = "/nata_year/add",
 *     "delete-form" = "/nata_year/{nata_year}/delete",
 *   },
 *   base_table = "nata_year",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */

class Year extends ContentEntityBase implements ContentEntityInterface {
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Year entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Year entity.'))
      ->setReadOnly(TRUE);

    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Year'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string'
      ]);

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is active'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox'
    ]);

    $fields['awards'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Awards'))
      ->setSetting('target_type', 'nata_award')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select'
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

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

    return $fields;
  }

  public function label()
  {
    return $this->year->value;
  }
}
