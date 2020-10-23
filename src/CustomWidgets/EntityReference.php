<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;

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
    return $prepared;
  }

  /**
   * Find the destination Uuid from the source target_id.
   *
   * @param array $component_data.
   *   Information about the block.
   *
   * @return string
   *   Returns the destination Uuid.
   */
  public static function getDestinationUuid($component_data) {
    Database::setActiveConnection('default');
    $db = Database::getConnection();
    $map = [
      'twitter_widget' => 'migrate_map_twitter_widgets',
    ];
    $type = $component_data['block_type'];
    $mapping = $db->select($map[$type], 'm')
      ->fields('m')
      ->condition('sourceid1', $component_data['block_data'][0]['target_id'])
      ->execute()
      ->fetchAll();
    $destination = $db->select('block_content', 'b')
      ->fields('b')
      ->condition('id', $mapping[0]->destid1)
      ->execute()
      ->fetchAll();
    Database::setActiveConnection('utexas_migrate');
    // Since these reusable blocks were creating during a previous migration
    // task, there will only be 1 revision, so we can safely use the 0th.
    return $destination[0]->uuid;
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
