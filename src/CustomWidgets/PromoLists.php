<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class PromoLists {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param int $source_nid
   *   The node ID from the source data.
   *
   * @return array
   *   Returns an array of field data for the widget.
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
    $source_promo_list_containers = Database::getConnection()->select('field_data_field_utexas_promo_list', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $output = [];
    foreach ($source_promo_list_containers as $container_delta => $container) {
      $source_promo_list_items = Database::getConnection()->select('field_data_field_utexas_promo_list_item', 'f')
        ->condition('entity_id', $container->field_utexas_promo_list_value)
        ->fields('f')
        ->execute()
        ->fetchAll();
      $output[$container_delta]['items'] = $source_promo_list_items;
      $source_promo_list_headline = Database::getConnection()->select('field_data_field_utexas_promo_list_headline', 'h')
        ->condition('entity_id', $container->field_utexas_promo_list_value)
        ->fields('h', ['field_utexas_promo_list_headline_value'])
        ->execute()
        ->fetchAll();
      $output[$container_delta]['headline'] = $source_promo_list_headline[0]->field_utexas_promo_list_headline_value;
      $source_promo_list_style = Database::getConnection()->select('field_data_field_utexas_promo_list_style', 's')
        ->condition('entity_id', $container->field_utexas_promo_list_value)
        ->fields('s', ['field_utexas_promo_list_style_value'])
        ->execute()
        ->fetchAll();
      $output[$container_delta]['style'] = $source_promo_list_style[0]->field_utexas_promo_list_style_value;
    }
    return $output;
  }

  /**
   * Save data as paragraph(s) & return the field data.
   *
   * @param array $source
   *   A simple key-value array of subfield & value.
   *
   * @return array
   *   A simple key-value array returned the metadata about the field
   */
  protected static function massageFieldData(array $source) {
    $destination = [];
    foreach ($source as $container_delta => $container) {
      if (isset($container['headline'])) {
        $destination[$container_delta]['headline'] = $container['headline'];
      }
      if (isset($container['items'])) {
        $destination[$container_delta]['promo_list_items'] = [];
        foreach ($container['items'] as $item_delta => $item) {
          if (isset($item->field_utexas_promo_list_item_headline)) {
            $destination[$container_delta]['promo_list_items'][$item_delta]['item']['headline'] = $item->field_utexas_promo_list_item_headline;
          }
          if (isset($item->field_utexas_promo_list_item_image_fid)) {
            $destination_mid = MigrateHelper::getMediaIdFromFid($item->field_utexas_promo_list_item_image_fid);
            $destination[$container_delta]['promo_list_items'][$item_delta]['item']['image'] = $destination_mid;
          }
          if (isset($item->field_utexas_promo_list_item_link)) {
            $destination[$container_delta]['promo_list_items'][$item_delta]['item']['link'] = MigrateHelper::prepareLink($item->field_utexas_promo_list_item_link);
          }
          if (isset($item->field_utexas_promo_list_item_copy_value)) {
            $destination[$container_delta]['promo_list_items'][$item_delta]['item']['copy']['value'] = $item->field_utexas_promo_list_item_copy_value;
          }
          if (isset($item->field_utexas_promo_list_item_copy_value)) {
            $destination[$container_delta]['promo_list_items'][$item_delta]['item']['copy']['format'] = 'restricted_html';
          }
        }
      }
      if (isset($destination[$container_delta]['promo_list_items'])) {
        $destination[$container_delta]['promo_list_items'] = serialize($destination[$container_delta]['promo_list_items']);
      }
    }
    // Finally, return all the Promo List Containers to the node.
    return $destination;
  }

}
