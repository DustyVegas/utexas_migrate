<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

/**
 * Provides a 'utexas_migrate_standard_page_source' source plugin.
 *
 * @MigrateSource(
 *   id = "utexas_migrate_standard_page_source",
 *   source_module="utexas_migrate"
 * )
 */
class StandardPageSource extends FlexPageSource {

  /**
   * Add condition to parent query to get Landing Page nodes.
   */
  public function query() {
    // Inherit all field mappings shared between Standard & Landing page from
    // UTexasFlexPageSource.
    $query = parent::query();
    $query->condition('type', 'standard_page', '=');
    return $query;
  }

}
