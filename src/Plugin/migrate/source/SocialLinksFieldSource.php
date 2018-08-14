<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Provides a 'utexas_social_links_field_source' migrate source.
 *
 * This provides a base source plugin for migrating Social Links field
 * from D7 into D8 Social Links blocks.
 *
 * @MigrateSource(
 *  id = "utexas_social_links_field_source",
 *  source_module = "utexas_migrate"
 * )
 */
class SocialLinksFieldSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $source_field = 'field_utexas_social_links';
    $query = $this->select('field_data_field_utexas_social_links', 'f')
      ->fields('f', array_keys($this->fields()));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $links = unserialize($row->getSourceProperty('field_utexas_social_links_links'));
    $prepared_links = [];
    if (!empty($links)) {
      $inc = 0;
      $allowed_providers = [
        'facebook',
        'flickr',
        'googleplus',
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
      foreach ($links as $provider => $link) {
        if (in_array(strtolower($provider), $allowed_providers)) {
          $prepared_links[] = [
            'social_account_url' => MigrateHelper::prepareLink($link),
            'social_account_name' => strtolower($provider),
            'delta' => $inc,
          ];
          $inc++;
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
      'entity_id' => $this->t('Entity ID'),
      'field_utexas_social_links_headline' => $this->t('Headline'),
      'field_utexas_social_links_links' => $this->t('Links'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

}
