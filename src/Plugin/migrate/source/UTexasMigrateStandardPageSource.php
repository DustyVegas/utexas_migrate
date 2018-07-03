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
class UTexasMigrateStandardPageSource extends UTexasMigrateNodeSource {

  /**
   * Add condition to parent query to get Standard Page nodes.
   */
  public function query() {
    $query = parent::query();
    $query->condition('type', 'standard_page', '=');
    return $query;
  }

}
