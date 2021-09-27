<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\block_content\Entity\BlockContent;
/**
 * Get information about a D7 field that is an entity reference.
 */
class EntityReference {

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
   *   Returns an array of target ID(s) of the widget.
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
        'target_id' => $field->{'field_' . $instance . '_target_id'},
      ];
    }
    if ($instance === 'utexas_contact_info') {
      $prepared['display_title'] = 'visible';
    }
    return $prepared;
  }

  /**
   * Find the destination id from the source target_id.
   *
   * @param array $component_data
   *   Information about the source data being migrated.
   *
   * @return string
   *   Returns the destination ID.
   */
  public static function getDestinationBlockId(array $component_data) {
    $destination_db = Database::getConnection('default', 'default');
    $map = [
      'twitter_widget' => 'migrate_map_twitter_widgets',
      'contact_info' => 'migrate_map_contact_info',
      'basic_reusable' => 'migrate_map_utexas_content_blocks',
    ];
    if (!in_array($component_data['block_type'], array_keys($map))) {
      print_r('Could not find a mapping from "getDestinationBlockId"');
      return FALSE;
    }
    $type = $component_data['block_type'];
    $mapping = $destination_db->select($map[$type], 'm')
      ->fields('m')
      ->condition('sourceid1', $component_data['block_data'][0]['target_id'])
      ->execute()
      ->fetchAll();
    if ($mapping[0]->destid1) {
      return $mapping[0]->destid1;
    }
    return FALSE;
  }

  /**
   * Find the destination id from the source target_id.
   *
   * @param string $source_id
   *   Information about the source data being migrated.
   *
   * @return string
   *   Returns the destination ID.
   */
  public static function getContactInfoName($source_id) {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $query = $source_db->select('utexas_contact_info', 'c')
      ->fields('c', ['name'])
      ->condition('id', $source_id, '=')
      ->execute()
      ->fetchCol('name');
    if (isset($query[0])) {
      return $query[0];
    }
    return NULL;
  }

  /**
   * Find the destination Uuid from the source target_id.
   *
   * @param string $bid
   *   ID of the block.
   *
   * @return string
   *   Returns the destination Uuid.
   */
  public static function getDestinationUuid($bid) {
    $destination_db = Database::getConnection('default', 'default');
    $destination = $destination_db->select('block_content', 'b')
      ->fields('b')
      ->condition('id', $bid)
      ->execute()
      ->fetchAll();
    if ($destination[0]->uuid) {
      // Since these reusable blocks were creating during a previous migration
      // task, there will only be 1 revision, so we can safely use the 0th.
      return $destination[0]->uuid;
    }
    return FALSE;
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
    // Nothing to do here...yet.
    return $source;
  }

}
