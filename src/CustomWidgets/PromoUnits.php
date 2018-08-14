<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;
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
    // Image style names changed, so we map them. Note that in D7,
    // the ...no_image style exists, whereas in D8, that field should
    // just default to the 'landscape' style.
    $image_style_options = [
      'utexas_promo_unit_landscape_image' => 'utexas_responsive_image_pu_landscape',
      'utexas_promo_unit_square_image' => 'utexas_responsive_image_pu_square',
      'utexas_promo_unit_portrait_image' => 'utexas_responsive_image_pu_portrait',
      'utexas_promo_unit_no_image' => '',
    ];

    // First save the promo unit instances as paragraphs & retrieve their IDs.
    foreach ($source as $delta => $instance) {
      $style = $instance['size_option'];
      $field_values = [
        'type' => 'utexas_promo_unit',
        'field_utexas_pu_headline' => [
          'value' => $instance['headline'],
        ],
        'field_utexas_pu_copy' => [
          'value' => $instance['copy'],
          'format' => 'flex_html',
        ],
        'field_utexas_pu_cta_link' => [
          'uri' => MigrateHelper::prepareLink($instance['cta_uri']),
          'title' => $instance['cta_title'],
        ],
        'field_utexas_pu_image_style' => [
          'value' => isset($image_style_options[$style]) ? $image_style_options[$style] : 'utexas_responsive_image_pu_landscape',
        ],
      ];
      if ($instance['image_fid'] != 0) {
        $destination_fid = MigrateHelper::getMediaIdFromFid($instance['image_fid']);
        $field_values['field_utexas_pu_image'] = [
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
    if (!empty($paragraphs)) {
      // Next, save a single Promo Unit Container, with the above
      // as its paragraph references.
      // Note that the zeroth delta can be used for the title, since
      // there is always only one title for Promo Units.
      $paragraph_container = Paragraph::create([
        'type' => 'utexas_promo_unit_container',
        'field_utexas_puc_title' => $source[0]['title'],
        'field_utexas_puc_items' => $paragraphs,
      ]);
      $paragraph_container->save();
      // Finally, return the *single* Promo Unit Container to the node.
      return [
        'target_id' => $paragraph_container->id(),
        'target_revision_id' => $paragraph_container->id(),
        'delta' => 0,
      ];
    }
  }

}
