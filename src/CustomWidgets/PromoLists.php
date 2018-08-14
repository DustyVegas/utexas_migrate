<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
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
    }
    return $output;
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
    foreach ($source as $container_delta => $container) {
      $paragraphs = [];
      foreach ($container['items'] as $item_delta => $instance) {
        $field_values = [
          'type' => 'utexas_promo_list',
          'field_utexas_pl_headline' => [
            'value' => $instance->field_utexas_promo_list_item_headline,
          ],
          'field_utexas_pl_copy' => [
            'value' => $instance->field_utexas_promo_list_item_copy_value,
            'format' => 'flex_html',
          ],
          'field_utexas_pl_link' => [
            'uri' => MigrateHelper::prepareLink($instance->field_utexas_promo_list_item_link),
          ],
        ];
        if ($instance->field_utexas_promo_list_item_image_fid != 0) {
          $destination_fid = MigrateHelper::getMediaIdFromFid($instance->field_utexas_promo_list_item_image_fid);
          $field_values['field_utexas_pl_image'] = [
            'target_id' => $destination_fid,
            'alt' => '@to be replaced with media reference',
          ];
        }
        $paragraph_instance = Paragraph::create($field_values);
        $paragraph_instance->save();
        $paragraphs[] = [
          'target_id' => $paragraph_instance->id(),
          'target_revision_id' => $paragraph_instance->id(),
          'delta' => $item_delta,
        ];
      }
      // Next, save the Promo List Container, with the above
      // as its paragraph references.
      $paragraph_container = Paragraph::create([
        'type' => 'utexas_promo_list_container',
        'field_utexas_plc_headline' => $container['headline'],
        'field_utexas_plc_items' => $paragraphs,
      ]);
      $paragraph_container->save();
      $paragraph_containers[] = [
        'target_id' => $paragraph_container->id(),
        'target_revision_id' => $paragraph_container->id(),
        'delta' => $container_delta,
      ];
    }
    // Finally, return all the Promo List Containers to the node.
    return $paragraph_containers;
  }

}
