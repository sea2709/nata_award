<?php

namespace Drupal\nata_award\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ds\Plugin\DsField\Entity;
use Drupal\nata_award\Entity\Invitation;
use Symfony\Component\HttpFoundation\RedirectResponse;

class InvitationController extends ControllerBase {
  public function updateStatus($invitation, $status) {
    $invitation->status = $status;
    $invitation->save();

    $uri = \Drupal::request()->query->get('destination', '/');
    return new RedirectResponse($uri);
  }

  public function updateStatusAccess($invitation) {
    return AccessResult::allowed();
  }

  public function delete($invitation) {
    $invitation->delete();
    \Drupal::messenger()->addMessage(t('Delete invitation successfully!'));
    $uri = \Drupal::request()->query->get('destination', '/');
    return new RedirectResponse($uri);
  }

  public function resend($invitation) {
    $invitation->sendEmailInvitation();
    \Drupal::messenger()->addMessage(t('Resend invitation successfully!'));
    $uri = \Drupal::request()->query->get('destination', '/');
    return new RedirectResponse($uri);
  }

  public function deleteAndResendAccess(EntityInterface $invitation) {
    $currentUser = \Drupal::currentUser();
    return AccessResult::allowed($currentUser
      && $invitation->status->value == Invitation::INVITED && $currentUser->id() == $invitation->user->target_id);
  }
}
