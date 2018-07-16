<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Provides a 'utexas_node_source' migrate source.
 *
 * This provides a base source plugin for migrating Standard Page,
 * Landing Page, and other node types from UTDK 7.
 *
 * @MigrateSource(
 *  id = "utexas_migrate_node_source",
 *  source_module = "utexas_migrate"
 * )
 */
abstract class NodeSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n', array_keys($this->fields()));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Vid'),
      'type' => $this->t('Type'),
      'language' => $this->t('Language'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Changed'),
      'status' => $this->t('Status'),
      'uid' => $this->t('Author'),
      'sticky' => $this->t('Sticky'),
      'promote' => $this->t('Promote'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

}
