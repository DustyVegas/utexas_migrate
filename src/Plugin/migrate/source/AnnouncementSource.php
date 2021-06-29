<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Provides the source for the sitewide announcement.
 *
 * @MigrateSource(
 *  id = "utexas_announcement_source",
 *  source_module = "utexas_migrate"
 * )
 */
class AnnouncementSource extends SqlBase {

  /**
   * Announcement settings.
   *
   * @var array
   */
  public static $settings = [
    'utexas_announcement_active',
    'utexas_announcement_title',
    'utexas_announcement_title_icon',
    'utexas_announcement_background',
    'utexas_announcement_body',
    'utexas_announcement_cta',
    'utexas_announcement_destination_url',
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $has_announcement = MigrateHelper::getVariable('utexas_announcement_active');
    if ($has_announcement) {
      $query = $this->select('variable', 'v')
        ->fields('v', array_keys($this->fields()))
        ->condition('name', 'utexas_announcement_active', '=');
      return $query;
    }
    // There is no active announcement.
    // Return a query object that will evaluate as count = 0.
    $query = $this->select('variable', 'v')
      ->fields('v', array_keys($this->fields()))
      ->condition('name', 'invalid_key_used_for_migration', '=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    foreach (self::$settings as $setting) {
      $value = MigrateHelper::getVariable($setting);
      $row->setSourceProperty($setting, $value);
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
