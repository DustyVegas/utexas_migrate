<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Convert D7 custom compound field to D8 paragraph.
 */
class QuickLinks {

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
        'links' => $item->{'field_utexas_quick_links_links'},
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
    // Technically, there should only ever be one delta for Quick Links.
    // See explanation in getQuickLinksSource().
    foreach ($source as $delta => $instance) {
      // The 'type' value here is the Paragraph type machine name.
      $field_values = [
        'type' => 'utexas_quick_links',
        'field_utexas_ql_headline' => [
          'value' => $instance['headline'],
        ],
        'field_utexas_ql_copy' => [
          'value' => $instance['copy'],
          'format' => 'flex_html',
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
        $field_values['field_utexas_ql_links'] = $prepared_links;
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
