<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\google_tag\Entity\Container;
use Drupal\utexas_migrate\MigrateHelper;
use Drupal\twitter_profile_widget\Authorization;

/**
 * Provides a 'utexas_site_settings_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_site_settings_destination"
 * )
 */
class SiteSettingsDestination extends MediaDestination implements MigrateDestinationInterface {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // One-to-one migrateable settings.
    // Additional site settings may be added here as needed.
    $settings = [
      'default_breadcrumb_display' => [
        'key' => 'breadcrumbs_visibility.content_type.utexas_flex_page',
        'value' => 'display_breadcrumbs',
      ],
      'utexas_twitter_widget_key' => [
        'key' => 'twitter_profile_widget.settings',
        'value' => 'twitter_widget_key',
      ],
      'utexas_twitter_widget_secret' => [
        'key' => 'twitter_profile_widget.settings',
        'value' => 'twitter_widget_secret',
      ],
      'site_mail' => [
        'key' => 'system.site',
        'value' => 'mail',
      ],
      'site_name' => [
        'key' => 'system.site',
        'value' => 'name',
      ],
      'site_slogan' => [
        'key' => 'system.site',
        'value' => 'slogan',
      ],
      'parent_link_title' => [
        'key' => 'forty_acres.settings',
        'value' => 'parent_link_title',
      ],
      'parent_link' => [
        'key' => 'forty_acres.settings',
        'value' => 'parent_link',
      ],
      'logo_height' => [
        'key' => 'forty_acres.settings',
        'value' => 'logo_height',
      ],
    ];
    foreach ($settings as $source => $destination) {
      $data = $row->getSourceProperty($source);
      // Convert QuickSites email address.
      if ($source === 'site_mail') {
        if ($row->getSourceProperty($source) == 'quicksites-admins@utlists.utexas.edu') {
          $data = 'wcs-drupal-site-admins@utlists.utexas.edu';
        }
      }
      $config = \Drupal::configFactory()->getEditable($destination['key']);
      $config->set($destination['value'], $data);
      $config->save();
    }

    // Establish initial Twitter token.
    if ($row->getSourceProperty('utexas_twitter_widget_secret')) {
      Authorization::getToken($row->getSourceProperty('utexas_twitter_widget_key'), $row->getSourceProperty('utexas_twitter_widget_secret'));
    }

    // Front page & 403 & 404 pages.
    $config = \Drupal::configFactory()->getEditable('system.site');
    // @todo: evaluate whether to add prepareLink() wrapper?
    $front = MigrateHelper::getDestinationFromSource($row->getSourceProperty('site_frontpage'));
    $site403 = MigrateHelper::getDestinationFromSource($row->getSourceProperty('site_403'));
    $site404 = MigrateHelper::getDestinationFromSource($row->getSourceProperty('site_404'));
    $config->set('page.front', $front);
    $config->set('page.403', $site403);
    $config->set('page.404', $site404);
    $config->save();

    // Google CSE.
    $cse_id = $row->getSourceProperty('utexas_google_cse_id');
    if ($cse_id) {
      $google_cse = \Drupal::configFactory()->getEditable('search.page.google_cse_search');
      $cse_config = $google_cse->get('configuration');
      $cse_config['cx'] = $cse_id;
      $google_cse->set('configuration', $cse_config);
      $google_cse->save();
    }

    // Validate if there is a google tag to migrate.
    if ($row->getSourceProperty('utexas_google_tag_manager_gtm_code') !== NULL) {
      // Create container with GTM source settings.
      $container = new Container([], 'google_tag_container');
      $container->enforceIsNew();
      $container->set('id', 'utexas_migrated_gtm');
      $container->set('label', 'Migrated GTM');
      $container->set('container_id', $row->getSourceProperty('utexas_google_tag_manager_gtm_code'));
      $excluded_paths = $container->get('path_list');
      $migrated_paths = $row->getSourceProperty('utexas_google_tag_manager_gtm_exclude_paths');
      // Convert default and incoming paths to arrays.
      $migrated_paths = explode("\n", $migrated_paths);
      $excluded_paths = explode("\n", $excluded_paths);
      // Loop through incoming paths.
      foreach ($migrated_paths as $key => &$path) {
        // Preppend slash for D8 compliance.
        $path = "/" . $path;
        // If path not in default array, add it.
        if (!in_array($path, $excluded_paths)) {
          array_push($excluded_paths, $path);
        }
      }
      // Convert parsed paths back to string.
      $excluded_paths = implode("\n", $excluded_paths);
      // Append paths to list.
      $container->set('path_list', $excluded_paths);
      // Save container.
      $container->save();
    }

    // AddToAny settings.
    $social_sharing = $row->getSourceProperty('social_sharing');
    if ($social_sharing) {
      $social_sharing_config = \Drupal::service('config.factory')->getEditable('addtoany.settings');
      $social_sharing_config->set('additional_html', $social_sharing);
      $social_sharing_config->save();
    }

    // Theme settings.
    // See https://github.austin.utexas.edu/eis1-wcs/utexas_migrate/wiki/Sample-Dataset-from-Source-site#theme-settings
    $search_display = $row->getSourceProperty('utexas_searchbar_theme_settings');
    if ($search_display === 'no') {
      if ($search_block = Block::load('search_form')) {
        $search_block->disable();
        $search_block->save();
      }
    }
    // Footer social media icons.
    $display_footer_icons = $row->getSourceProperty('display_social_icons');
    if ($display_footer_icons == 1) {
      self::placeSocialMediaBlock('footer_left');
    }

