<?php

namespace Drupal\utevent_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Provides a 'utexas_event_entity' migrate source.
 *
 * @MigrateSource(
 *  id = "utexas_event_entity",
 *  source_module = "utexas_migrate"
 * )
 */
class EventEntity extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('utexas_event', 'b')
      ->fields('b', array_keys($this->fields()));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $detail_format = $row->getSourceProperty('detail_format');
    $row->setSourceProperty('detail_format', MigrateHelper::getDestinationTextFormat($detail_format));

    // Retrieve entity field data to replicate behavior of
    // \Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity.
    $source_tags = $this->select('field_data_field_event_tags', 't')
      ->fields('t', ['field_event_tags_tid'])
      ->condition('entity_id', $row->getSourceProperty('id'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('field_event_tags_tid', $source_tags);

    $source_locations = $this->select('field_data_field_event_location', 'l')
      ->fields('l', ['field_event_location_tid'])
      ->condition('entity_id', $row->getSourceProperty('id'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('field_event_location_tid', $source_locations);

    $start = $row->getSourceProperty('start');
    $end = $row->getSourceProperty('end');
    // Set smart_date array structure. Notes:
    // * None of the events from version 2 have recurrence rules, so we simply
    // set the `start`, `end` and duration values.
    // * Based on how values were stored in v2, the duration will always be
    // `end` - `start`. Events that are all day will have the same start & end
    // date, and smart date determines the all day property from a
    // duration of 0.
    // Theoretically, this could be done in a .yml file, but it is easier to
    // debug here.
    $row->setDestinationProperty('field_utevent_datetime', [
      'value' => $start,
      'end_value' => $end,
      'duration' => ($end - $start) / 60,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Entity ID'),
      'title' => $this->t('Title'),
      'all_day' => $this->t('All day'),
      'date_range' => $this->t('Date range'),
      'featured' => $this->t('Search'),
      'start' => $this->t('Account'),
      'end' => $this->t('Timeline List'),
      'detail_text' => $this->t('Detail text'),
      'detail_format' => $this->t('Detail format'),
      'summary_text' => $this->t('Summary'),
      'image_fid' => $this->t('Image'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
