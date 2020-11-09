<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\taxonomy\Plugin\migrate\source\d7\Vocabulary;

/**
 * Taxonomy term source from database.
 *
 * @MigrateSource(
 *   id = "utexas_taxonomy_vocabulary",
 *   source_module = "utexas_migrate"
 * )
 */
class VocabSource extends Vocabulary {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if (isset($this->configuration['bundle'])) {
      $query->condition('v.machine_name', (array) $this->configuration['bundle'], 'IN');
    }
    if (isset($this->configuration['skip_bundle'])) {
      $query->condition('v.machine_name', (array) $this->configuration['skip_bundle'], 'NOT IN');
    }
    return $query;
  }

}
