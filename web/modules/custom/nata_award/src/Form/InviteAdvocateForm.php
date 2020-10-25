<?php

namespace Drupal\nata_award\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\nata_award\Entity\Candidate;
use Drupal\nata_award\Entity\Invitation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for year forms.
 */
class InviteAdvocateForm extends FormBase {
  protected $_candidate;

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

  public function getFormId() {
    return 'nata_candidate_invitation';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, Candidate $candidate = NULL)
  {
    $this->_candidate = $candidate;

    $nataAwardCfg = \Drupal::config('nata_award.settings');

    $form['instructions'] = [
      '#type' => 'markup',
      '#markup' => $nataAwardCfg->get('advocate_form_instructions')
    ];

    $form['info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'user-wrapper']
    ];

    $form['info']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE
    ];

    $form['info']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE
    ];

    # the options to display in our form radio buttons
    $options = Invitation::RELATIONSHIP_OPTIONS;
    foreach ($options as $key => $value) {
      $options[$key] = t($value);
    }

    $form['relationship'] = array(
      '#type' => 'radios',
      '#title' => t('Relationship to Candidate'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => t('Each advocate should be chosen from a different relationship category. To assist you with this, categories which you have already chosen for other advocates will no longer appear in your list of choices.'),
    );

    $form['cancel'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.nata_candidate.canonical', ['nata_candidate' => $this->_candidate->id()]),
      '#title' => t('<< Back'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Invitation'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    $user = \Drupal::currentUser();
    if ($user && $user->getEmail() == $form_state->getValue('email')) {
      $form_state->setErrorByName('email', t('This email is invalid!'));
    }

    $invitations = \Drupal::entityTypeManager()->getStorage('nata_invitation')->loadByProperties([
      'candidate' => $this->_candidate->id(),
      'email' => $form_state->getValue('email')
    ]);

    if (!empty($invitations)) {
      $form_state->setErrorByName('email', t('This email had already got the invitation!'));
    }
  }


  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();

    $random = new \Drupal\Component\Utility\Random();
    $token = $random->name(32);

    $invitation = Invitation::create([
      'candidate' => $this->_candidate->id(),
      'user' => $this->_account->id(),
      'name' => $values['name'],
      'email' => $values['email'],
      'relationship' => $values['relationship'],
      'status' => Invitation::INVITED,
      'token' => $token
    ]);

    $invitation->save();

    $this->messenger()->addMessage('Send invitation successfully !');

    $form_state->setRedirect('entity.nata_candidate.canonical', ['nata_candidate' => $this->_candidate->id()]);
  }
}
