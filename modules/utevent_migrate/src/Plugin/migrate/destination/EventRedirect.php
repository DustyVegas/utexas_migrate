<?php

namespace Drupal\utevent_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Drupal\redirect\Entity\Redirect;

/**
 * Provides the 'utevent:node' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utevent:redirect"
 * )
 */
class EventRedirect extends EntityContentBase {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $source_id = $row->getSourceProperty('id');
    $destination_id = \Drupal::database()->select('migrate_map_utevent_nodes', 'n')
      ->fields('n', ['destid1'])
      ->condition('n.sourceid1', $source_id)
      ->execute()
      ->fetchField();
    Redirect::create([
      'redirect_source' => 'events/' . $source_id,
      'redirect_redirect' => 'internal:/node/' . $destination_id,
      'status_code' => 301,
    ])->save();
    return [$destination_id];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRollback() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // No action.
  }

}
