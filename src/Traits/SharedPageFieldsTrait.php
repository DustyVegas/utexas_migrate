<?php

namespace Drupal\utexas_migrate\Traits;

use Drupal\migrate\Row;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Defines query and row elements shared between Standard Page & Landing Page.
 */
trait SharedPageFieldsTrait {

  /**
   * The machine names of the fields mapping the source to destination.
   *
   * @var sharedFields
   *
   * @see UTexasMigrateLandingPageDestination
   * & UTexasMigrateStandardPageDestination.
   */
  protected $sharedFields = [
    'field_wysiwyg_a' => 'field_flex_page_wysiwyg_a',
    'field_wysiwyg_b' => 'field_flex_page_wysiwyg_b',
  ];

  /**
   * Helper method to add SQL query joins.
   *
   * This is called by UTexasMigrateLandingPageSource and
   * UTexasMigrateStandardPageSource.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A Drupal Select query object.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The modified Drupal Select query object.
   */
  protected function setSharedQueryJoins(SelectInterface $query) {
    foreach ($this->sharedFields as $source => $destination) {
      $query->leftJoin('field_data_' . $source, $source, $source . '.entity_id = n.nid');
      $query->fields($source);
    }
    return $query;
  }

  /**
   * Helper method to populate source from Standard Page & Landing Page.
   *
   * Each field is listed sequentially,
   * since their value/format structure may differ.
   *
   * @param \Drupal\migrate\Row $row
   *   The actual node data from the source.
   */
  protected function setSharedSourceProperties(Row $row) {
    $default_format = 'flex_html';

    // WYSIWYG A.
    $row->setSourceProperty('field_wysiwyg_a', [
      'value' => $row->getSourceProperty('field_wysiwyg_a_value'),
      'format' => $default_format,
    ]);

    // WYSIWYG B.
    $row->setSourceProperty('field_wysiwyg_b', [
      'value' => $row->getSourceProperty('field_wysiwyg_b_value'),
      'format' => $default_format,
    ]);
  }

  /**
   * Helper method to send field data to appropriate handlers.
   *
   * For example, send a Flex Content Area compound field to be converted into
   * a Paragraph.
   *
   * @param string $source_name
   *   The machine name of the Drupal 7 field this corresponds to.
   * @param array $source_data
   *   The actual field data, in simple key => value format.
   */
  protected function prepareSharedField($source_name, array $source_data) {
    switch ($source_name) {
      case '':
        return $source_data;

      default:
        return $source_data;

    }
  }

}
