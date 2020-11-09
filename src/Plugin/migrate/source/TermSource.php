<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\taxonomy\Plugin\migrate\source\d7\Term;

/**
 * Taxonomy term source from database.
 *
 * @MigrateSource(
 *   id = "utexas_taxonomy",
 *   source_module = "utexas_migrate"
 * )
 */
class TermSource extends Term {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if (isset($this->configuration['skip_bundle'])) {
      $query->condition('tv.machine_name', (array) $this->configuration['skip_bundle'], 'NOT IN');
    }
    return $query;
  }

}