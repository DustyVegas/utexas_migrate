<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class FeaturedHighlight {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget.
   */
  public static function convert($source_nid) {
    $source_data = self::getSourceData($source_nid);
    $paragraph_data = self::save($source_data);
    return $paragraph_data;
  }

  /**
   * Query the source database for data.
   *
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget
   */
  public static function getSourceData($source_nid) {
    // Get all instances from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $source_data = Database::getConnection()->select('field_data_field_utexas_featured_highlight', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'image_fid' => $item->field_utexas_featured_highlight_image_fid,
        'date' => $item->field_utexas_featured_highlight_date,
        'headline' => $item->field_utexas_featured_highlight_headline,
        'copy' => $item->field_utexas_featured_highlight_copy_value,
        'link_href' => $item->field_utexas_featured_highlight_link,
        'link_title' => $item->field_utexas_featured_highlight_cta,
        'style' => $item->field_utexas_featured_highlight_highlight_style,
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
    // Technically, there should only ever be one delta.
    foreach ($source as $delta => $instance) {
      // The 'type' value here is the Paragraph type machine name.
      $field_values = [
        'type' => 'utexas_featured_highlight',
        'field_utexas_fh_copy' => [
          'value' => $instance['copy'],
          'format' => 'flex_html',
        ],
        'field_utexas_fh_headline' => [
          'value' => $instance['headline'],
        ],
        'field_utexas_fh_cta' => [
          'uri' => MigrateHelper::prepareLink($instance['link_href']),
          'title' => $instance['link_title'],
        ],
      ];
      if ($instance['date'] != 0) {
        $field_values['field_utexas_fh_date'] = [
          'value' => $instance['date'],
        ];
      }
      // @todo: determine how to migrate Featured Highlight highlight style.
      // @todo: support Video file entity migration.
      if ($instance['image_fid'] != 0) {
        if ($destination_fid = MigrateHelper::getMediaIdFromFid($instance['image_fid'])) {
          $field_values['field_utexas_fh_media'] = [
            'target_id' => $destination_fid,
            'alt' => '@to be replaced with media reference',
          ];
        }
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
