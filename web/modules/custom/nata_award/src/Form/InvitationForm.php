<?php

namespace Drupal\nata_award\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for year forms.
 */
class InvitationForm extends ContentEntityForm {
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.nata_invitation.collection');
    $entity = $this->getEntity();
    $entity->save();
  }
}
