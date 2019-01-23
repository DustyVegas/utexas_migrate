<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\migrate\Plugin\migrate\destination\Entity;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Save all Flex Page fields from their respective source.
 *
 * @MigrateDestination(
 *   id = "flex_page_fields_destination"
 * )
 */
class FlexPageFieldsDestination extends Entity implements MigrateDestinationInterface {

  /**
   * Import function that runs on each row.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // This gets the NID we requested in the "process" declaration's
    // migration_lookup in utexas_flex_page_fields.yml.
    $destination = $row->getDestinationProperty('temp_nid');

    try {
      $node = Node::load($destination);
      // Add fields, as necessary, per the model below.
      // The first parameters is the Drupal 8 field name
      // The second parameter gets data from what has been defined
      // in FlexPageFieldsSource::prepareRow(), which may have callbacks
      // to other classes for more complex field mappings.
      $node->set('field_flex_page_wysiwyg_a', $row->getSourceProperty('wysiwyg_a'));
      $node->set('field_flex_page_wysiwyg_b', $row->getSourceProperty('wysiwyg_b'));
      // $node->set('field_flex_page_fca_a', $row->getSourceProperty('fca_a'));
      // $node->set('field_flex_page_fca_b', $row->getSourceProperty('fca_b'));
      // $node->set('field_flex_page_fh', $row->getSourceProperty('featured_highlight'));
      // $node->set('field_flex_page_hi', $row->getSourceProperty('hero'));
      $node->set('field_flex_page_il_a', $row->getSourceProperty('image_link_a'));
      $node->set('field_flex_page_il_b', $row->getSourceProperty('image_link_b'));
      $node->set('field_flex_page_pca', $row->getSourceProperty('photo_content_area'));
      $node->set('field_flex_page_pl', $row->getSourceProperty('promo_lists'));
      $node->set('field_flex_page_pu', $row->getSourceProperty('promo_units'));
      $node->set('field_flex_page_ql', $row->getSourceProperty('quick_links'));
      $node->set('field_flex_page_resource', $row->getSourceProperty('resource'));
      // Save the node with the fields!
      $node->save();
      return [$node->id()];
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Field import to node :nid failed: :error - Code: :code", [
        ':nid' => $destination,
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
