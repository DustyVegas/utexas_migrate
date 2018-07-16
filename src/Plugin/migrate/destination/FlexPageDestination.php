<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\utexas_migrate\Traits\FlexPageFieldsTrait;

/**
 * Provides a 'utexas_migrate_flex_page_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_migrate_flex_page_destination"
 * )
 */
class FlexPageDestination extends NodeDestination {

  use FlexPageFieldsTrait;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // In this case, the parent NodeDestination class is providing
    // standard node entity type properties, like title, author, promoted, etc.,
    // to the $node_properties array, referenced in saveImportData().
    parent::import($row, $old_destination_id_values);

    // Pass each shared field through a "prepare" function, which can handle
    // more complex operations, like creating a Paragraph type to be referenced.
    foreach ($this->flexPageFields as $source => $destination) {
      $this->nodeElements[$destination] = $this->prepareField($source, $row->getSourceProperty($source));
    }

    // @see NodeDestination.
    return $this->saveImportData();
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
  protected function prepareField($source_name, array $source_data) {
    switch ($source_name) {
      case '':
        // @todo: replace with callbacks to field-specific preparation.
        return $source_data;

      default:
        // Return the structured data as-is from the source.
        return $source_data;

    }
  }

}
