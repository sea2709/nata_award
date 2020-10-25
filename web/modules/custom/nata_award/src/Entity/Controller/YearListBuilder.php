<?php

namespace Drupal\nata_award\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for nata_year entity.
 *
 */
class YearListBuilder extends EntityListBuilder {
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['year'] = $this->t('Year');
    $header['active'] = $this->t('Active');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['year'] = $entity->toLink($entity->year->value, 'edit-form')->toString();
    $row['active'] = $entity->active->value;

    return $row + parent::buildRow($entity);
  }
}
