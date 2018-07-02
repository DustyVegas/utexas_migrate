<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Provides a 'utexas_migrate_node_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_migrate_node_destination"
 * )
 */
class UTexasMigrateNodeDestination extends Entity implements MigrateDestinationInterface {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $nid = $row->getSourceProperty('nid');
    $title = $row->getSourceProperty('title');
    $language = $row->getSourceProperty('language');
    $created = $row->getSourceProperty('created');
    $changed = $row->getSourceProperty('changed');
    $status = $row->getSourceProperty('status');
    $uid = $row->getSourceProperty('uid');
    $sticky = $row->getSourceProperty('sticky');
    $promote = $row->getSourceProperty('promote');
    try {
      $node = Node::create([
        'type' => $this->configuration['default_bundle'],
        'langcode' => $language,
        'id' => $nid,
        'title' => $title,
        'created' => $created,
        'changed' => $changed,
        'status' => $status,
        'uid' => $uid,
        'sticky' => $sticky,
        'promote' => $promote,
      ]);
      $node->save();
      return [$node->id()];
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Import of node with nid of :nid failed: :error - Code: :code", [
        ':nid' => $nid,
        ':error' => $e->getMessage(),
        ':code' => $e->getCode(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
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
    return 'node';
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
      $node = Node::load($destination_identifier['id']);
      if ($node != NULL) {
        $node->delete();
      }
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Rollback of node with nid of :nid failed: :error - Code: :code", [
        ':nid' => $destination_identifier['id'],
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
