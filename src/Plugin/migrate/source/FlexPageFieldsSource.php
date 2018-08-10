<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\utexas_migrate\CustomWidgets\FlexContentArea;
use Drupal\utexas_migrate\CustomWidgets\PhotoContentArea;
use Drupal\utexas_migrate\CustomWidgets\PromoUnits;
use Drupal\utexas_migrate\CustomWidgets\QuickLinks;
use Drupal\utexas_migrate\CustomWidgets\Resource;

/**
 * Query available fields in Drupal 7 database and prepare them..
 *
 * @MigrateSource(
 *   id = "flex_page_fields_source",
 *   source_module = "utexas_migrate"
 * )
 */
class FlexPageFieldsSource extends NodeSource {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Inherit SQL joins from NodeSource.
    $query = parent::query();
    // Add joins, as necessary, per each field you want to migrate.
    // For fields with multiple deltas, do a separate query in a callback.
    $query->leftJoin('field_data_field_wysiwyg_a', 'wysiwyg_a', 'wysiwyg_a.entity_id = n.nid');
    $query->leftJoin('field_data_field_wysiwyg_b', 'wysiwyg_b', 'wysiwyg_b.entity_id = n.nid');

    // We limit this to D7 node types which have these fields.
    $query->condition('type', ['landing_page', 'standard_page'], 'IN');

    // Optionally, you can specify an array as a second parameter to limit
    // the columns returned.
    $query->fields('wysiwyg_a');
    $query->fields('wysiwyg_b');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_nid = $row->getSourceProperty('nid');
    // For simple field preparation, such as WYSIWYG,
    // you can just define the structure of the D8 array here.
    // For complex fields, add an external class and "use" it in this class.
    $wysiwyg_a = $row->getSourceProperty('field_wysiwyg_a_value');
    $row->setSourceProperty('wysiwyg_a', ['value' => $wysiwyg_a, 'format' => 'flex_html']);
    $wysiwyg_b = $row->getSourceProperty('field_wysiwyg_b_value');
    $row->setSourceProperty('wysiwyg_b', ['value' => $wysiwyg_b, 'format' => 'flex_html']);

    // Here, the first parameter to convert() specifies FCA 'A' or 'B' data.
    $row->setSourceProperty('fca_a', FlexContentArea::convert('a', $source_nid));
    $row->setSourceProperty('fca_b', FlexContentArea::convert('b', $source_nid));

    $row->setSourceProperty('promo_units', PromoUnits::convert($source_nid));
    $row->setSourceProperty('quick_links', QuickLinks::convert($source_nid));
    $row->setSourceProperty('photo_content_area', PhotoContentArea::convert($source_nid));
    $row->setSourceProperty('resource', Resource::convert($source_nid));

    return parent::prepareRow($row);
  }

}
