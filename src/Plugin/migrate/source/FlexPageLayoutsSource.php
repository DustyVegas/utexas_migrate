<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Query available layouts in Drupal 7 database and prepare them..
 *
 * @MigrateSource(
 *   id = "flex_page_layouts_source",
 *   source_module = "utexas_migrate"
 * )
 */
class FlexPageLayoutsSource extends NodeSource {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Inherit SQL joins from NodeSource.
    $query = parent::query();

    // We limit this to D7 node types which have these fields.
    $query->condition('type', ['landing_page', 'standard_page'], 'IN');
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
    $layout = $this->select('context', 'c')
      ->fields('c', ['reactions'])
      ->condition('name', 'context_field-node-' . $source_nid)
      ->execute()
      ->fetchField();
    $row->setSourceProperty('layout', $layout);
    return parent::prepareRow($row);
  }

}
