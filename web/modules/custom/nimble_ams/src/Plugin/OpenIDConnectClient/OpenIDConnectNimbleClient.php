<?php

namespace Drupal\nimble_ams\Plugin\OpenIDConnectClient;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Nimble AMS OpenID Connect client.
 *
 * Used primarily to login to Drupal sites powered by oauth2_server or PHP
 * sites powered by oauth2-server-php.
 *
 * @OpenIDConnectClient(
 *   id = "nimble",
 *   label = @Translation("Nimble")
 * )
 */
class OpenIDConnectNimbleClient extends OpenIDConnectClientBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'endpoint' => '',
      'authorization_endpoint' => '',
      'token_endpoint' => '',
      'userinfo_endpoint' => '',
      'instance' => 'staging',
      'enable_logging' => FALSE,
      'account_query_enable' => FALSE,
      'sso_login_redirect_whitelist' => '',
      'auth_user_redirect_path' => '',
      'auth_non_member_user_redirect_path' => '',
      'sso_login_auth_non_member_user_redirect_path' => '',
      'sso_login_auth_user_redirect_path' => '',
      'account_query_append_url' => 'services/apexrest/NUINT/NUIntegrationService',
      'account_query_name' => 'CMSQuery',
      'account_query_key' => '',
      'member_number_field_name' => '',
      'membership_type_field_name' => '',
      'membership_type_id_field_name' => '',
      'is_member_field_name' => '',
      'is_staff_field_name' => '',
      'is_student_field_name' => '',
      'membership_join_date_field_name' => '',
      'membership_start_date_field_name' => '',
      'membership_end_date_field_name' => '',
      'membership_end_date_overrride_field_name' => '',
      'district_field_name' => '',
      'gender_field_name' => '',
      'age_field_name' => '',
      'job_setting_field_name' => '',
      'title_field_name' => '',
      'full_name_field_name' => '',
      'name_field_name' => '',
      'first_name_field_name' => '',
      'last_name_field_name' => '',
      'account_id_field_name' => '',
      'company_account_id_field_name' => '',
      'company_name_field_name' => '',
      'extra_email_field_name' => '',
      'roles_automatic_roles' => [],
      'roles_member_role' => '',
      'roles_non_member_role' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
    $user_fields = ['' => 'None'];
    foreach ($field_map['user'] as $field_name => $field) {
      if (strpos($field_name, 'field_') === 0) {
        $user_fields[$field_name] = $field_name;
      }
    }
    $roles = user_role_names(TRUE);
    if (isset($roles['authenticated'])) {
      unset($roles['authenticated']);
    }
    $form['endpoint'] = [
      '#title' => $this->t('Endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['endpoint'],
    ];
    $form['authorization_endpoint'] = [
      '#title' => $this->t('Authorization endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['authorization_endpoint'],
    ];
    $form['token_endpoint'] = [
      '#title' => $this->t('Token endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['token_endpoint'],
    ];
    $form['userinfo_endpoint'] = [
      '#title' => $this->t('UserInfo endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['userinfo_endpoint'],
    ];
    $form['sso_login_redirect_whitelist'] = [
      '#title' => $this->t('SSO Login retUrl parameter hosts whitelist'),
      '#description' => $this->t('Enter hosts, one per line, that are allowed to be redirected to with the retUrl url parameter on the sso-login path, e.g stage-pdc.nata.org'),
      '#type' => 'textarea',
      '#default_value' => !empty($this->configuration['sso_login_redirect_whitelist']) ? implode("\n", $this->configuration['sso_login_redirect_whitelist']) : '',
    ];
    $form['auth_user_redirect_path'] = [
      '#title' => $this->t('SSO Authenticated Member user redirect path'),
      '#description' => $this->t('Must be a valid internal Drupal path. In Drupal 8, valid internal paths start with "/", e.g. "/user/password"'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['auth_user_redirect_path'],
    ];
    $form['auth_non_member_user_redirect_path'] = [
      '#title' => $this->t('SSO Authenticated Non-Member user redirect path'),
      '#description' => $this->t('Must be a valid internal Drupal path. In Drupal 8, valid internal paths start with "/", e.g. "/user/password"'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['auth_non_member_user_redirect_path'],
    ];
    $form['sso_login_auth_user_redirect_path'] = [
      '#title' => $this->t('SSO Login Authenticated Member user redirect path'),
      '#description' => $this->t('Redirect from the /sso-login path for already logged in member users. Must be a valid internal Drupal path. In Drupal 8, valid internal paths start with "/", e.g. "/user/password"'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['sso_login_auth_user_redirect_path'],
    ];
    $form['sso_login_auth_non_member_user_redirect_path'] = [
      '#title' => $this->t('SSO Login Authenticated Non-Member user redirect path'),
      '#description' => $this->t('Redirect from the /sso-login path for already logged in non member users. Must be a valid internal Drupal path. In Drupal 8, valid internal paths start with "/", e.g. "/user/password"'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['sso_login_auth_non_member_user_redirect_path'],
    ];
    $form['instance'] = [
      '#title' => $this->t('Nimble Instance'),
      '#type' => 'select',
      '#options' => ['staging' => 'Staging', 'production' => 'Production'],
      '#default_value' => $this->configuration['instance'],
      '#required' => TRUE,
    ];
    $form['enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Logging'),
      '#default_value' => $this->configuration['enable_logging'],
    ];
    $form['account_query_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AccountQuery user field sync '),
      '#default_value' => $this->configuration['account_query_enable'],
    ];
    $form['account_query_append_url'] = [
      '#title' => $this->t('Append URL'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['account_query_append_url'],
    ];
    $form['account_query_name'] = [
      '#title' => $this->t('AccountQuery Name'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['account_query_name'],
    ];
    $form['account_query_key'] = [
      '#title' => $this->t('AccountQuery Authentication Key'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['account_query_key'],
    ];
    $form['member_number_field_name'] = [
      '#title' => $this->t('Member Number field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['member_number_field_name'],
    ];
    $form['membership_type_field_name'] = [
      '#title' => $this->t('Membership Type field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['membership_type_field_name'],
    ];
    $form['membership_type_id_field_name'] = [
      '#title' => $this->t('Membership Type ID field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['membership_type_id_field_name'],
    ];
    $form['is_member_field_name'] = [
      '#title' => $this->t('Is Member? field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['is_member_field_name'],
    ];
    $form['is_staff_field_name'] = [
      '#title' => $this->t('Is Staff? field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['is_staff_field_name'],
    ];
    $form['is_student_field_name'] = [
      '#title' => $this->t('Is Student? field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['is_student_field_name'],
    ];
    $form['membership_join_date_field_name'] = [
      '#title' => $this->t('Membership Join Date field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['membership_join_date_field_name'],
    ];
    $form['membership_start_date_field_name'] = [
      '#title' => $this->t('Membership Start Date field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['membership_start_date_field_name'],
    ];
    $form['membership_end_date_field_name'] = [
      '#title' => $this->t('Membership End Date field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['membership_end_date_field_name'],
    ];
    $form['membership_end_date_override_field_name'] = [
      '#title' => $this->t('Membership End Date Override field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['membership_end_date_override_field_name'],
    ];
    $form['district_field_name'] = [
      '#title' => $this->t('District field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['district_field_name'],
    ];
    $form['gender_field_name'] = [
      '#title' => $this->t('Gender field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['gender_field_name'],
    ];
    $form['age_field_name'] = [
      '#title' => $this->t('Age field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['age_field_name'],
    ];
    $form['job_setting_field_name'] = [
      '#title' => $this->t('Job setting field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['job_setting_field_name'],
    ];
    $form['title_field_name'] = [
      '#title' => $this->t('Title field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['title_field_name'],
    ];
    $form['full_name_field_name'] = [
      '#title' => $this->t('Full name field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['full_name_field_name'],
    ];
    $form['name_field_name'] = [
      '#title' => $this->t('Name field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['name_field_name'],
    ];
    $form['first_name_field_name'] = [
      '#title' => $this->t('First name field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['first_name_field_name'],
    ];
    $form['last_name_field_name'] = [
      '#title' => $this->t('Last name field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['last_name_field_name'],
    ];
    $form['account_id_field_name'] = [
      '#title' => $this->t('Nimble Account ID field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['account_id_field_name'],
    ];
    $form['company_account_id_field_name'] = [
      '#title' => $this->t('Nimble Company Account ID field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['company_account_id_field_name'],
    ];
    $form['company_name_field_name'] = [
      '#title' => $this->t('Nimble Company Name field name'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['company_name_field_name'],
    ];
    $form['extra_email_field_name'] = [
      '#title' => $this->t('Extra email field name'),
      '#description' => $this->t('Must be a field of type "Email"'),
      '#type' => 'select',
      '#options' => $user_fields,
      '#default_value' => $this->configuration['extra_email_field_name'],
    ];
    $form['roles_automatic_roles'] = [
      '#title' => $this->t('Roles to assign automatically'),
      '#type' => 'select',
      '#options' => $roles,
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['roles_automatic_roles'],
    ];
    $form['roles_member_role'] = [
      '#title' => $this->t('Member Role'),
      '#type' => 'select',
      '#options' => $roles,
      '#default_value' => $this->configuration['roles_member_role'],
    ];
    $form['roles_non_member_role'] = [
      '#title' => $this->t('Non Member Role'),
      '#type' => 'select',
      '#options' => $roles,
      '#default_value' => $this->configuration['roles_non_member_role'],
    ];
    $form['roles_allowed_user_form'] = [
      '#title' => $this->t('Roles allowed to see user edit form'),
      '#type' => 'select',
      '#options' => $roles,
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['roles_allowed_user_form'],
    ];
    $membership_types = ['' => 'None'] + $this->getMembershipTypes($this->configuration['instance']);
    $roles_membership_types = $this->configuration['roles_membership_types'];
    if (!empty($roles_membership_types)) {
      foreach ($roles_membership_types as $type) {
        $roles_membership_types_default_values[$type['role']] = $type['membership_type'];
      }
    }
    foreach ($roles as $rid => $role) {
      $form['roles_mapped_membership_types'][$rid] = [
        '#title'         => $role,
        '#type'          => 'select',
        '#options'       => $membership_types,
        '#default_value' => !empty($roles_membership_types_default_values[$rid]) ? $roles_membership_types_default_values[$rid] : '',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    return [
      'authorization' => $this->configuration['authorization_endpoint'],
      'token' => $this->configuration['token_endpoint'],
      'userinfo' => $this->configuration['userinfo_endpoint'],
    ];
  }

  /**
   * Get an array of membership types keyed by Nimble membership type id
   *
   * @param string $instance
   *
   * @return array
   */
  public function getMembershipTypes($instance = 'staging') {
    if ($instance == 'staging') {
      return [
        'a0r0v000001XKbYAAW' => 'Honorary',
        'a0r0v000001XKbJAAW' => 'Licensed Professional',
        'a0r0v000001T5xDAAS' => 'Certified Professional',
        'a0r0v000001T5xEAAS' => 'Certified Student',
        'a0r0v000001T5xFAAS' => 'Student',
        'a0r0v000001T5xGAAS' => 'Certified Retired',
        'a0r0v000001T5xHAAS' => 'Retired',
        'a0r6A000001Xz9GQAS' => 'Associate',
      ];
    }
    elseif ($instance == 'production') {
      return [
        'a0r6A0000029hR0QAI' => 'Honorary',
        'a0r6A000001wUhoQAE' => 'Licensed Professional',
        'a0r6A000001wUhiQAE' => 'Certified Professional',
        'a0r6A000001wUhjQAE' => 'Certified Student',
        'a0r6A000001wUhlQAE' => 'Student',
        'a0r6A000001wUhmQAE' => 'Certified Retired',
        'a0r6A000001wUhnQAE' => 'Retired',
        'a0r6A000001wUhkQAE' => 'Associate',
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    if (!empty($values['roles_mapped_membership_types'])) {
      foreach ($values['roles_mapped_membership_types'] as $rid => $membership_type) {
        $roles_to_membership_type_value[] = ['role' => $rid, 'membership_type' => $membership_type];
      }
      $form_state->setValue('roles_membership_types', $roles_to_membership_type_value);
    }
    $sso_login_redirect_whitelist_domains = [];
    if (!empty($values['sso_login_redirect_whitelist'])) {
      $sso_login_redirect_whitelist_domains = explode("\n", str_replace("\r", "", trim($values['sso_login_redirect_whitelist'])));
    }
    $form_state->setValue('sso_login_redirect_whitelist', $sso_login_redirect_whitelist_domains);
  }
}
