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
  public static function getMediaIdFromFid($fid) {
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
    // Handle internal URLs.
    if (strpos($link, 'node/') === 0) {
      $source_nid = substr($link, 5);
      // Each node type migration must be queried individually,
      // since they have no relational shared field for joining.
      $tables_to_query = [
        'migrate_map_utexas_landing_page',
        'migrate_map_utexas_standard_page',
      ];
      foreach ($tables_to_query as $table) {
        $destination_nid = \Drupal::database()->select('migrate_map_utexas_landing_page', 'n')
          ->fields('n', ['destid1'])
          ->condition('n.sourceid1', $source_nid)
          ->execute()
          ->fetchField();
        if ($destination_nid) {
          return('internal:/node/' . $destination_nid);
        }
      }
      // The destination NID doesn't exist. Print a warning message.
      \Drupal::logger('utexas_migrate')->warning('* Source node %source contained link "@link". No equivalent destination node was found. Link replaced with link to homepage.', [
        '@link' => $link,
        '%source' => $source_nid,
      ]);
      return 'internal:/';
    }

    // Handle <front>.
    if ($link == '<front>') {
      return 'internal:/';
    }
    return $link;
  }

}
