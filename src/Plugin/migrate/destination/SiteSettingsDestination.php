<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'utexas_site_settings_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_site_settings_destination"
 * )
 */
class SiteSettingsDestination extends DestinationBase implements MigrateDestinationInterface {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Additional site settings may be added here as needed.
    // Default flex_page breadcrumb is derived from the standard_page value
    // in the D7 theme settings.
    $breadcrumb_display = $row->getSourceProperty('default_breadcrumb_display');
    $flex_page_breadcrumb_display = \Drupal::configFactory()->getEditable('utexas_breadcrumbs_visibility.content_type.utexas_flex_page');
    $flex_page_breadcrumb_display->set('display_breadcrumbs', $breadcrumb_display);
    $flex_page_breadcrumb_display->save();

    // As an array of 1 item, this will indicate that the migration operation
    // completed its one task (composed of multiple settings).
    return ['site_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    try {
      // Config for flex_page defaults to 1 for breadcrumb visibility. Reset.
      $flex_page_breadcrumb_display = \Drupal::configFactory()->getEditable('utexas_breadcrumbs_visibility.content_type.utexas_flex_page');
      $flex_page_breadcrumb_display->set('display_breadcrumbs', 1);
      $flex_page_breadcrumb_display->save();
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Rollback of site_settings failed. :error - Code: :code", [
        ':error' => $e->getMessage(),
        ':code' => $e->getCode(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsRollback() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackAction() {
    return MigrateIdMapInterface::ROLLBACK_DELETE;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // Not needed; must be implemented to respect MigrateDestinationInterface.
  }

}
