<?php

/**
 * @file
 * Theme settings form for NATA Award 2 theme.
 */

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function nata_award_2_form_system_theme_settings_alter(&$form, &$form_state) {

  $form['nata_award_2'] = [
    '#type' => 'details',
    '#title' => t('NATA Award 2'),
    '#open' => TRUE,
  ];

  $form['nata_award_2']['font_size'] = [
    '#type' => 'number',
    '#title' => t('Font size'),
    '#min' => 12,
    '#max' => 18,
    '#default_value' => theme_get_setting('font_size'),
  ];

}
