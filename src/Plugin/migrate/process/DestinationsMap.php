<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\utexas_migrate\MigrateHelper;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Given a source path, get the destination.
 *
 * This process plugin is necessary (over the core Migration Lookup plugin)
 * when we need to know the destination across entity type (nodes, users, files)
 * and when we can't be sure which mappings are available (e.g., utevent_nodes)
 * may not have been executed.
 *
 * @MigrateProcessPlugin(
 *   id = "utexas_destinations_map"
 * )
 */
class DestinationsMap extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Note: if an external URL is passed to this function, or special paths
    // like <front> (such as could be the case with a redirect destination),
    // this will return the external URL intact.
    $destination = MigrateHelper::getDestinationFromSource($value);
    if ($destination === FALSE) {
      $migrate_executable->saveMessage('Unable to find applicable mapping for ' . $value, MigrationInterface::MESSAGE_WARNING);
    }
    // Redirects should not include a preceding slash for manipulation; this is
    // supplied by the core d7_redirect_source_query.
    $destination = trim($destination, "/");
    return $destination;
  }

}
