<?php

namespace Drupal\nata_nimble\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Action description.
 *
 * @Action(
 *   id = "nimble_ams_account_query_test_action",
 *   label = @Translation("Nimble Account Query Test"),
 *   type = "user"
 * )
 */
class NimbleAccountSync extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    //kint($this->configuration);
    $config = \Drupal::config('openid_connect.settings.nimble');
    $settings = $config->get('settings');
    $request_body = new \stdClass();
    $request_body->Request = new \stdClass();
    $request_body->Request->Parameters = new \stdClass();
    if (!empty($settings['endpoint'])) {
      $request_body->Request->Name = !empty($settings['account_query_name']) ? $settings['account_query_name'] : '';
      $request_body->Request->AuthenticationKey = !empty($settings['account_query_key']) ? $settings['account_query_key'] : '';
      if ($this->configuration['matching'] == 0 || $this->configuration['matching'] == 2) {
        $request_body->Request->Parameters->Id_param = $account->{$settings['account_id_field_name']}->value;
      }
      else {
        $request_body->Request->Parameters->Id_param = '';
      }
      if ($this->configuration['matching'] == 1 || $this->configuration['matching'] == 2) {
        $request_body->Request->Parameters->NATAId__c_param = $account->{$settings['member_number_field_name']}->value;
      }
      $api_path = !empty($settings['account_query_append_url']) ? $settings['account_query_append_url'] : '';
      $full_path = $settings['endpoint'] . '/' . $api_path;

      $response = \Drupal::httpClient()
        ->post($full_path, [
          'json'        => $request_body,
          'http_errors' => FALSE,
        ]);
      if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
        $response_contents = $response->getBody()->getContents();
        $response_contents_unpacked = json_decode($response_contents, TRUE);
        if ($this->configuration['logging'] == 1) {
          \Drupal::messenger()->addMessage('<pre>' . print_r($response_contents_unpacked, TRUE) . '</pre>');
        }
        else {
          \Drupal::logger('nimble_ams_vbo')->notice('<pre>' . print_r($response_contents_unpacked, TRUE) . '</pre>');
        }
      }
      else {
        // error, log it
        \Drupal::logger('nimble_ams_vboError-StatusCode')->notice($response->getStatusCode());
        \Drupal::logger('nimble_ams_vboError-ReasonPhrase')->notice($response->getReasonPhrase());
        \Drupal::logger('nimble_ams_vboError-ResponseBody')->notice($response->getBody()->getContents());
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['logging'] = [
      '#type' => 'radios',
      '#options' => [1 => 'Show API results on screen', 2 => 'Log API results to Drupal log'],
      '#title' => 'API Result logging',
      '#description' => 'Showing API results on screen after action works best with the devel module is enabled. Showing on screen should only be done for a few rows only.',
      '#default_value' => 1,
    ];
    $form['matching'] = [
      '#type' => 'radios',
      '#options' => [0 => 'Nimble Account ID', 1 => 'Member number', 2 => 'Both'],
      '#title' => 'Match by user fields',
      '#description' => 'Query by which user fields.',
      '#default_value' => 0,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }
}
