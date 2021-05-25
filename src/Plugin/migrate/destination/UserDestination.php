<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\user\Plugin\migrate\destination\EntityUser;

/**
 * Provides a 'user' destination plugin. The id MUST end in the entity name.
 *
 * @MigrateDestination(
 *   id = "utexas:user"
 * )
 */
class UserDestination extends EntityUser {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Do not overwrite the root account password.
    if ($row->getSourceProperty('uid') == 1) {
      $row->removeDestinationProperty('pass');
      $row->setDestinationProperty('uid', 1);
    }
    if ($row->getSourceProperty('uid') == 0) {
      $row->setDestinationProperty('uid', 0);
      // Do not import the anonymous user, but make it available for future mappings.
      return [0];
    }
    return parent::import($row, $old_destination_id_values);
  }

}
