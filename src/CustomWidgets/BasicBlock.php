<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\utexas_migrate\MigrateHelper;
use Drupal\utexas_migrate\WysiwygHelper;

/**
 * Convert D7 custom compound field to D8 Inline blocks.
 */
class BasicBlock {

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
      'body' => [
        'value' => WysiwygHelper::process($data['block_data'][0]['value']),
        'format' => $data['block_data'][0]['format'],
      ],
      'reusable' => FALSE,
    ];
    if (empty($block_definition['body']['value'])) {
      return FALSE;
    }
    return $block_definition;
  }

  /**
   * Convert D7 data to D8 structure.
   *
   * @param string $field_name.
   *  A string of the format `block-<source_bid>`
   *
   * @return array
   *   Returns destination settings for basic block.
   */
  public static function getFromBid($field_name) {
    $prepared = [];
    $block_id = str_replace('block-', '', $field_name);
    $prepared[0] = ['target_id' => $block_id];
    $prepared['field_name'] = self::getBlockTitle($block_id);
    if (!empty($prepared['field_name'])) {
      $prepared['display_title'] = 'visible';
    }
    return $prepared;
  }

  /**
   * Convert D7 data to D8 structure.
   *
   * @param string $instance
   *   Whether this is image_link_ 'a' or 'b'.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of field data for the widget.
   */
  public static function getFromNid($instance, $source_nid) {
    $source_data = self::getSourceData($instance, $source_nid);
    $field_data = self::massageFieldData($source_data);
    return $field_data;
  }

  /**
   * Query the source database for data.
   *
   * @param string $instance
   *   Whether this is image_link_ 'a' or 'b'.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget
   */
  public static function getSourceData($instance, $source_nid) {
    // Get all instances from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $source_data = Database::getConnection()->select('field_data_field_' . $instance, 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $field) {
      $prepared[$delta] = [
        'value' => $field->{'field_' . $instance . '_value'},
        'format' => $field->{'field_' . $instance . '_format'},
      ];
    }
    return $prepared;
  }

  /**
   * Rearrange data as necessary for destination import.
   *
   * @param array $source
   *   A simple key-value array of subfield & value.
   *
   * @return array
   *   A simple key-value array returned the metadata about the field.
   */
  protected static function massageFieldData(array $source) {
    foreach ($source as $delta => $instance) {
      $source[$delta]['format'] = MigrateHelper::prepareTextFormat($instance['format']);
    }
    return $source;
  }

  /**
   * Retrieve the block title from the source & process according
   * to MenuBlock logic.
   *
   * @return string
   *   The title string.
   */
  public static function getBlockTitle($id) {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $query = $source_db->select('block', 'b')
      ->fields('b', ['title'])
      ->condition('delta', $id, '=')
      ->condition('module', 'block', '=')
      ->execute()
      ->fetch();
    if (empty($query->title)) {
      return NULL;
    }
    if ($query->title === '<none>') {
      return '';
    }
    return $query->title;
  }

  /**
   * Retrieve the block info from the source & process.
   *
   * @return string
   *   The title string.
   */
  public static function getBlockInfo($id) {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $query = $source_db->select('block_custom', 'b')
      ->fields('b', ['info'])
      ->condition('bid', $id, '=')
      ->execute()
      ->fetch();
    if (empty($query->info)) {
      return NULL;
    }
    return $query->info;
  }

  /**
   * Retrieve the block.
   *
   * @return array
   *   The layout associated with the block.
   */
  public static function getBlockLayout($id) {
    $theme = MigrateHelper::getVariable('theme_default');
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $query = $source_db->select('block', 'b')
      ->fields('b')
      ->condition('delta', $id, '=')
      ->condition('module', 'block', '=')
      ->condition('theme', $theme, '=')
      ->execute()
      ->fetch();
    return (array) $query;
  }

  /**
   * Retrieve the block roles for layout.
   *
   * @return array
   *   The roles associated with the block.
   */
  public static function getBlockRoles($id) {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $query = $source_db->select('block_role', 'b')
      ->fields('b')
      ->condition('delta', $id, '=')
      ->condition('module', 'block', '=')
      ->execute()
      ->fetchAll();
    return (array) $query;
  }

  /**
   * Retrieve the block.
   *
   * @return array
   *   The new block visibility.
   */
  public static function getVisibility($old_visibility, $pages, $roles) {
    $visibility = [];
    // If the block is assigned to specific roles, add the user_role condition.
    if ($roles) {
      $visibility['user_role'] = [
        'id' => 'user_role',
        'roles' => [],
        'context_mapping' => [
          'user' => '@user.current_user_context:current_user',
        ],
        'negate' => FALSE,
      ];
      foreach ($roles as $r) {
        $destination_roles[] = MigrateHelper::getMappedRole($r->rid);
      }
      $visibility['user_role']['roles'] = array_unique($destination_roles);
    }
    if ($pages) {
      // 2 == BLOCK_VISIBILITY_PHP in Drupal 6 and 7.
      if ($old_visibility == 2) {
        // If the PHP module is present, migrate the visibility code unaltered.
        return $visibility;
      }
      else {
        $paths = preg_split("(\r\n?|\n)", $pages);
        foreach ($paths as $key => $path) {
          if ($path !== '<front>') {
            $path = MigrateHelper::getDestinationFromSource($path);
            if (strpos($path, '/') !== 0) {
              $path = '/' . $path;
            }
          }
          $paths[$key] = $path;
        }
        $visibility['request_path'] = [
          'id' => 'request_path',
          'negate' => !$old_visibility,
          'pages' => implode("\n", $paths),
        ];
      }
    }
    return $visibility;
  }

}
