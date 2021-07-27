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
    $query = $this->select('variable', 'v')
      ->fields('v', array_keys($this->fields()))
      ->condition('name', 'theme_default', '=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $this->getActiveThemeSettings($row);
    $this->getTwitterCredentials($row);
    $this->getGtmSettings($row);
    $this->getSiteInformation($row);
    $this->getSocialSharing($row);
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
   */
  public function getActiveThemeSettings(&$row) {
    $theme_machine_name = $this->getVariable('theme_default');
    $settings = $this->getVariable('theme_' . $theme_machine_name . '_settings');
    foreach ($settings as $key => $value) {
      $row->setSourceProperty($key, $value);
    }
  }

  /**
   * Custom callback to source Twitter credentials, if present.
   */
  public function getTwitterCredentials(&$row) {
    $key = $this->getVariable('utexas_twitter_widget_key');
    if (isset($key)) {
      $row->setSourceProperty('utexas_twitter_widget_key', $key);
    }
    $secret = $this->getVariable('utexas_twitter_widget_secret');
    if (isset($secret)) {
      $row->setSourceProperty('utexas_twitter_widget_secret', $secret);
    }
  }

  /**
   * Custom callback to source social sharing settings.
   */
  public function getSocialSharing(&$row) {
    $platforms = [
      'utexas_social_sharing_em' => '<a class="a2a_button_email"></a>',
      'utexas_social_sharing_fb' => '<a class="a2a_button_facebook"></a>',
      'utexas_social_sharing_tw' => '<a class="a2a_button_twitter"></a>',
      'utexas_social_sharing_li' => '<a class="a2a_button_linkedin"></a>',
      'utexas_social_sharing_pi' => '<a class="a2a_button_pinterest"></a>',
      'utexas_social_sharing_tu' => '<a class="a2a_button_tumblr"></a>',
      'utexas_social_sharing_re' => '<a class="a2a_button_reddit"></a>',
    ];
    $social_sharing = '';
    foreach ($platforms as $source => $value) {
      $key = $this->getVariable($source);
      if (isset($key)) {
        $social_sharing .= $value . '\r\n';
      }
    }
    if (!empty($social_sharing)) {
      $row->setSourceProperty('social_sharing', $social_sharing);
    }
  }


  /**
   * Custom callback to site info variables.
   */
  public function getSiteInformation(&$row) {
    $info = [
      'site_frontpage',
      'site_403',
      'site_404',
      'site_slogan',
      'site_mail',
      'site_name',
    ];
    foreach ($info as $key) {
      $value = $this->getVariable($key);
      if (isset($value)) {
        $row->setSourceProperty($key, $value);
      }
    }

  }

  /**
   * Custom callback to source GTM settings, if present.
   */
  public function getGtmSettings(&$row) {
    $gtm_code = $this->getVariable('utexas_google_tag_manager_gtm_code');
    if (isset($gtm_code)) {
      $row->setSourceProperty('utexas_google_tag_manager_gtm_code', $gtm_code);
    }
    $exclude_paths = $this->getVariable('utexas_google_tag_manager_gtm_exclude_paths');
    if (isset($exclude_paths)) {
      $row->setSourceProperty('utexas_google_tag_manager_gtm_exclude_paths', $exclude_paths);
    }
  }

  /**
   * Helper function for DB queries.
   *
   * @return array
   *   The unserialized value.
   */
  public function getVariable($name) {
    $query = $this->database->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('name', $name, '=')
      ->execute()
      ->fetch();
    return unserialize($query->value);
  }

}
