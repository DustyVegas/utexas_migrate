<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\utexas_migrate\MigrateHelper;
use Drupal\utexas_migrate\WysiwygHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 *
 * @see doc/decisions/0002-migration-processors-for-custom-components.md
 */
class QuickLinks {

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
    $block_definition = [
      'type' => 'utexas_quick_links',
      'info' => $data['field_identifier'],
      'field_block_ql' => $data['block_data']['field'],
      'reusable' => FALSE,
    ];
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
    // Get all instances from the legacy DB.
    Database::setActiveConnection('utexas_migrate');
    $source_data = Database::getConnection()->select('field_data_field_utexas_quick_links', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'headline' => $item->{'field_utexas_quick_links_headline'},
        'copy' => $item->{'field_utexas_quick_links_copy_value'},
        'copy_format' => $item->{'field_utexas_quick_links_copy_format'},
        'links' => $item->{'field_utexas_quick_links_links'},
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
    // Technically, there should only ever be one delta for Quick Links.
    $instances = [];
    // If the first Quick Links instance has a headline, populate the block
    // title with it.
    if (!empty($source[0]['headline'])) {
      $instances['title'] = $source[0]['headline'];
      // Note: label => TRUE: Quick Links headlines from v2 are migrated to
      // block titles in v3.
      $instances['label'] = 'visible';
    }
    foreach ($source as $delta => $instance) {
      // Migrate the headline field into the block title (see above).
      $instances['field'][$delta]['headline'] = '';

      $instances['field'][$delta]['copy_value'] = WysiwygHelper::process($instance['copy']);
      $instances['field'][$delta]['copy_format'] = MigrateHelper::prepareTextFormat($instance['copy_format']);

      $links = unserialize($instance['links']);
      if (!empty($links)) {
        foreach ($links as $i => $link) {
          $prepared_links[] = [
            'uri' => MigrateHelper::prepareLink($link['link_url']),
            'title' => $link['link_title'],
          ];
        }
        $instances['field'][$delta]['links'] = serialize($prepared_links);
      }
    }
    // Quick Links should always display with border w/o background.
    $instances['additional'] = [
      'layout_builder_styles_style' => [
        'utexas_border_without_background' => 'utexas_border_without_background',

      ],
    ];
    return $instances;
  }

}
