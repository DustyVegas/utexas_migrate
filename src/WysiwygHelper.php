<?php

namespace Drupal\utexas_migrate;

use Drupal\Core\Database\Database;

/**
 * Helper functions for migrating elements within WYSIWYG fields.
 */
class WysiwygHelper {

  /**
   * Main method for processing all content.
   *
   * @param string $text
   *   The entire text of a WYSIWYG field.
   *
   * @return string
   *   The processed text
   */
  public static function process($text) {
    $text = self::transformMediaLibrary($text);
    return $text;
  }

  /**
   * Find v2 media markup & render it as v3 media tags.
   *
   * @param string $text
   *   The entire text of a WYSIWYG field.
   *
   * @return string
   *   The processed text
   */
  public static function transformMediaLibrary($text) {
    // Source: [[{"fid":"1","view_mode":"preview","fields":{"format":"preview","alignment":"","field_file_image_alt_text[und][0][value]":"placeholder image","field_file_image_title_text[und][0][value]":"placeholder image","external_url":""},"type":"media","field_deltas":{"1":{"format":"preview","alignment":"","field_file_image_alt_text[und][0][value]":"placeholder image","field_file_image_title_text[und][0][value]":"placeholder image","external_url":""}},"attributes":{"alt":"placeholder image","title":"placeholder image","class":"media-element file-preview","data-delta":"1"}}]]
    $destination_token = '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="UUID_TOKEN"></drupal-media>';
    $pattern = '/\[\[{(.*)"fid":"(\d*)",(.*)}\]\]/';
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
    if (isset($matches)) {
      foreach ($matches as $match) {
        $uuid = self::getMediaUuid($match[2]);
        if ($uuid) {
          $replace = str_replace('UUID_TOKEN', $uuid, $destination_token);
          $text = str_replace($match[0], $replace, $text);
        }
      }
    }
    return $text;
  }

  /**
   * Get a v3 media UUID from a source site FID.
   *
   * @param int $source_fid
   *   The FID of the source site media item.
   *
   * @return string
   *   The processed text
   */
  public static function getMediaUuid($source_fid) {
    $destination_mid = MigrateHelper::getDestinationMid($source_fid);
    if ($destination_mid) {
      $connection = Database::getConnection('default', 'default');
      $uuid = $connection->select('media')
        ->fields('media', ['uuid'])
        ->condition('mid', $destination_mid, '=')
        ->execute()
        ->fetchField();
      if ($uuid) {
        return $uuid;
      }
    }
    return FALSE;
  }

}
