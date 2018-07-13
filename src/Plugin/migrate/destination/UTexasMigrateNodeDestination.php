<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Provides a 'utexas_migrate_node_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_migrate_node_destination"
 * )
 */
abstract class UTexasMigrateNodeDestination extends Entity implements MigrateDestinationInterface {

  public $nodeProperties = [];

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $basic_node_properties = [
      'nid',
      'title',
      'language',
      'created',
      'changed',
      'status',
      'uid',
      'sticky',
      'promote',
    ];
    foreach ($basic_node_properties as $property) {
      $this->nodeProperties[$property] = $row->getSourceProperty($property);
    }
    $this->nodeProperties['type'] = $this->configuration['default_bundle'];

    // NOTE: this will not import items by itself. saveImportData() must be
    // called by an extending node type destination class.
  }

  /**
   * Helper function that actually saves node data.
   *
   * This MUST be called in classes that extend UTexasMigrateNodeDestination
   * as the last element in those extending classes' import() method.
   */
  protected function saveImportData() {
    try {
      $node = Node::create($this->nodeProperties);
      $node->save();
      return [$node->id()];
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Import of node failed: :error - Code: :code", [
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
