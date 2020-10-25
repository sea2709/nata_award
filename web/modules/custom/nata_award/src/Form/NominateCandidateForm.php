<?php

namespace Drupal\nata_award\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\nata_award\Entity\Award;
use Drupal\nata_award\Entity\Candidate;
use Drupal\nata_award\Entity\Nomination;
use Drupal\nata_award\Entity\Year;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NominateCandidateForm extends FormBase
{
  protected $_year = NULL;
  protected $_award = NULL;
  protected $_candidate = NULL;

  /**
   * @var AccountInterface $account
   */
  protected $_account;

  /**
   * Class constructor.
   */
  public function __construct(AccountInterface $account) {
    $this->_account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state, Year $year = NULL, Award $award = NULL, $candidate = NULL)
  {
    $this->_year = $year;
    $this->_award = $award;
    $this->_candidate = $candidate;

    $candidateName = \Drupal::request()->query->get('candidateName');
    $candidateEmail = \Drupal::request()->query->get('candidateEmail');

    $candidate = current(\Drupal::entityTypeManager()->getStorage('nata_candidate')->loadByProperties([
      'year'=> $this->_year->id(),
      'award' => $this->_award->id(),
      'nimble_account_id' => $this->_candidate
    ]));

    if (!empty($candidate)) {
      $nomination = current(\Drupal::entityTypeManager()->getStorage('nata_nomination')->loadByProperties([
        'candidate' => $candidate->id(),
        'sponsored_by' => $this->_account->id()
      ]));

      if (empty($nomination)) {
        $nomination = Nomination::create([
          'candidate' => $candidate->id(),
          'sponsored_by' => $this->_account->id()
        ]);

        $nomination->save();
      }

      return new RedirectResponse(Url::fromRoute('nata_award.nominate_successfully')->toString());
    }

    $form['#title'] = t(':candidate for :award', [':candidate' => $candidateName, ':award' => $award->name->value]);

    $form['name'] = array(
      '#type' => 'hidden',
      '#value' => $candidateName
    );

    $form['email'] = array(
      '#type' => 'hidden',
      '#value' => $candidateEmail
    );

    $nataAwardCfg = \Drupal::config('nata_award.settings');

    $form['statement'] = array(
      '#type' => 'textarea',
      '#title' => t('Nominator Statement'),
      '#description' => $nataAwardCfg->get('nomination_statement_description'),
      '#description_display' => 'before',
      '#default_value' => '',
      '#required' => TRUE,
    );

    $tokenService = \Drupal::token();
    $form['agreement_term'] = [
      '#type' => 'item',
      '#markup' => $tokenService->replace($nataAwardCfg->get('nomination_agreement_term'),
        ['nata_award' => $this->_award]),
    ];

    $form['agree'] = [
      '#type' => 'checkbox',
      '#title' => t('I agree'),
      '#size' => 10,
      '#maxlength' => 255,
      '#required' => TRUE
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Nominate',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'nata_award_nominate_candidates_form';
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $candidate = current(\Drupal::entityTypeManager()->getStorage('nata_candidate')->loadByProperties([
      'year'=> $this->_year->id(),
      'award' => $this->_award->id(),
      'nimble_account_id' => $this->_candidate
    ]));

    if (empty($candidate)) {
      $candidateData = [
        'year' => $this->_year->id(),
        'award' => $this->_award->id(),
        'nimble_account_id' => $this->_candidate,
        'status' => Candidate::NOMINATED,
        'sponsored_by' => $this->_account->id(),
        'sponsor_statement' => $form_state->getValue('statement'),
        'name' => $form_state->getValue('name'),
        'email' => $form_state->getValue('email'),
      ];

      $user = current(\Drupal::entityTypeManager()->getStorage('user')->loadByProperties([
        'field_nimble_account_id' => $this->_candidate
      ]));

      if (!empty($user)) {
        $candidateData['user'] = $user->id();
      }

      $candidate = Candidate::create($candidateData);

      $candidate->save();
    }

    $nomination = Nomination::create([
      'candidate' => $candidate->id(),
      'sponsored_by' => $this->_account->id()
    ]);

    $nomination->save();

    $form_state->setRedirect('nata_award.nominate_successfully');
  }
}
