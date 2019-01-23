<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
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
   *   Returns an array of compound field data for the widget.
   */
  public static function convert($type, $source_nid) {
    $source_data = self::getSourceData($type, $source_nid);
    return self::massageFieldData($source_data);
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
   * Return field data as an array.
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
      if (!empty($instance['photo_credit'])) {
        $destination[$delta]['credit'] = $instance['photo_credit'];
      }
      if (!empty($instance['subheading'])) {
        $destination[$delta]['subheading'] = $instance['subheading'];
      }
      if (!empty($instance['link_href'])) {
        $destination[$delta]['link_uri'] = MigrateHelper::prepareLink($instance['link_href']);
        $destination[$delta]['link_title'] = $instance['link_title'];
      }
      // Handle divergence between Hero Photo Full & Hero Photo Standard.
      if (!empty($instance['caption'])) {
        switch ($instance['type']) {
          case 'standard_page':
            $destination[$delta]['caption'] = $instance['caption'];
            break;

          case 'landing_page':
            // Caption is used as heading in "full" hero widget(Bg5 b5?!).
            $destination[$delta]['heading'] = $instance['caption'];
            break;
        }
      }
      if ($instance['image_fid'] != 0) {
        $destination[$delta]['media'] = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
      }
    }
    return $destination;
  }

}
