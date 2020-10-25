<?php

namespace Drupal\nata_award\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 *
 * @internal
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nata_award_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nata_award.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nata_award.settings');

    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];

    $form['general_settings']['general_bcc_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BCC Emails'),
      '#description' => $this->t('Multiple emails are separated by comma'),
      '#default_value' => $config->get('general_bcc_email'),
    ];

    $form['general_settings']['about_me_form'] = [
      '#type' => 'webform_entity_select',
      '#target_type' => 'webform',
      '#title' => $this->t('About Me Form'),
      '#default_value' => $config->get('about_me_form'),
    ];

    $form['advocate_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advocate Form'),
      '#open' => TRUE,
    ];

    $form['advocate_settings']['advocate_form_instructions'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Advocate Form instructions'),
      '#default_value' => $config->get('advocate_form_instructions'),
      '#required' => TRUE,
    ];

    $form['advocate_settings']['advocate_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('advocate_email_subject'),
      '#description' => $this->t('Request for Candidate Advocate Form email subject.'),
      '#required' => TRUE,
    ];

    $form['advocate_settings']['advocate_email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('advocate_email_body'),
      '#description' => $this->t('Request for Candidate Advocate Form email body.'),
      '#required' => TRUE,
    ];

    $form['candidate_submission_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Candidate Submission'),
      '#open' => TRUE,
    ];

    $form['candidate_submission_settings']['candidate_submission_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('candidate_submission_email_subject'),
      '#description' => $this->t('Candidate Submission email subject.'),
      '#required' => TRUE,
    ];

    $form['candidate_submission_settings']['candidate_submission_email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('candidate_submission_email_body'),
      '#description' => $this->t('Candidate Submission email body.'),
      '#required' => TRUE,
    ];

    $form['nomination_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Nomination Notification'),
      '#open' => TRUE,
    ];

    $form['nomination_settings']['nomination_statement_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Nomination Statement Description'),
      '#default_value' => $config->get('nomination_statement_description'),
      '#required' => TRUE,
    ];

    $form['nomination_settings']['nomination_agreement_term'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Agreement Term'),
      '#default_value' => $config->get('nomination_agreement_term'),
      '#required' => TRUE,
    ];

    $form['nomination_settings']['nomination_settings_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('nomination_settings_email_subject'),
      '#description' => $this->t('Nomination notification email subject.'),
      '#required' => TRUE,
    ];

    $form['nomination_settings']['nomination_settings_email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('nomination_settings_email_body'),
      '#description' => $this->t('Nomination notification email body.'),
      '#required' => TRUE,
    ];


    $form['candidate_agreement'] = [
      '#type' => 'details',
      '#title' => $this->t('Nomination Agreement'),
      '#open' => TRUE,
    ];

    $form['candidate_agreement']['candidate_agreement_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('candidate_agreement_email_subject'),
      '#description' => $this->t('Candidate agreement email subject.'),
      '#required' => TRUE,
    ];

    $form['candidate_agreement']['candidate_agreement_email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => $config->get('candidate_agreement_email_body'),
      '#description' => $this->t('Candidate agreement email body.'),
      '#required' => TRUE,
    ];

    $form['salesforce_api_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Salesforce API'),
      '#open' => FALSE,
    ];

    $form['salesforce_api_settings']['salesforce_base_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Salesforce Base URI'),
      '#default_value' => $config->get('salesforce_base_uri'),
      '#required' => TRUE,
    ];

    $form['salesforce_api_settings']['salesforce_api_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('salesforce_api_client_id'),
      '#required' => TRUE,
    ];

    $form['salesforce_api_settings']['salesforce_api_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('salesforce_api_client_secret'),
      '#required' => TRUE,
    ];

    $form['salesforce_api_settings']['salesforce_api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('salesforce_api_username'),
      '#required' => TRUE,
    ];

    $form['salesforce_api_settings']['salesforce_api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('salesforce_api_password'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nata_award.settings');

    $config->set('general_bcc_email', $form_state->getValue('general_bcc_email'));
    $config->set('about_me_form', $form_state->getValue('about_me_form'));

    $config->set('advocate_form_instructions', $form_state->getValue('advocate_form_instructions')['value']);
    $config->set('advocate_email_subject', $form_state->getValue('advocate_email_subject'));
    $config->set('advocate_email_body', $form_state->getValue('advocate_email_body')['value']);

    $config->set('candidate_submission_email_subject', $form_state->getValue('candidate_submission_email_subject'));
    $config->set('candidate_submission_email_body', $form_state->getValue('candidate_submission_email_body')['value']);

    $config->set('nomination_statement_description', $form_state->getValue('nomination_statement_description'));
    $config->set('nomination_agreement_term', $form_state->getValue('nomination_agreement_term')['value']);
    $config->set('nomination_settings_email_subject', $form_state->getValue('nomination_settings_email_subject'));
    $config->set('nomination_settings_email_body', $form_state->getValue('nomination_settings_email_body')['value']);

    $config->set('candidate_agreement_email_subject', $form_state->getValue('candidate_agreement_email_subject'));
    $config->set('candidate_agreement_email_body', $form_state->getValue('candidate_agreement_email_body')['value']);

    $config->set('salesforce_base_uri', $form_state->getValue('salesforce_base_uri'));
    $config->set('salesforce_api_client_id', $form_state->getValue('salesforce_api_client_id'));
    $config->set('salesforce_api_client_secret', $form_state->getValue('salesforce_api_client_secret'));
    $config->set('salesforce_api_username', $form_state->getValue('salesforce_api_username'));
    $config->set('salesforce_api_password', $form_state->getValue('salesforce_api_password'));

    $config->save();

    parent::submitForm($form, $form_state);
  }
}
