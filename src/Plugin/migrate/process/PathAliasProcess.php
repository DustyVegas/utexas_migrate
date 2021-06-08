<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Update any nodes which have pathauto unchecked.
 *
 * @MigrateProcessPlugin(
 *   id = "utexas_path_alias_process"
 * )
 */
class PathAliasProcess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (\Drupal::moduleHandler()->moduleExists('utprof_migrate') === FALSE && $row->get('type') === 'team_member') {
      throw new MigrateSkipRowException('Skipping team_member/profile nodes given utprof content type does not exist.');
    }
    if (\Drupal::moduleHandler()->moduleExists('utnews_migrate') === FALSE && $row->get('type') === 'news') {
      throw new MigrateSkipRowException('Skipping news nodes given utnews content type does not exist.');
    }
    if (\Drupal::moduleHandler()->moduleExists('utevent_migrate') === FALSE && $row->get('type') === 'event') {
      throw new MigrateSkipRowException('Skipping event nodes given utevent content type does not exist.');
    }
    return $value;
  }

}
