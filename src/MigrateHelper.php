<?php

namespace Drupal\utexas_migrate;

/**
 * Helper functions for migration.
 */
class MigrateHelper {

  /**
   * Retrieve a media entity ID for an equivalent D7 file from migration map.
   *
   * @param int $fid
   *   The file ID from the D7 site.
   *
   * @return int
   *   Returns the matching media entity ID imported to the D8 site.
   */
  public static function getMediaIdFromFid(int $fid) {
    return \Drupal::database()->select('migrate_map_utexas_media_image')
      ->fields('migrate_map_utexas_media_image', ['destid1'])
      ->condition('sourceid1', $fid, '=')
      ->execute()
      ->fetchField();
  }

  /**
   * Receive a Drupal 7 link & format it for Drupal 8.
   *
   * @param string $link
   *   A link, in string format.
   *
   * @return string
   *   The appropriate link for D8.
   */
  public static function prepareLink($link) {
    // @todo: Add logic for handling internal URLs.

    // Handle <front>.
    if ($link == '<front>') {
      return 'internal:/';
    }
    return $link;
  }

}
