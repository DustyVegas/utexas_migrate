<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class HeroImage {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param string $type
   *   Whether this is landing page or standard page.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget.
   */
  public static function convert($type, $source_nid) {
    $source_data = self::getSourceData($type, $source_nid);
    $paragraph_data = self::save($source_data);
    return $paragraph_data;
  }

  /**
   * Query the source database for data.
   *
   * @param string $type
   *   Whether this is landing page or standard page.
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of Paragraph ID(s) of the widget
   */
  public static function getSourceData($type, $source_nid) {
    // Get all instances from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $source_data = Database::getConnection()->select('field_data_field_utexas_hero_photo', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'type' => $type,
        'image_fid' => $item->field_utexas_hero_photo_image_fid,
        'caption' => $item->field_utexas_hero_photo_caption,
        'display_style' => $item->field_utexas_hero_photo_hero_image_style,
        'position' => $item->field_utexas_hero_photo_hero_image_position,
        'photo_credit' => $item->field_utexas_hero_photo_credit,
        'subheading' => $item->field_utexas_hero_photo_subhead,
        'link_href' => $item->field_utexas_hero_photo_link_href,
        'link_title' => $item->field_utexas_hero_photo_link_text,
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
      $display_type_map = [
        'hero-style-1' => '2',
        'hero-style-2' => '3',
        'hero-style-3' => '4',
        'hero-style-4' => '5',
        'hero-style-5' => '6',
      ];
      // The 'type' value here is the Paragraph type machine name.
      $field_values = [
        'type' => 'utexas_hero_image',
        'field_utexas_hi_photo_credit' => [
          'value' => $instance['photo_credit'],
        ],
        'field_utexas_hi_display_style' => [
          'value' => isset($display_type_map[$instance['display_style']]) ? $display_type_map[$instance['display_style']] : '1',
        ],
        'field_utexas_hi_subheading' => [
          'value' => $instance['subheading'],
        ],
        'field_utexas_hi_link' => [
          'uri' => MigrateHelper::prepareLink($instance['link_href']),
          'title' => $instance['link_title'],
        ],
      ];
      // Handle divergence between Hero Photo Full & Hero Photo Standard.
      if ($instance['type'] == 'standard_page') {
        $field_values['field_utexas_hi_caption'] = [
          'value' => $instance['caption'],
        ];
      }
      if ($instance['type'] == 'landing_page') {
        // Caption is re-used as heading in full hero photo widget(!).
        $field_values['field_utexas_hi_heading'] = [
          'value' => $instance['caption'],
        ];
      }
      if ($instance['image_fid'] != 0) {
        $destination_fid = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
        $field_values['field_utexas_hi_image'] = [
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
