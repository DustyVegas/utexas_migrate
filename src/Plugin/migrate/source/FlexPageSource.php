<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\utexas_migrate\Traits\FlexPageFieldsTrait;

/**
 * Provides a 'utexas_flex_page_source' source plugin.
 *
 * @MigrateSource(
 *   id = "utexas_flex_page_source",
 *   source_module="utexas_migrate"
 * )
 */
class FlexPageSource extends NodeSource {

  use FlexPageFieldsTrait;

  /**
   * Add condition to parent query to get Landing Page nodes.
   */
  public function query() {
    // Inherit SQL joins from UTexasNodeSource.
    $query = parent::query();

    // @see FlexPageFieldsTrait.
    foreach ($this->flexPageFields as $source => $destination) {
      $query->leftJoin('field_data_' . $source, $source, $source . '.entity_id = n.nid');
      $query->fields($source);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);

    // Map the source data into a row that can be consumed by the destination.
    // WYSIWYG A.
    $row->setSourceProperty('field_wysiwyg_a', [
      'value' => $row->getSourceProperty('field_wysiwyg_a_value'),
      'format' => $this->defaultTextFormat,
    ]);

    // WYSIWYG B.
    $row->setSourceProperty('field_wysiwyg_b', [
      'value' => $row->getSourceProperty('field_wysiwyg_b_value'),
      'format' => $this->defaultTextFormat,
    ]);

    return $row;
  }

}
