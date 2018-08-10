<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class PhotoContentArea {

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
    $source_data = Database::getConnection()->select('field_data_field_utexas_photo_content_area', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'headline' => $item->{'field_utexas_photo_content_area_headline'},
        'image_fid' => $item->{'field_utexas_photo_content_area_image_fid'},
        'copy' => $item->{'field_utexas_photo_content_area_copy_value'},
        'links' => $item->{'field_utexas_photo_content_area_links'},
        'credit' => $item->{'field_utexas_photo_content_area_credit'},
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
    // Technically, there should only ever be one delta for Photo Content.
    // See explanation in getPhotoContentAreaSource().
    foreach ($source as $delta => $instance) {
      // The 'type' value here is the Paragraph type machine name.
      $field_values = [
        'type' => 'utexas_photo_content_area',
        'field_utexas_pca_headline' => [
          'value' => $instance['headline'],
        ],
        'field_utexas_pca_copy' => [
          'value' => $instance['copy'],
          'format' => 'flex_html',
        ],
        'field_utexas_pca_credit' => [
          'value' => $instance['credit'],
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
        $field_values['field_utexas_pca_links'] = $prepared_links;
      }
      if ($instance['image_fid'] != 0) {
        $destination_fid = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
        $field_values['field_utexas_pca_image'] = [
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
    // @todo --> need to evaluate whether this field is empty.

    return $paragraphs;

  }

}
