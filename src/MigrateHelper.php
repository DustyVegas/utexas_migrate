<?php

namespace Drupal\utexas_migrate;

use Drupal\Core\Database\Database;

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
   * Given a source nid, return a destination nid if there is one.
   *
   * @param int $source_nid
   *   The NID from the D7 site.
   *
   * @return int
   *   Returns the matching media entity ID imported to the D8 site.
   */
  public static function getDestinationNid($source_nid) {
    // Each node type migration must be queried individually,
    // since they have no relational shared field for joining.
    $tables_to_query = [
      'migrate_map_utexas_landing_page',
      'migrate_map_utexas_standard_page',
    ];
    foreach ($tables_to_query as $table) {
      $destination_nid = \Drupal::database()->select($table, 'n')
        ->fields('n', ['destid1'])
        ->condition('n.sourceid1', $source_nid)
        ->execute()
        ->fetchField();
      if ($destination_nid) {
        return $destination_nid;
      }
    }
    return FALSE;
  }

  /**
   * Given an internal source path, return its alias, if it exists.
   *
   * @param string $internal_source
   *   The canonical route (e.g., "node/1")
   *
   * @return string
   *   An path alias (e.g., "welcome-your-new-site").
   */
  public static function getSourceAlias($internal_source) {
    // Get alias from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $alias = Database::getConnection()->select('url_alias', 'ua')
      ->fields('ua', ['alias'])
      ->condition('source', $internal_source)
      ->execute()
      ->fetchField();
    return $alias;
  }

  /**
   * Receive a Drupal 7 link & format it for Drupal 8.
   *
   * @param string $link
   *   A link, in string format.
   * @param string $source_path
   *   The source path that referenced this link.
   *
   * @return string
   *   The appropriate link for D8.
   */
  public static function prepareLink($link, $source_path = '') {
    // Check for node/ links.
    // @todo: check for taxonomy/term/, file/, and other internal links (e.g., Views routes)
    if (strpos($link, 'node/') === 0) {
      $source_nid = substr($link, 5);
      if ($destination_nid = self::getDestinationNid($source_nid)) {
        return('internal:/node/' . $destination_nid);
      }
      // The destination NID doesn't exist. Print a warning message.
      \Drupal::logger('utexas_migrate')->warning('* Source node %source contained link "@link". No equivalent destination node was found. Link replaced with link to homepage.', [
        '@link' => $link,
        '%source' => $source_path,
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
