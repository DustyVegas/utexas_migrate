<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class ImageLink {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param string $instance
   *   Whether this is image_link_ 'a' or 'b'.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget.
   */
  public static function convert($instance, $source_nid) {
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
    $source_data = Database::getConnection()->select('field_data_field_utexas_image_link_' . $instance, 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $fca) {
      $prepared[$delta] = [
        'image_fid' => $fca->{'field_utexas_image_link_' . $instance . '_image_fid'},
        'uri' => $fca->{'field_utexas_image_link_' . $instance . '_link_href'},
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
    // Technically, there should only ever be one delta for Image Link.
    foreach ($source as $delta => $instance) {
      $instances[$delta]['link'] = MigrateHelper::prepareLink($instance['uri']);

      if ($instance['image_fid'] != 0) {
        $destination_mid = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
        $instances[$delta]['image'] = $destination_mid;
      }
    }
    return $instances;
  }

}
