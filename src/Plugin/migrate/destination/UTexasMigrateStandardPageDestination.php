<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\utexas_migrate\Traits\SharedPageFieldsTrait;

/**
 * Provides a 'utexas_migrate_standard_page_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_migrate_standard_page_destination"
 * )
 */
class UTexasMigrateStandardPageDestination extends UTexasMigrateNodeDestination {

  use SharedPageFieldsTrait;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // In this case, the parent UTexasMigrateNodeDestination class is providing
    // standard node entity type properties, like title, author, promoted, etc.,
    // to the $node_properties array, referenced in saveImportData().
    parent::import($row, $old_destination_id_values);

    // Populate rows shared between Standard Page & Landing Page.
    foreach ($this->sharedFields as $source => $destination) {
      $this->nodeProperties[$destination] = $row->getSourceProperty($source);
    }

    /* Add import mapping for fields specific to this node type. */

    // @see UTexasMigrateNodeDestination.
    return $this->saveImportData();
  }

}
