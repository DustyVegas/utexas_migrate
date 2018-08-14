<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class Resource {

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
    $source_data = Database::getConnection()->select('field_data_field_utexas_resource', 'f')
      ->fields('f')
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchAll();
    $prepared = [];
    foreach ($source_data as $delta => $item) {
      $prepared[$delta] = [
        'title' => $item->{'field_utexas_resource_title'},
        'headline' => $item->{'field_utexas_resource_headline'},
        'image_fid' => $item->{'field_utexas_resource_image_fid'},
        'links' => $item->{'field_utexas_resource_links'},
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
    // First save the resource instances as paragraphs & retrieve their IDs.
    foreach ($source as $delta => $instance) {
      $field_values = [
        'type' => 'utexas_resource',
        'field_utexas_resource_headline' => [
          'value' => $instance['headline'],
        ],
      ];
      if ($instance['image_fid'] != 0) {
        $destination_fid = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
        $field_values['field_utexas_resource_image'] = [
          'target_id' => $destination_fid,
          'alt' => '@to be replaced with media reference',
        ];
      }
      $links = unserialize($instance['links']);
      if (!empty($links)) {
        foreach ($links as $delta => $link) {
          $prepared_links[] = [
            'uri' => MigrateHelper::prepareLink($link['link_url']),
            'title' => $link['link_title'],
            'delta' => $delta,
          ];
        }
        $field_values['field_utexas_resource_links'] = $prepared_links;
      }
      $paragraph_instance = Paragraph::create($field_values);
      $paragraph_instance->save();
      $paragraphs[] = [
        'target_id' => $paragraph_instance->id(),
        'target_revision_id' => $paragraph_instance->id(),
        'delta' => $delta,
      ];
    }
    if (!empty($paragraphs)) {
      // Next, save a single Resource Container, with the above
      // as its paragraph references.
      // Note that the zeroth delta can be used for the title, since
      // there is always only one title for Resources.
      $paragraph_container = Paragraph::create([
        'type' => 'utexas_resource_container',
        'field_utexas_rc_title' => $source[0]['title'],
        'field_utexas_rc_items' => $paragraphs,
      ]);
      $paragraph_container->save();
      // Finally, return the *single* Resource Container to the node.
      return [
        'target_id' => $paragraph_container->id(),
        'target_revision_id' => $paragraph_container->id(),
        'delta' => 0,
      ];
    }

  }

}
