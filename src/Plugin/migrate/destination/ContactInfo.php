<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\block\Entity\Block;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Provides a destination plugin.
 *
 * @MigrateDestination(
 *   id = "contact_info"
 * )
 */
class ContactInfo extends Entity implements MigrateDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Query all IDs stored on Standard Page & Landing Page.
    $referenced_on_pages = self::getReferencedContactInfo();
    if (!in_array($row->getSourceProperty('id'), $referenced_on_pages)) {
      return [1];
    }
    try {
      $block = BlockContent::create([
        'type' => 'utexas_flex_list',
        'info' => 'Contact Info: ' . $row->getSourceProperty('label'),
        'field_utexas_flex_list_items' => $row->getSourceProperty('fields'),
      ]);
      $block->save();
      $region = $row->getSourceProperty('region');
      if ($region) {
        $config = \Drupal::config('system.theme');
        $placed_block = Block::create([
          'id' => $block->id(),
          'weight' => 0,
          'theme' => $config->get('default'),
          'status' => TRUE,
          'region' => 'hidden',
          'plugin' => 'block_content:' . $block->uuid(),
          'settings' => [],
        ]);
        $placed_block->save();
      }
      return [$block->id()];
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Import of block failed: :error - Code: :code", [
        ':error' => $e->getMessage(),
        ':code' => $e->getCode(),
      ]);
    }
  }

  /**
   * Retrieve a user entity ID from the migration map.
   *
   * @param int $uid
   *   A user entity ID from the source site.
   *
   * @return mixed
   *   Returns the matching media entity ID or FALSE.
   */
  public static function getReferencedContactInfo() {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $result = $source_db->select('field_data_field_utexas_contact_info', 'r')
      ->fields('r', ['field_utexas_contact_info_target_id'])
      ->condition('bundle', ['standard_page', 'landing_page'], 'IN')
      ->execute()
      ->fetchCol('field_utexas_contact_info_target_id');
    return array_values($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'unsigned' => FALSE,
        'size' => 'big',
      ],
    ];
  }

  /**
   * Finds the entity type from configuration or plugin ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return string
   *   The entity type.
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'block';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    try {
      $block = BlockContent::load($destination_identifier['entity_id']);
      if ($block != NULL) {
        $block->delete();
      }
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Rollback of block with id of :bid failed: :error - Code: :code", [
        ':bid' => $destination_identifier['id'],
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

}
