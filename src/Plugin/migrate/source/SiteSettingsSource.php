<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * A source plugin.
 *
 * This provides a base source plugin for migrating config
 * originating from D7's variable table into D8.
 *
 * @MigrateSource(
 *  id = "utexas_site_settings_source",
 *  source_module = "utexas_migrate"
 * )
 */
class SiteSettingsSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // An examination query needs to be run to check what the default theme is,
    // and then to check whether there is footer text defined for that theme,
    // supporting sites using forty_acres OR a subtheme of forty_acres.
    $theme_data = $this->getActiveThemeSettings();

    if (isset($theme_data['values']->value)) {
      $settings = unserialize($theme_data['values']->value);
      if (!empty($settings)) {
        // Query for the "theme_THEMENAME_settings" variable,
        // and parse it in prepareRow().
        $query = $this->select('variable', 'v')
          ->fields('v', array_keys($this->fields()))
          ->condition('name', 'theme_' . $theme_data['name'] . '_settings', '=');
        return $query;
      }
    }
    // Backup: there are no theme settings defined.
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
    $theme_data = $this->getActiveThemeSettings();
    if (isset($theme_data['values'])) {
      $settings = unserialize($theme_data['values']->value);
      if (!empty($settings)) {
        // Default breadcrumb value.
        $default_breadcrumb_display = !empty($settings['utexas_standard_page_breadcrumb']) ? $settings['utexas_standard_page_breadcrumb'] : 1;
        $row->setSourceProperty('default_breadcrumb_display', $default_breadcrumb_display);
      }
      else {
        // If there is no theme data, abandon the import.
        return FALSE;
      }
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

  /**
   * Custom callback to retrieve the active theme's settings.
   *
   * @return array
   *   The 'name' is the theme machine name & the 'values' are the settings.
   */
  public function getActiveThemeSettings() {
    $theme = $this->database->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('name', 'theme_default', '=')
      ->execute()
      ->fetch();
    $theme_machine_name = unserialize($theme->value);
    $key = 'theme_' . $theme_machine_name . '_settings';
    $query = $this->database->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', $key, '=')
      ->execute()
      ->fetch();
    return ['name' => $theme_machine_name, 'values' => $query];
  }

}
