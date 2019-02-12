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
    $mid = 0;
    $mid = \Drupal::database()->select('migrate_map_utexas_media_image')
      ->fields('migrate_map_utexas_media_image', ['destid1'])
      ->condition('sourceid1', $fid, '=')
      ->execute()
      ->fetchField();
    // Try the video map.
    if (!$mid) {
      $mid = \Drupal::database()->select('migrate_map_utexas_media_video')
        ->fields('migrate_map_utexas_media_video', ['destid1'])
        ->condition('sourceid1', $fid, '=')
        ->execute()
        ->fetchField();
    }
    return $mid;
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
      'migrate_map_utexas_basic_page',
      'migrate_map_utexas_article',
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
   * Given an source text format, return an available format.
   *
   * @param string $text_format
   *   The source format (e.g., 'filtered_html')
   *
   * @return string
   *   The destination format (e.g., 'flex_html')
   */
  public function getDestinationTextFormat($text_format) {
    // As much as possible, we want to map the set text formats to their
    // respective D8 equivalents. If a D8 equivalent doesn't exist, fall back
    // to 'flex_html'.
    $destination_text_formats = [
      'flex_html',
      'basic_html',
      'full_html',
      'restricted_html',
      'plain_text',
    ];
    if (in_array($text_format, $destination_text_formats)) {
      return $text_format;
    }
    return 'flex_html';
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
