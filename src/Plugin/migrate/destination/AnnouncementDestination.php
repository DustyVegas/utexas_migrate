<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block\Entity\Block;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\utexas_migrate\Plugin\migrate\source\AnnouncementSource;

/**
 * Provides the destination plugin for the sitewide announcement.
 *
 * @MigrateDestination(
 *   id = "utexas_announcement_destination"
 * )
 */
class AnnouncementDestination extends Entity implements MigrateDestinationInterface {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Fallback defaults.
    $config = [
      'scheme' => 'yellow_black',
      'icon' => 'beacon',
    ];

    foreach (AnnouncementSource::$settings as $setting) {
      $source[$setting] = $row->getSourceProperty($setting);
    }
    $config['label_display'] = "0";
    if ($source['utexas_announcement_active'] == 1) {
      $config['active'] = 'homepage';
    }
    else {
      $config['active'] = 'all';
    }
    if (isset($source['utexas_announcement_title'])) {
      $config['title'] = $source['utexas_announcement_title'];
    }
    if (isset($source['utexas_announcement_body'])) {
      $config['message'] = [
        'value' => $source['utexas_announcement_body'],
        'format' => 'restricted_html',
      ];
    }
    if (isset($source['utexas_announcement_destination_url'])) {
      $config['cta'] = [
        'uri' => MigrationHelper::prepareLink($source['utexas_announcement_destination_url']),
        'title' => $source['utexas_announcement_cta'],
      ];
    }
    if (isset($source['utexas_announcement_title_icon']) && false) {
      $icon_map = [
        'icon-announcement' => 'bullhorn',
        'icon-warning' => 'warning',
        'icon-beacon' => 'beacon',
      ];
      $config['icon'] = $icon_map[$source['utexas_announcement_title_icon']];
    }
    if (isset($source['utexas_announcement_background'])) {
      $background_map = [
        'yellow-background' => 'yellow_black',
        'orange-background' => 'orange_black',
        'green-background' => 'green_white',
        'gray-background' => 'grey_white',
      ];
      $config['scheme'] = $background_map[$source['utexas_announcement_background']];
    }
    $theme = \Drupal::config('system.theme')->get('default');
    try {
      if ($block = Block::load('siteannouncement')) {
        $block->set('settings', $config);
      }
      else {
        // Instantiate the block for an edge case where it has been deleted.
        $blockEntityManager = \Drupal::entityTypeManager()->getStorage('block');
        $block = $blockEntityManager->create([
          'id' => 'siteannouncement',
          'settings' => $config,
          'plugin' => 'utexas_announcement',
          'theme' => $theme,
        ]);
        $block->setRegion('site_announcement');
      }
      $block->enable();
      $block->set('theme', $theme);
      $visibility = $block->getVisibility();
      // Set the block visibility per "all" or "homepage".
      if ($config['state'] === "homepage") {
        $visibility['request_path']['pages'] = "<front>";
      }
      else {
        // Default to all pages.
        $visibility['request_path'] = [];
      }
      $block->setVisibilityConfig("request_path", $visibility['request_path']);
      $block->save();
      return [$block->id()];
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Import of announcement failed: :error - Code: :code", [
        ':error' => $e->getMessage(),
        ':code' => $e->getCode(),
      ]);
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
   * Finds the entity type from configuration or plugin ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return string
   *   The entity type.
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'block';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    try {
      $block = BlockContent::load($destination_identifier['id']);
      if ($block != NULL) {
        $block->delete();
      }
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Rollback of block with id of :nid failed: :error - Code: :code", [
        ':nid' => $destination_identifier['id'],
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

}
