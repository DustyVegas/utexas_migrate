<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\block\Entity\Block;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\utexas_migrate\MigrateHelper;
use Drupal\utexas_migrate\WysiwygHelper;
use Drupal\utexas_migrate\CustomWidgets\BasicBlock;

/**
 * Provides a 'utexas_content_blocks_destination' destination plugin.
 *
 * @MigrateDestination(
 *   id = "utexas_content_blocks_destination"
 * )
 */
class ContentBlocksDestination extends Entity implements MigrateDestinationInterface {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    try {
      // Get block layout information where module = 'block' & 'delta' = $bid
      $block_layout = BasicBlock::getBlockLayout($row->getSourceProperty('bid'));
      $block_roles = BasicBlock::getBlockRoles($row->getSourceProperty('bid'));
      $visibility = BasicBlock::getVisibility($block_layout['visibility'], $block_layout['pages'], $block_roles);
      // print_r($visibility);
      $migrated_format = MigrateHelper::getDestinationTextFormat($row->getSourceProperty('format'));
      $block = BlockContent::create([
        'type' => 'basic',
        'info' => BasicBlock::getBlockInfo($row->getSourceProperty('bid')),
        'body' => [
          'value' => WysiwygHelper::process($row->getSourceProperty('body')),
          'format' => $migrated_format,
        ],
      ]);
      $block->save();

      // Place any active blocks in layout.
      if ($block_layout['region']) {
        $config = \Drupal::config('system.theme');
        $placed_block = Block::create([
          'id' => $block->id(),
          'weight' => $row->getSourceProperty('weight'),
          'theme' => $config->get('default'),
          'status' => TRUE,
          'region' => MigrateHelper::getMappedRegion($block_layout['region']),
          'plugin' => 'block_content:' . $block->uuid(),
          'settings' => [
            'label_display' => FALSE,
          ],
        ]);
        // Set the block visibility.
        if (isset($visibility['user_role'])) {
          $placed_block->setVisibilityConfig("user_role", $visibility['user_role']);
        }
        if (isset($visibility['request_path'])) {
          $placed_block->setVisibilityConfig("request_path", $visibility['request_path']);
        }
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