    // Newsletter.
    $display_newsletter = $row->getSourceProperty('newsletter_exists');
    if ($display_newsletter == 1) {
      $newsletter_url = $row->getSourceProperty('newsletter_url');
      $newsletter_block = BlockContent::create([
        'type' => 'call_to_action',
        'info' => 'Site Newsletter',
        'field_utexas_call_to_action_link' => [
          'uri' => $newsletter_url,
          'title' => 'Subscribe to our newsletter',
        ],
      ]);
      $newsletter_block->save();
      $blockEntityManager = \Drupal::entityTypeManager()->getStorage('block');
      $theme = \Drupal::config('system.theme')->get('default');
      $block = $blockEntityManager->create([
        'id' => 'sitewide_newsletter',
        'plugin' => 'block_content:' . $newsletter_block->uuid(),
        'theme' => $theme,
      ]);
      $block->setRegion('footer_right');
      $block->setWeight(-100);
      $block->enable();
      $block->save();
    }

    // Set footer menu to depth of 1 to match expectations.
    if ($footer = Block::load('footer')) {
      $settings = $footer->get('settings');
      // Explicitly set footer menu depth to 1.
      $settings['depth'] = 1;
      $footer->set('settings', $settings);
      $footer->save();
    }

    switch ($row->getSourceProperty('secondary_menu')) {
      case 'social_accounts':
        self::placeSocialMediaBlock('header_secondary');
        if ($header = Block::load('header')) {
          $header->disable();
          $header->save();
        }
        break;

      case 'header_menu':
        if ($header = Block::load('header')) {
          $settings = $header->get('settings');
          // Explicitly set header menu depth to 1.
          $settings['depth'] = 1;
          $header->set('settings', $settings);
          $header->save();
        }

        break;

      default:
        // Empty means no block in the header secondary.
        // Disable header menu, if it exists.
        if ($header = Block::load('header')) {
          $header->disable();
          $header->save();
        }
        break;
    }
    if (!$row->getSourceProperty('default_logo')) {
      if ($logo_uri = $row->getSourceProperty('logo_path')) {
        // Save logo to filesystem.
        $path = $this->getPathToFile($logo_uri);
        $this->saveManagedFile($path, $logo_uri);
        // Store logo in configuration.
        $config = \Drupal::configFactory()->getEditable('forty_acres.settings');
        $config->set('logo', [
          'use_default' => FALSE,
          'path' => $logo_uri,
        ]);
        $config->save();
      }
      // Optional theme libraries (migrate from Foundation to Bootstrap).
      $library_map = [
        'dropdown' => 'dropdown',
        'reveal' => 'modal',
        'tab' => 'tab',
        'accordion' => 'collapse',
        'alert' => 'alert',
        'tooltip' => 'tooltip',
      ];
      foreach (array_values($library_map) as $initial) {
        $bootstrap_libraries[$initial] = 0;
      }
      if ($foundation_settings = $row->getSourceProperty('foundation_files')) {
        $source_settings = array_values($foundation_settings);
        foreach (array_values($source_settings) as $value) {
          if (is_string($value) && in_array($value, array_keys($library_map))) {
            $bootstrap = $library_map[$value];
            $bootstrap_libraries[$bootstrap] = $bootstrap;
          }
        }
        // If utprof is enabled., ensure the Tab library is on, even
        // if it wasn't enabled in the source site.
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('utprof')) {
          $bootstrap_libraries['tab'] = 'tab';
        }
        $config = \Drupal::configFactory()->getEditable('forty_acres.settings');
        $config->set('bootstrap_components', $bootstrap_libraries);
        $config->save();
      }
    }

    // As an array of 1 item, this will indicate that the migration operation
    // completed its one task (composed of multiple settings).
    return ['site_settings'];
  }

  /**
   * Place an instance of the sitewide social media block in a region.
   *
   * @param string $region
   *   The theme region to place the block in.
   */
  private static function placeSocialMediaBlock($region) {
    $destination_db = Database::getConnection('default', 'default');
    $table = 'migrate_map_utexas_social_links_sitewide';
    if (!$destination_db->schema()->tableExists($table)) {
      return;
    }
    $destination_bid = $destination_db->select($table, 'n')
      ->fields('n', ['destid1'])
      ->execute()
      ->fetchField();
    if (!$destination_bid) {
      return;
    }
    $sitewide_block = BlockContent::load($destination_bid);
    if ($sitewide_block) {
      $blockEntityManager = \Drupal::entityTypeManager()->getStorage('block');
      $theme = \Drupal::config('system.theme')->get('default');
      $block = $blockEntityManager->create([
        'id' => 'sitewide_social_links_' . $region,
        'plugin' => 'block_content:' . $sitewide_block->uuid(),
        'theme' => $theme,
      ]);
      $block->setRegion($region);
      $block->setWeight(100);
      $block->enable();
      $block->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    try {
      // Config for flex_page defaults to 1 for breadcrumb visibility. Reset.
      $flex_page_breadcrumb_display = \Drupal::configFactory()->getEditable('breadcrumbs_visibility.content_type.utexas_flex_page');
      $flex_page_breadcrumb_display->set('display_breadcrumbs', 1);
      $flex_page_breadcrumb_display->save();
      // Delete GTM container.
      $container = \Drupal::configFactory()->getEditable('google_tag.container.utexas_migrated_gtm');
      $container->delete();
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Rollback of site_settings failed. :error - Code: :code", [
        ':error' => $e->getMessage(),
        ':code' => $e->getCode(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRollback() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackAction() {
    return MigrateIdMapInterface::ROLLBACK_DELETE;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // Not needed; must be implemented to respect MigrateDestinationInterface.
  }

}
