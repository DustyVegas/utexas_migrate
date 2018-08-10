<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class FlexContentArea {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param string $instance
   *   Whether this is flex_content_area_ 'a' or 'b'.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget.
   */
  public static function convert($instance, $source_nid) {
    $source_data = self::getSourceData($instance, $source_nid);
    $paragraph_data = self::save($source_data);
    return $paragraph_data;
  }

  /**
   * Query the source database for data.
   *
   * @param string $instance
   *   Whether this is flex_content_area_ 'a' or 'b'.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget
   */
  public static function getSourceData($instance, $source_nid) {
    // Get all instances from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $source_data = Database::getConnection()->select('field_data_field_utexas_flex_content_area_' . $instance, 'fc')
      ->fields('fc')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'headline' => $item->{'field_utexas_flex_content_area_' . $instance . '_headline'},
        'image_fid' => $item->{'field_utexas_flex_content_area_' . $instance . '_image_fid'},
        'copy' => $item->{'field_utexas_flex_content_area_' . $instance . '_copy_value'},
        'links' => $item->{'field_utexas_flex_content_area_' . $instance . '_links'},
        'cta_title' => $item->{'field_utexas_flex_content_area_' . $instance . '_cta_title'},
        'cta_uri' => $item->{'field_utexas_flex_content_area_' . $instance . '_cta_uri'},
      ];
    }
    return $prepared;
  }

  /**
   * Save data as paragraph(s) & return the paragraph ID(s)
   *
   * @param array $source
   *   A simple key-value array of subfield & value.
   *
   * @return array
   *   A simple key-value array returned the metadata about the paragraph.
   */
  protected static function save(array $source) {
    $paragraphs = [];
    foreach ($source as $delta => $instance) {
      $field_values = [
        'type' => 'utexas_flex_content_area',
        'field_utexas_fca_headline' => [
          'value' => $instance['headline'],
        ],
        'field_utexas_fca_copy' => [
          'value' => $instance['copy'],
          'format' => 'flex_html',
        ],
        'field_utexas_fca_cta' => [
          'uri' => $instance['cta_uri'],
          'title' => $instance['cta_title'],
        ],
      ];
      $links = unserialize($instance['links']);
      if (!empty($links)) {
        foreach ($links as $delta => $link) {
          $prepared_links[] = [
            'uri' => MigrateHelper::prepareLink($link['link_url']),
            'title' => $link['link_title'],
            'delta' => $delta,
          ];
        }
        $field_values['field_utexas_fca_links'] = $prepared_links;
      }
      if ($instance['image_fid'] != 0) {
        $destination_fid = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
        $field_values['field_utexas_fca_image'] = [
          'target_id' => $destination_fid,
          'alt' => '@to be replaced with media reference',
        ];
      }
      $paragraph_instance = Paragraph::create($field_values);
      $paragraph_instance->save();
      $paragraphs[] = [
        'target_id' => $paragraph_instance->id(),
        'target_revision_id' => $paragraph_instance->id(),
        'delta' => $delta,
      ];
    }
    return $paragraphs;
  }

}
