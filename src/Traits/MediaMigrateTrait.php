<?php

namespace Drupal\utexas_migrate\Traits;

/**
 * Trait for Media entity migration.
 */
trait MediaMigrateTrait {

  /**
   * Retrieve a media entity ID for an equivalent D7 file from migration map.
   *
   * @param int $fid
   *   The file ID from the D7 site.
   *
   * @return int
   *   Returns the matching media entity ID imported to the D8 site.
   */
  public function getMediaIdFromFid(int $fid) {
    return \Drupal::database()->select('migrate_map_utexas_media_image')
      ->fields('migrate_map_utexas_media_image', ['destid1'])
      ->condition('sourceid1', $fid, '=')[0];
  }

}
