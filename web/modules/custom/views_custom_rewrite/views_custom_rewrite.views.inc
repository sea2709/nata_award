<?php

/**
 * @file
 * Include file for defining views handlers and data.
 */

/**
 * Implements hook_views_data().
 */
function views_custom_rewrite_views_data() {
  $data['views_custom_rewrite']['table']['group'] = t('Views');
  $data['views_custom_rewrite']['table']['join'] = [
    '#global' => [],
  ];

  $data['views_custom_rewrite']['views_custom_rewrite_field'] = [
    'title' => t('Views Custom Rewrite'),
    'field' => [
      'title' => t('Views Custom Rewrite'),
      'help' => t('Custom rewrite view output.'),
      'id' => 'views_custom_rewrite_field',
    ],
  ];

  return $data;
}
