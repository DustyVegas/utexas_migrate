<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;

/**
 * Convert v2 "UT Newsreel" data to v3 Feed Block.
 */
class Newsreel {

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
    $types = unserialize($data['block_data'][0]['type']);
    $categories = unserialize($data['block_data'][0]['category']);
    $tags = $data['block_data'][0]['tag'];
    $block_definition = [
      'type' => 'feed_block',
      'info' => $data['block_data'][0]['headline'],
      'field_rss_feed' => [
        'feed_uri' => self::getFeedUrl($types, $categories, $tags),
        'count' => $data['block_data'][0]['count'],
        'display_date' => 1,
        'date_format' => 'custom',
        'custom_date_format' => 'F j, Y',
        'display_description' => $data['block_data'][0]['display_description'],
        'description_length' => 0,
        'description_plaintext' => 1,
      ],
      'reusable' => FALSE,
    ];
    if ($data['block_data'][0]['cta_title']) {
      $block_definition['field_read_more'] = [
        'uri' => 'https://news.utexas.edu',
        'title' => $data['block_data'][0]['cta_title'],
      ];
    }
    return $block_definition;
  }

  /**
   * Convert D7 data to D8 structure.
   *
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of field data for the widget.
   */
  public static function getFromNid($source_nid) {
    $source_data = self::getRawSourceData($source_nid);
    $field_data = self::massageFieldData($source_data);
    return $field_data;
  }

  /**
   * Query the source database for data.
   *
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of IDs of the widget
   */
  public static function getRawSourceData($source_nid) {
    $instance = 'newsreel';
    // Get all instances from the legacy DB.
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $source_data = $source_db->select('field_data_field_utexas_' . $instance, 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $i) {
      $prepared[$delta] = [
        'headline' => $i->{'field_utexas_' . $instance . '_headline'},
        'type' => $i->{'field_utexas_' . $instance . '_type'},
        'category' => $i->{'field_utexas_' . $instance . '_category'},
        'tag' => $i->{'field_utexas_' . $instance . '_tag'},
        'count' => $i->{'field_utexas_' . $instance . '_count'},
        'include_description' => $i->{'field_utexas_' . $instance . '_include_description'},
        'view_all' => $i->{'field_utexas_' . $instance . '_view_all'},
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
    $destination = [];
    foreach ($source as $delta => $instance) {
      $destination[$delta]['count'] = $instance['count'];
      $destination[$delta]['display_description'] = $instance['include_description'];
      $destination[$delta]['cta_title'] = $instance['view_all'];
      $destination[$delta]['type'] = $instance['type'];
      $destination[$delta]['category'] = $instance['category'];
      $destination[$delta]['tag'] = $instance['tag'];
    }
    $destination['title'] = $source[0]['headline'];
    $destination['label'] = 'visible';
    return $destination;
  }

  /**
   * Custom function to construct the RSS feed URL for news.utexas.edu.
   *
   * @return string
   *   URL: "https://news.utexas.edu/rss/[types]/[categories]/[tags]/feed.xml".
   */
  protected static function getFeedUrl($types, $categories, $tags) {
    $concatenated = self::getUrlStrings($types, $categories, $tags);
    $feed_base = 'https://news.utexas.edu';
    $feed_url = $feed_base . '/rss/' . $concatenated['types'] . '/' . $concatenated['categories'] . '/' . $concatenated['tags'] . '/feed.xml';
    return $feed_url;
  }

  /**
   * Custom function to concatenate array items into strings.
   *
   * @param str[] $types
   *   An array of News types: features, press-releases, and opinions.
   * @param str[] $categories
   *   An array of machine-readable names corresponding to the News taxonomy.
   * @param string $tags
   *   A comma-separated list of user-supplied tags, or empty string.
   *
   * @return str[]
   *   An array of 'types', 'categories', and 'tags' as concatenated strings.
   */
  protected static function getUrlStrings(array $types, array $categories, $tags) {
    $concatenated = [];
    $all_types = TRUE;
    $valid_types = [];
    $modified_types = [
      'news' => 'features',
      'press-releases' => 'press-releases',
      'texas-perspectives' => 'opinions',
    ];
    // Names of the News types were changed.
    // See https://utexas-digx.atlassian.net/browse/UTCMSX-842
    // To accommodate sites that already have set data, this rewrites those
    // machine names here, at the preprocess level to match UT News.
    foreach (array_values($types) as $value) {
      if ($value != '0') {
        // Rename the existing machine names to match updated UT News site.
        $valid_types[] = $modified_types[$value];
      }
      else {
        $all_types = FALSE;
      }
    }
    $concatenated['types'] = (!$all_types) ? implode(',', $valid_types) : 'all';

    // Prepare concatenated list of primary categories.
    $all_categories = TRUE;
    $valid_categories = [];

    foreach (array_values($categories) as $value) {
      if ($value != '0') {
        $valid_categories[] = $value;
      }
      else {
        $all_categories = FALSE;
      }
    }
    $concatenated['categories'] = (!$all_categories) ? implode(',', $valid_categories) : 'all';

    // Prepare concatenated list of tags.
    $tag_list = $tags != '' ? $tags : 'all';
    // Remove potential user-added space in comma delimited list.
    $tag_list = str_replace(', ', ',', $tag_list);
    // Convert tags to lower-case hyphenated.
    $concatenated['tags'] = strtolower(str_replace(' ', '-', $tag_list));
    return $concatenated;
  }

}
