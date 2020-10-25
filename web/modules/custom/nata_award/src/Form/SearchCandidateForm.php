<?php

namespace Drupal\nata_award\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\nata_award\Entity\Award;
use Drupal\nata_award\Entity\Year;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchCandidateForm extends FormBase
{
  const CANDIDATE_PAGE_SIZE = 200;

  protected $_year = NULL;
  protected $_award = NULL;

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

  private function _buildSearchCandidatesTable($query = '', $nextRecordsUrl = '')
  {
    $nataAwardCfg = \Drupal::config('nata_award.settings');

    $apiCredentials = [
      'client_id' => $nataAwardCfg->get('salesforce_api_client_id'),
      'client_secret' => $nataAwardCfg->get('salesforce_api_client_secret'),
    ];
    $userCredentials = [
      'username' => $nataAwardCfg->get('salesforce_api_username'),
      'password' => $nataAwardCfg->get('salesforce_api_password'),
    ];
    $client = new Client(['base_uri' => $nataAwardCfg->get('salesforce_base_uri')]);
    try {
      $response = $client->post('services/oauth2/token', [
        RequestOptions::FORM_PARAMS => [
          'grant_type' => 'password',
          'client_id' => $apiCredentials['client_id'],
          'client_secret' => $apiCredentials['client_secret'],
          'username' => $userCredentials['username'],
          'password' => $userCredentials['password'],
        ]
      ]);
      $data = json_decode($response->getBody());
    } catch (\Exception $exception) {
      throw new \Exception('Unable to connect to Salesforce');
    }

    $hash = hash_hmac(
      'sha256',
      $data->id . $data->issued_at,
      $apiCredentials['client_secret'],
      true
    );

    if (base64_encode($hash) !== $data->signature) {
      throw new \Exception('Access token is invalid');
    }

    $accessToken = $data->access_token; // Valid access token

    $where_arr = ['IsPersonAccount = true'];
    if ($v = $this->_award->years_of_certification->value) {
      $where_arr[] = 'Years_Of_Certification__c >= ' . $v;
    }
    if ($v = $this->_award->years_of_membership->value) {
      $where_arr[] = 'Years_Of_Membership__c >= ' . $v;
    }

    $query_tokens = explode(',', $query);
    $last = trim($query_tokens[0]);
    $first = isset($query_tokens[1]) ? trim($query_tokens[1]) : '';

    if (!empty($last)) {
      $where_arr[] = 'LastName like \'%' . $last . '%\'';
    }

    if (!empty($first)) {
      $where_arr[] = 'FirstName like \'%' . $first . '%\'';
    }

    $q = 'SELECT Id,FirstName,LastName,NU__FullName__c,District__c,DistrictStateCode__c,ProfessionalCertifications__c,'
      . 'PrimaryAffiliationName__c,Years_Of_Certification__c,Years_of_Membership__c,PersonEmail '
      . 'FROM Account '
      . 'WHERE ' . implode(' AND ', $where_arr)
      . ' ORDER BY LastName, FirstName';

    try {
      if (!empty($nextRecordsUrl)) {
        $response = $client->get($nextRecordsUrl, [
          RequestOptions::HEADERS => [
            'Authorization' => 'Bearer ' . $accessToken,
            'X-PrettyPrint' => 1,
            'Sforce-Query-Options' => 'batchSize=' . self::CANDIDATE_PAGE_SIZE
          ]
        ]);
      } else {
        $response = $client->get('services/data/v49.0/query', [
          RequestOptions::HEADERS => [
            'Authorization' => 'Bearer ' . $accessToken,
            'X-PrettyPrint' => 1,
            'Sforce-Query-Options' => 'batchSize=' . self::CANDIDATE_PAGE_SIZE
          ],
          RequestOptions::QUERY => [
            'q' => $q
          ]
        ]);
      }
      $accounts = json_decode($response->getBody());
    } catch (\Exception $exception) {
      throw new \Exception($exception->getMessage());
    }

    $resultsFound = $accounts->totalSize;
    $results = $accounts->records;

    if (count($results) > 0) {
      $pastWinners = [];
      $pastWinnersCSVFileEntity = current($this->_award->past_winners_csv_file->referencedEntities());
      if (!empty($pastWinnersCSVFileEntity)) {
        $file = fopen($pastWinnersCSVFileEntity->uri->value, "r");
        $line = 0;
        while (!feof($file)) {
          $record = fgetcsv($file);
          if ($line > 0 && $record) {
            $pastWinners[$record[0]] = $record[1];
          }
          $line++;
        }
      }
    }

    $renderer = \Drupal::service('renderer');
    $header = ['No', 'Last Name', 'First Name', 'Full Name', 'Professional Certification', 'District', 'Years of membership', 'Years of certification', 'Note', ''];

    $rows = [];
    $awardCandidates = \Drupal::entityTypeManager()->getStorage('nata_candidate')->loadByProperties([
      'award' => $this->_award->id(),
    ]);
    $awardCandidateNimbleIds = [];
    foreach ($awardCandidates as $c) {
      $y = current($c->year->referencedEntities());
      $awardCandidateNimbleIds[$c->nimble_account_id->value] = $y->year->value;
    }
    $nominationQuery = \Drupal::entityQuery('nata_nomination');
    $nominationQuery->condition('sponsored_by', $this->_account->id());
    $nominationQuery->condition('candidate.entity.year', $this->_year->id());
    $nominationQuery->condition('candidate.entity.award', $this->_award->id());
    $nominationIds = $nominationQuery->execute();
    if (!empty($nominationIds)) {
      $nominations = \Drupal::entityTypeManager()->getStorage('nata_nomination')->loadMultiple($nominationIds);
    }

    $state = \Drupal::state();
    $page = $state->get('nata_award_search_candidate_form.page', 0);
    foreach ($results as $key => $result) {
      $note = '';
      $actionBtn = NULL;
      $couldNominate = TRUE;
      if (!empty($pastWinners[$result->Id])) {
        $couldNominate = FALSE;
        $note = t('Won in %year', ['%year' => $pastWinners[$result->Id]]);
      } else {
        if (isset($awardCandidateNimbleIds[$result->Id])) {
          $iNominated = FALSE;
          if (isset($nominations)) {
            foreach ($nominations as $n) {
              if (isset($awardCandidates[$n->candidate->target_id])) {
                $c = $awardCandidates[$n->candidate->target_id];
                if ($c->nimble_account_id->value == $result->Id) {
                  $iNominated = TRUE;
                  break;
                }
              }
            }
          }

          if ($iNominated) {
            $note = t('You already nominated this candidate!');
            $couldNominate = FALSE;
          }

        }
      }

      if ($couldNominate) {
        $candidateName = $result->NU__FullName__c;
        if (!empty($result->ProfessionalCertifications__c)) {
          $candidateName .= ', ' . $result->ProfessionalCertifications__c;
        }

        $actionBtn = [
          '#type' => 'link',
          '#url' => Url::fromRoute('nata_award.nominate',
            [
              'year' => $this->_year->id(), 'award' => $this->_award->id(),
              'candidate' => $result->Id, 'candidateName' => $candidateName, 'candidateEmail' => $result->PersonEmail,
            ]),
          '#title' => t('Select'),
          '#attributes' => ['class' => 'button']
        ];
      }
      $rows[] = [
        ($page * self::CANDIDATE_PAGE_SIZE) + $key + 1,
        $result->LastName,
        $result->FirstName,
        $result->NU__FullName__c,
        $result->ProfessionalCertifications__c,
        $result->District__c . ' ' . $result->DistrictStateCode__c,
        $result->Years_of_Membership__c,
        $result->Years_Of_Certification__c,
        $note,
        !empty($actionBtn) ? $renderer->render($actionBtn) : ''
      ];
    }

    $render_arr = [];

    if (count($rows) > 0) {
      $render_arr[] = [
        '#theme' => 'item',
        '#markup' => t('Returned records :from - :to of :total record(s)', [
          ':from' => ($page * self::CANDIDATE_PAGE_SIZE) + 1,
          ':to' => ($page * self::CANDIDATE_PAGE_SIZE) + count($rows),
          ':total' => $resultsFound]),
      ];

      $render_arr[] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows
      ];
    } else {
      $render_arr[] = [
        '#type' => 'markup',
        '#markup' => t('No results found !'),
      ];
    }

    $state = \Drupal::state();
    if (!empty($accounts->nextRecordsUrl)) {
      $state->set('nata_award_search_candidate_form.next_records_url', $accounts->nextRecordsUrl);
    } else {
      $state->delete('nata_award_search_candidate_form.next_records_url');
    }

    return $render_arr;
  }

  public function loadMoreCandidates(&$form, FormStateInterface $form_state) {
    $f['candidates-wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="candidates-section">',
      '#suffix' => '</div>'
    ];

    $state = \Drupal::state();
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#reset_search_results'])) {
      $state->delete('nata_award_search_candidate_form.next_records_url');
      $state->set('nata_award_search_candidate_form.page', 0);
    } else {
      $nextRecordsUrl = $state->get('nata_award_search_candidate_form.next_records_url');
      $page = $state->get('nata_award_search_candidate_form.page');
      $state->set('nata_award_search_candidate_form.page', $page + 1);
    }

    if (!empty($nextRecordsUrl)) {
      $f['candidates-wrapper']['candidates'] = $this->_buildSearchCandidatesTable('', $nextRecordsUrl);
    } else {
      $query = $form_state->getValue('query', '');
      if (!empty($query)) {
        $f['candidates-wrapper']['candidates'] = $this->_buildSearchCandidatesTable($query);
      }
    }
    $renderer = \Drupal::service('renderer');
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(null, $renderer->render($f)));

    $nextRecordsUrl = $state->get('nata_award_search_candidate_form.next_records_url');
    if (!empty($nextRecordsUrl)) {
      $response->addCommand(new InvokeCommand(NULL, 'showLoadMoreCandidatesBtn'));
    } else {
      $response->addCommand(new InvokeCommand(NULL, 'hideLoadMoreCandidatesBtn'));
    }

    return $response;
  }

  public function buildForm(array $form, FormStateInterface $form_state, Year $year = NULL, Award $award = NULL)
  {
    $this->_award = $award;
    $this->_year = $year;

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($award->getEntityTypeId());
    $form['award']['teaser'] = $view_builder->view($award, 'teaser');

    $form['search-form'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'search-form-wrapper']
    ];

    $form['search-form']['help-text'] = [
      '#type' => 'item',
      '#markup' => $this->t('Search for your potential candidate below by the beginning letters of their name in "last, first" format. Select the individual you wish to nominate.'),
    ];

    $form['search-form']['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Please enter at least one character above to begin your search.'),
      '#placeholder' => t('Last, First')
    ];

    $form['search-form']['search'] = [
      '#type' => 'button',
      '#value' => $this->t('Search'),
      '#ajax' => [
        'callback' => '::loadMoreCandidates',
        'wrapper' => 'candidates-section',
      ],
      '#reset_search_results' => TRUE
    ];

    $form['load_more_1_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['hidden'], 'id' => 'load-more-1-wrapper']
    ];
    $form['load_more_1_wrapper']['btn'] = [
      '#type' => 'button',
      '#value' => t('More ...'),
      '#ajax' => [
        'callback' => '::loadMoreCandidates',
        'wrapper' => 'candidates-section',
      ],
    ];

    $form['candidates-wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="candidates-section">',
      '#suffix' => '</div>'
    ];

    $form['load_more_2_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['hidden'], 'id' => 'load-more-2-wrapper']
    ];
    $form['load_more_2_wrapper']['btn'] = [
      '#type' => 'button',
      '#value' => t('More ...'),
      '#ajax' => [
        'callback' => '::loadMoreCandidates',
        'wrapper' => 'candidates-section',
      ],
      '#attributes' => ['class' => ['hidden']]
    ];

    $form['#attached']['library'][] = 'nata_award/drupal.nata_award.search_candidates';

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function getFormId()
  {
    return 'nata_award_search_candidates_form';
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
  }
}
