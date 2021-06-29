<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Provides a 'utexas_social_links_sitewide_source' migrate source.
 *
 * This provides a base source plugin for migrating Social Links config
 * from D7 into D8 Social Links blocks.
 *
 * @MigrateSource(
 *  id = "utexas_social_links_sitewide_source",
 *  source_module = "utexas_migrate"
 * )
 */
class SocialLinksSitewideSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Here we query just for the "facebook" variable, to register the count.
    // If "facebook" isn't present, we can assume the module was not used.
    $query = $this->select('variable', 'v')
      ->fields('v', array_keys($this->fields()))
      ->condition('name', 'utexas_social_accounts_facebook', '=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $all_social_accounts = $this->database->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', 'utexas_social_accounts_%', 'LIKE')
      ->execute()
      ->fetchAll();
    if (!empty($all_social_accounts)) {
      $prepared_links = [];
      $allowed_providers = [
        'facebook',
        'flickr',
        'instagram',
        'linkedin',
        'pinterest',
        'reddit',
        'snapchat',
        'tumblr',
        'twitter',
        'vimeo',
        'youtube',
      ];
      foreach ($all_social_accounts as $account) {
        $provider = str_ireplace('utexas_social_accounts_', '', $account->name);
        $url = unserialize($account->value);
        if ($url != '' && in_array(strtolower($provider), $allowed_providers)) {
          $prepared_links[] = [
            'social_account_url' => MigrateHelper::prepareLink($url),
            'social_account_name' => $provider,
          ];
        }
      }
      $row->setSourceProperty('links', $prepared_links);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('name'),
      'value' => $this->t('value'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'v',
      ],
    ];
  }

}
