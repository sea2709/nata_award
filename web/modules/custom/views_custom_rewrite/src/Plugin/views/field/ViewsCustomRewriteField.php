<?php

namespace Drupal\views_custom_rewrite\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Custom;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_custom_rewrite_field")
 */
class ViewsCustomRewriteField extends Custom {

  /**
   * Rewrite the fields' value by the fields' labels in field settings.
   */
  public function advancedRender(ResultRow $values) {
    $this->options['alter']['alter_text'] = TRUE;
    $value = parent::advancedRender($values);

    if (!empty($this->options['rewrite_info']['items'])) {
      foreach ($this->options['rewrite_info']['items'] as $item) {
        if ($item['value'] == $value) {
          $value = $item['label'];
          break;
        }
      }
    }

    return $value;
  }

  /**
   * Build settings form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Provide 2 options to start when we are in a new group.
    if (count($this->options['rewrite_info']['items']) == 0) {
      $this->options['rewrite_info']['items'] = array_fill(1, 2, []);
    }

    $form['#attached']['library'][] = 'views_custom_rewrite/views_custom_rewrite.library';

    $header = ['Value', 'Label', 'Operation'];

    $form['rewrite_info']['items'] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    foreach ($this->options['rewrite_info']['items'] as $item_id => $item) {
      $form['rewrite_info']['items'][$item_id]['value'] = [
        '#title' => $this->t('Value'),
        '#type' => 'textfield',
        '#title_display' => 'invisible',
        '#size' => 20,
        '#default_value' => isset($item['value']) ? $item['value'] : NULL,
        '#attributes' => ['class' => ['custom_rewrite_field_value_text']],
      ];
      $form['rewrite_info']['items'][$item_id]['label'] = [
        '#title' => $this->t('label'),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#size' => 20,
        '#default_value' => isset($item['label']) ? $item['label'] : NULL,
        '#attributes' => ['class' => ['custom_rewrite_field_label_text']],
      ];
      $form['rewrite_info']['items'][$item_id]['remove']['btn'] = [
        '#type' => 'button',
        '#value' => t('Remove'),
        '#attributes' => ['class' => ['custom_rewrite_field_remove_btn']],
      ];
    }

    $form['rewrite_info']['add_item'] = [
      '#prefix' => '<div class="views-build-group clear-block">',
      '#suffix' => '</div>',
      '#type' => 'submit',
      '#value' => $this->t('Add another item'),
      '#submit' => [[$this, 'addItem']],
    ];
  }

  /**
   * Callback function for adding item.
   */
  public function addItem($form, FormStateInterface $form_state) {
    $item = &$this->options;

    // Add a new row.
    $item['rewrite_info']['items'][] = [];
    $form_state->set('rerender', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Handle form setting submission.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $options = &$form_state->getValue('options');

    // Remove empty options.
    if (!empty($options['rewrite_info']['items'])) {
      foreach ($options['rewrite_info']['items'] as $key => $item) {
        if (empty($item['value']) && empty($item['label'])) {
          unset($options['rewrite_info']['items'][$key]);
        }
      }
    }
    if (!empty($options['rewrite_info']['items'])) {
      $this->options['rewrite_info']['items'] = $options['rewrite_info']['items'];
    }
  }

}
