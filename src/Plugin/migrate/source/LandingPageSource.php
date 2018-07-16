<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

/**
 * Provides a 'utexas_migrate_landing_page_source' source plugin.
 *
 * @MigrateSource(
 *   id = "utexas_migrate_landing_page_source",
 *   source_module="utexas_migrate"
 * )
 */
class LandingPageSource extends FlexPageSource {

  /**
   * Add condition to parent query to get Landing Page nodes.
   */
  public function query() {
    // Inherit all field mappings shared between Standard & Landing page from
    // UTexasFlexPageSource.
    $query = parent::query();
    $query->condition('type', 'landing_page', '=');
    return $query;
  }

}
