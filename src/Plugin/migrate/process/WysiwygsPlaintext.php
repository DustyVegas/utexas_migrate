<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\utexas_migrate\WysiwygHelper;

/**
 * Wysiwyg processor.
 *
 * Return a plaintext version of copy input.
 *
 * @MigrateProcessPlugin(
 *   id = "utexas_wysiwygs_plaintext"
 * )
 */
class WysiwygsPlaintext extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $prepared = WysiwygHelper::process($value);
    // In addition to removing 'traditional' HTML, this will remove Drupal
    // media shortcodes and URL shortcodes, since those are HTML format
    // (<drupal-url> and <drupal-media>)
    $prepared = strip_tags($prepared);
    return $prepared;
  }
}
