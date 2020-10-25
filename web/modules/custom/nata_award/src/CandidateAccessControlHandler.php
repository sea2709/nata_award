<?php

namespace Drupal\nata_award;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\nata_award\Entity\Candidate;

/**
 * Access controller for the contact entity.
 */
class CandidateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess() is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.
    $admin_permission = $this->entityType->getAdminPermission();
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }

    switch ($operation) {
      case 'view':
        if ($entity->status->value == Candidate::ACCEPTED || $entity->status->value == Candidate::SUBMITTED) {
          if (!empty($entity->user->target_id) && $entity->user->target_id == $account->id()) {
            return AccessResult::allowed();
          }

          $nomination = current(\Drupal::entityTypeManager()->getStorage('nata_nomination')
            ->loadByProperties(['candidate' => $entity->id(), 'sponsored_by' => $account->id()]));
          if (!empty($nomination)) {
            return AccessResult::allowed();
          }
        }
      case 'update':
        return AccessResult::allowedIf(!empty($entity->user->target_id) && $entity->user->target_id == $account->id());
      case 'invite_advocate':
        if (!empty($entity->user->target_id) && $entity->user->target_id == $account->id()) {
          return AccessResult::allowed();
        }

        $nomination = current(\Drupal::entityTypeManager()->getStorage('nata_nomination')
          ->loadByProperties(['candidate' => $entity->id(), 'sponsored_by' => $account->id()]));
        if (!empty($nomination)) {
          return AccessResult::allowed();
        }
      case 'fill_out_advocate':
        return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist. It
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.
    $admin_permission = $this->entityType->getAdminPermission();
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermission($account, 'add nata_award entity');
  }

}
