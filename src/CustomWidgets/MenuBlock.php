<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 Menu block to D8 block.
 */
class MenuBlock {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param $menu_block_id
   *   A menu block ID, such as menu_block-2
   *
   * @return array
   *   Returns an array of field data for the widget.
   */
  public static function getBlockData($menu_block_id) {
    $source_data = self::getSourceData($menu_block_id);
    return $source_data;
  }

  /**
   * Query the source database for data.
   *
   * @param string $views_block_id
   *   The Views block ID as defined in the source site.
   *
   * @return array
   *   Returns contextual data about the Views block being used.
   */
  public static function getSourceData($menu_block_id) {
    $data = [];
    // Start by assuming that the title display has been suppressed.
    $data['display_title'] = '';
    $variables = [
      'parent',
      'title_link',
      'admin_title',
      'level',
      'follow',
      'depth',
      'depth_relative',
      'expanded',
      'sort',
    ];
    $variable_id = str_replace('-', '_', $menu_block_id);
    $block_id = str_replace('menu_block_', '', $variable_id);
    foreach ($variables as $v) {
      $data[$v] = self::getVariable($variable_id . '_' . $v);
    }
    $parent_parts = explode(':', $data['parent']);
    if ($parent_parts[1] == 0) {
      $data['parent'] = 0;
    }
    else {
      $data['parent'] = self::getMenuLink($parent_parts[0], $parent_parts[1]);
    }
    // In v3, the `follow` value is 1 or 0,
    // and `follow_parent` is 'active' or 'child'.
    $data['follow_parent'] = $data['follow'];
    $data['follow'] = $data['follow'] === 0 ? 0 : 1;
    $data['admin_title'] = self::getMenuBlockTitle($block_id);
    if (!empty($data['admin_title'])) {
      $data['display_title'] = 'visible';
    }
    if ($data['title_link']) {
      $data['display_title'] = 'visible';
      $data['label_link'] = TRUE;
      // Drupal 8/9 provides more options for title-as-link. We map the single option from D7.
      $data['label_type'] = 'fixed';
    }
    $data['menu_block_id'] = 'menu_block:' . MigrateHelper::getMappedMenuName($parent_parts[0]);
    return $data;
  }

  /**
   * Helper function for DB queries.
   *
   * @return string
   *   The unserialized value.
   */
  public static function getVariable($name) {
    Database::setActiveConnection('utexas_migrate');
    $query = Database::getConnection()->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('name', $name, '=')
      ->execute()
      ->fetch();
    return unserialize($query->value);
  }

  /**
   * Retrieve the block title from the source & process according
   * to MenuBlock logic.
   *
   * @return string
   *   The title string.
   */
  public static function getMenuBlockTitle($id) {
    Database::setActiveConnection('utexas_migrate');
    $query = Database::getConnection()->select('block', 'b')
      ->fields('b', ['title'])
      ->condition('delta', $id, '=')
      ->condition('module', 'menu_block', '=')
      ->execute()
      ->fetch();
    Database::setActiveConnection('default');
    if (empty($query->title)) {
      return NULL;
    }
    if ($query->title === '<none>') {
      return '';
    }
    return $query->title;
  }

  /**
   * Given a source menu name & menu link ID, return the destination.
   *
   * @param string $menu
   *   The source menu machine name (e.g., "menu-main")
   * @param int $id
   *   The source menu link id (e.g., "341")
   *
   * @return string
   *   A D8 menu link reference (e.g., main:menu_link_content:f0abbbdb-07b5).
   */
  public static function getMenuLink($menu, $id) {
    Database::setActiveConnection('default');
    $db = Database::getConnection();
    $mapping = $db->select('migrate_map_utexas_menu_links', 'm')
      ->fields('m', ['destid1'])
      ->condition('sourceid1', $id)
      ->execute()
      ->fetch();
    if (!$mapping->destid1) {
      return 0;
    }
    $query = Database::getConnection()->select('menu_link_content', 'm')
      ->fields('m', ['uuid'])
      ->condition('m.id', $mapping->destid1, '=')
      ->execute()
      ->fetch();
    if ($query->uuid) {
      $parts = [
        MigrateHelper::getMappedMenuName($menu),
        'menu_link_content',
        $query->uuid,
      ];
      return implode(':', $parts);
    }
    return '0';
  }

  /**
   * Prepare an array for saving a block.
   *
   * @param array $data
   *   The D7 fields.
   *
   * @return array
   *   D8 block format.
   */
  public static function createBlockDefinition(array $data) {
    $block_definition = [
      'type' => $data['block_type'],
      'info' => $data['field_identifier'],
      'reusable' => FALSE,
    ];
    return $block_definition;
  }

}
