<?php

namespace Drupal\nata_award\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for nata_year entity.
 *
 */
class CandidateListBuilder extends EntityListBuilder {
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['year'] = $this->t('Year');
    $header['award'] = $this->t('Award');
    $header['user'] = $this->t('User');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $year = current($entity->year->referencedEntities());
    $award = current($entity->award->referencedEntities());
    $user = current($entity->user->referencedEntities());

    $row['id'] = $entity->id();
    $row['name'] = $entity->name->value;
    $row['year'] = !empty($year) ? $year->year->value : '';
    $row['award'] = !empty($award) ? $award->name->value : '';
    $row['user'] = !empty($user) ? $user->getAccountName() : '';
    $row['status'] = $entity->status->value;

    return $row + parent::buildRow($entity);
  }
}
