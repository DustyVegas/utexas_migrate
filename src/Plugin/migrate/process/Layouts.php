<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Database;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin takes care of processing a D7 "Page Layout"
 * setting into something consumable buy a D8 "Layout Builder"
 * setting.
 *
 * @MigrateProcessPlugin(
 *   id = "utexas_process_layout"
 * )
 */
class Layouts extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $layout = unserialize($value);
    $blocks = $layout['block']['blocks'];

    return $value;
  }

}
