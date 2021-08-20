<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Text formats processor.
 *
 * Map text formats from v2 to applicable v3 formats.
 *
 * @MigrateProcessPlugin(
 * id = "utexas_user_lookup"
 * )
 */
class UtexasUserLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return MigrateHelper::getDestinationUid($value);
  }

}
