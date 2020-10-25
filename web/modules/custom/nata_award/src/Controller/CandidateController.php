<?php

namespace Drupal\nata_award\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\nata_award\Entity\Candidate;
use Drupal\nata_award\Entity\Invitation;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CandidateController extends ControllerBase {
  public function updateStatus($candidate, $status) {
    $candidate->status = $status;
    $candidate->save();

    $messenger = \Drupal::messenger();
    $award = current($candidate->award->referencedEntities());

    if ($status == Candidate::SUBMITTED) {
      $params = [];

      $print_engine = \Drupal::service('plugin.manager.entity_print.print_engine')->createSelectedInstance('pdf');
      $print_builder = \Drupal::service('entity_print.print_builder');
      $uri = $print_builder->savePrintable([$candidate], $print_engine);

      $file  = new \stdClass();
      $file->uri = $uri; // File path
      $file->filename = $candidate->name->value . '.pdf'; //File name
      $file->filemime = 'application/pdf'; //File mime type
      $params['attachments'][] = $file;

      $tokenService = \Drupal::token();
      $mailManager = \Drupal::service('plugin.manager.mail');
      $nataAwardCfg = \Drupal::config('nata_award.settings');

      $module = 'nata_award';
      $key = 'candidate_submitted';
      $user = current($candidate->user->referencedEntities());
      $to = $user->getEmail();
      $params['subject'] = $nataAwardCfg->get('candidate_submission_email_subject');
      $params['body'] = Markup::create($tokenService->replace($nataAwardCfg->get('candidate_submission_email_body'), ['nata_candidate' => $candidate]));
      $langcode = \Drupal::currentUser()->getPreferredLangcode();

      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);

      if ($result['result'] !== true) {
        \Drupal::logger('nata_award')->error('Could not send email to candidate after the candidate\'s submission',
          ['nata_candidate' => $candidate]);
      } else {
        $messenger->addMessage(t('Thank you for your submission. An email has been sent to you!'));
      }
    } elseif ($status == Candidate::ACCEPTED) {
      $messenger->addMessage(t('You have just accepted the nomination for the award :award!',
        [':award' => $award->name->value]));
      $uri = Url::fromRoute('entity.nata_candidate.canonical',
        ['nata_candidate' => $candidate->id()])->toString();

      $nominations = \Drupal::entityTypeManager()->getStorage('nata_nomination')->loadByProperties([
        'candidate' => $candidate->id(),
      ]);
      if (!empty($nominations)) {
        $userIds = [];
        foreach ($nominations as $nomination) {
          $userIds[] = $nomination->sponsored_by->target_id;
        }
        if (!empty($userIds)) {
          $users = User::loadMultiple($userIds);
          $userEmails = [];
          foreach ($users as $u) {
            $userEmails[] = $u->getEmail();
          }
          $recipients = implode(', ', $userEmails);

          $tokenService = \Drupal::token();
          $mailManager = \Drupal::service('plugin.manager.mail');
          $nataAwardCfg = \Drupal::config('nata_award.settings');

          $module = 'nata_award';
          $key = 'candidate_accepted';
          $params['subject'] = $nataAwardCfg->get('candidate_agreement_email_subject');
          $params['body'] = Markup::create($tokenService->replace($nataAwardCfg->get('candidate_agreement_email_body'),
            ['nata_candidate' => $candidate]));
          $langcode = \Drupal::currentUser()->getPreferredLangcode();

          $result = $mailManager->mail($module, $key, $recipients, $langcode, $params, NULL, TRUE);

          if ($result['result'] !== true) {
            \Drupal::logger('nata_award')->error('Could not send email to your sponsors\' about your nomination acceptance',
              ['nata_candidate' => $candidate]);
          } else {
            $messenger->addMessage(t('We just sent emails to your sponsors about your nomination acceptance!'));
          }
        }
      }
    }

    if (empty($uri)) {
      $uri = \Drupal::request()->query->get('destination', '/');
    }

    return new RedirectResponse($uri);
  }

  public function updateStatusAccess($candidate, $status) {
    if (!$candidate instanceof EntityInterface) {
      $candidate = Candidate::load($candidate);
    }
    return AccessResult::allowedIf(!empty($candidate) && \Drupal::currentUser()->id() == $candidate->user->target_id);
  }

  public function inviteAdvocate($candidate) {
    $form = \Drupal::formBuilder()->getForm('\Drupal\nata_award\Form\InviteAdvocateForm');

    return [
      '#type' => 'markup',
      '#markup' => $form
    ];
  }

  public function fillOutForm($candidate, $form) {
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($candidate->getEntityTypeId());
    return $viewBuilder->view($candidate);
  }

  public function fillOutAdvocateForm(EntityInterface $candidate, EntityInterface $invitation) {
    $award = current($candidate->award->referencedEntities());
    $build['candidate_advocate_invitation'] = [
      '#theme' => 'nata_award_candidate_advocate_invitation',
      '#candidate' => $candidate,
      '#invitation' => $invitation,
      '#award' => $award,
      '#cache' => ['max-age' => 0]
    ];

    $build['candidate_advocate_actions'] = [
      '#type' => 'actions',
      '#cache' => ['max-age' => 0],
      '#attributes' => ['class' => 'button-wrapper']
    ];

    if ($invitation->status->value == Invitation::INVITED) {
      $build['candidate_advocate_actions']['accept'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('nata_award.nata_invitation.update_status',
          ['invitation' => $invitation->id(), 'status' => Invitation::ACCEPTED, 'destination' => \Drupal::request()->getRequestUri()]),
        '#title' => t('Accept'),
        '#attributes' => ['class' => 'button']
      ];

      $build['candidate_advocate_actions']['decline'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('nata_award.nata_invitation.update_status',
          ['invitation' => $invitation->id(), 'status' => Invitation::DECLINED, 'destination' => \Drupal::request()->getRequestUri()]),
        '#title' => t('Decline'),
        '#attributes' => ['class' => 'button button-alt']
      ];
    } elseif ($invitation->status->value == Invitation::ACCEPTED) {
      if (!empty($award)) {
        $advocateForm = current($award->advocate_form->referencedEntities());
        if (!empty($advocateForm)) {
          $build['candidate_advocate_form'] = $advocateForm->getSubmissionForm(['entity_type' => $invitation->getEntityTypeId(), 'entity_id' => $invitation->id()]);
          $build['candidate_advocate_form']['#cache']['max-age'] = 0;
        }
      }
    } elseif ($invitation->status->value == Invitation::SUBMITTED) {
      $advocateForm = current($award->advocate_form->referencedEntities());
      if (!empty($advocateForm)) {
        $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
        $webformSubmissions = $storage->loadByProperties([
          'entity_type' => $invitation->getEntityTypeId(),
          'entity_id' => $invitation->id(),
          'webform_id' => $advocateForm->id()
        ]);
        if (!empty($webformSubmissions)) {
          $sub = current($webformSubmissions);
          $webformSubmissionViewBuilder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
          $build['candidate_advocate_submission'] = $webformSubmissionViewBuilder->view($sub);
        }
      }
    }

    $build['#cache'] = ['max-age' => 0];

    return $build;
  }

  public function fillOutAdvocateFormAccess(EntityInterface $candidate = NULL, EntityInterface $invitation = NULL) {
    $email = \Drupal::request()->query->get('email');
    $token = \Drupal::request()->query->get('token');

    if (!empty($email) && !empty($token)) {
      $i = current(\Drupal::entityTypeManager()->getStorage('nata_invitation')->loadByProperties([
        'candidate' => $candidate->id(),
        'email' => $email,
        'token' => $token
      ]));

      if (!empty($i) && $i->id() == $invitation->id()) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden('The form does not exist!');
  }

  public function viewCandidate(EntityInterface $candidate, $view = 'full') {
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($candidate->getEntityTypeId());
    return $viewBuilder->view($candidate, $view);
  }
}
