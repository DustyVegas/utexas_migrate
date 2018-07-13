<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\utexas_migrate\Traits\SharedPageFieldsTrait;

/**
 * Provides a 'utexas_migrate_landing_page_source' source plugin.
 *
 * @MigrateSource(
 *   id = "utexas_migrate_landing_page_source",
 *   source_module="utexas_migrate"
 * )
 */
class UTexasMigrateLandingPageSource extends UTexasMigrateNodeSource {

  use SharedPageFieldsTrait;

  /**
   * Add condition to parent query to get Landing Page nodes.
   */
  public function query() {
    $query = parent::query();

    // Perform SQL joins on fields shared between Standard & Landing page.
    // @see SharedPageFieldsTrait.
    $query = $this->setSharedQueryJoins($query);

    $query->condition('type', 'landing_page', '=');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);

    // Map the source data into a row that can be consumed by the destination.
    // @see SharedPageFieldsTrait.
    $this->setSharedSourceProperties($row);

    return $row;
  }

}
