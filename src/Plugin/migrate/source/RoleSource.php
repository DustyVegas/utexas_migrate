<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\Role;

/**
 * Roles source definition.
 *
 * This extends the core `Role` source plugin by adding the ability to specify
 * which roles should be included/excluded.
 *
 * @MigrateSource(
 *   id = "utexas_role",
 *   source_module = "user"
 * )
 */
class RoleSource extends Role {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if (isset($this->configuration['name'])) {
      $query->condition('v.name', (array) $this->configuration['name'], 'IN');
    }
    if (isset($this->configuration['skip_name'])) {
      // If no roles are left after skipping, return a '0' query for the
      // purposes of the migration.
      $this->configuration['skip_name'][] = 'authenticated user';
      $this->configuration['skip_name'][] = 'anonymous user';
      $count = $this->database->select('role', 'r')
        ->fields('r', ['name'])
        ->condition('name', $this->configuration['skip_name'], 'NOT IN')
        ->countQuery()->execute()->fetchField();
      if ($count == 0) {
        $query->condition('name', (array) $this->configuration['skip_name'], 'NOT IN');
      }
      else {
        $query = $this->select('role', 'v')
          ->fields('v', array_keys($this->fields()))
          ->condition('name', 'invalid_key_used_for_migration', '=');
        return $query;
      }
    }
    return $query;
  }

}
