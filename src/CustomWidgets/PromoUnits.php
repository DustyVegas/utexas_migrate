<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class PromoUnits {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of custom compound field data.
   */
  public static function convert($source_nid) {
    $source_data = self::getSourceData($source_nid);
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
   *   Returns an array of Paragraph ID(s) of the widget
   */
  public static function getSourceData($source_nid) {
    // Get all instances from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $source_data = Database::getConnection()->select('field_data_field_utexas_promo_units', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'title' => $item->{'field_utexas_promo_units_title'},
        'headline' => $item->{'field_utexas_promo_units_headline'},
        'image_fid' => $item->{'field_utexas_promo_units_image_fid'},
        'copy' => $item->{'field_utexas_promo_units_copy_value'},
        'cta_title' => $item->{'field_utexas_promo_units_cta'},
        'cta_uri' => $item->{'field_utexas_promo_units_link'},
        'size_option' => $item->{'field_utexas_promo_units_size_option'},
      ];
    }
    return $prepared;
  }

  /**
   * Rearrange schema as needed from D7 to D8.
   *
   * @param array $source
   *   A simple key-value array of subfield & value.
   *
   * @return array
   *   A simple key-value array of D8 field data.
   */
  protected static function massageFieldData(array $source) {
    $destination = [];
    if (isset($source[0]['title'])) {
      $destination['headline'] = $source[0]['title'];
    }
    $items = [];
    foreach ($source as $delta => $instance) {
      if (isset($instance['headline'])) {
        $items[$delta]['item']['headline'] = $instance['headline'];
      }
      $items[$delta]['item']['image'][0] = $instance['image_fid'] != 0 ? MigrateHelper::getMediaIdFromFid($instance['image_fid']) : 0;
      if (isset($instance['copy'])) {
        $items[$delta]['item']['copy']['value'] = $instance['copy'];
        $items[$delta]['item']['copy']['format'] = 'flex_html';
      }
      if (isset($instance['link'])) {
        $items[$delta]['item']['link']['url'] = MigrateHelper::prepareLink($instance['cta_uri']);
        $items[$delta]['item']['link']['title'] = $instance['cta_title'];
      }
    }
    if (!empty($items)) {
      $destination['promo_unit_items'] = serialize($items);
    }
    return $destination;
  }

}
