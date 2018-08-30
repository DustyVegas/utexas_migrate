<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Query available layouts in Drupal 7 database and prepare them..
 *
 * @MigrateSource(
 *   id = "flex_page_layouts_source",
 *   source_module = "utexas_migrate"
 * )
 */
class FlexPageLayoutsSource extends NodeSource {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Inherit SQL joins from NodeSource.
    $query = parent::query();

    // We limit this to D7 node types which have these fields.
    $query->condition('type', ['landing_page', 'standard_page'], 'IN');
    return $query;
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

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_nid = $row->getSourceProperty('nid');
    $layout = $this->select('context', 'c')
      ->fields('c', ['reactions'])
      ->condition('name', 'context_field-node-' . $source_nid)
      ->execute()
      ->fetchField();
    $row->setSourceProperty('layout', $layout);

    // Retrieve the template name.
    $template_id = $this->select('field_data_field_template', 't')
      ->fields('t', ['field_template_target_id'])
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchField();
    $template_name = $this->select('utexas_templates', 't')
      ->fields('t', ['name'])
      ->condition('id', $template_id)
      ->execute()
      ->fetchField();
    $row->setSourceProperty('template', $template_name);

    // Retrieve the social links field; it is no longer a field in D8.
    $social_links = $this->select('field_data_field_utexas_social_links', 's')
      ->fields('s', ['entity_id'])
      ->condition('entity_id', $source_nid)
      ->execute()
      ->fetchField();
    if ($social_links) {
      $social_link_block_id = \Drupal::database()->select('migrate_map_utexas_social_link_fields')
        ->fields('migrate_map_utexas_social_link_fields', ['destid1'])
        ->condition('sourceid1', $social_links, '=')
        ->execute()
        ->fetchField();
      $uuid = \Drupal::database()->select('block_content')
        ->fields('block_content', ['uuid'])
        ->condition('id', $social_link_block_id, '=')
        ->execute()
        ->fetchField();
      $row->setSourceProperty('social_link_id', $uuid);
    }

    return parent::prepareRow($row);
  }

}
