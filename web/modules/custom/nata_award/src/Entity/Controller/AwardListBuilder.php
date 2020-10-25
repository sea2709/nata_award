<?php

namespace Drupal\nata_award\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for nata_year entity.
 *
 */
class AwardListBuilder extends EntityListBuilder {
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['years_of_membership'] = $this->t('Years of membership');
    $header['years_of_certification'] = $this->t('Years of certification');
    $header['num_required_advocate_forms'] = $this->t('Number of required advocate form');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink($entity->name->value, 'edit-form')->toString();
    $row['years_of_membership'] = $entity->years_of_membership->value;
    $row['years_of_certification'] = $entity->years_of_certification->value;
    $row['n_required_advocate_form'] = $entity->n_required_advocate_form->value;

    return $row + parent::buildRow($entity);
  }
}
