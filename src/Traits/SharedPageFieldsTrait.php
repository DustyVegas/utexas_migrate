<?php

namespace Drupal\utexas_migrate\Traits;

/**
 * Defines query and row elements shared between Standard Page & Landing Page.
 */
trait SharedPageFieldsTrait {

  /**
   * The machine names of the fields mapping the source to destination.
   *
   * @var sharedFields
   *
   * See @UTexasMigrateLandingPageDestination
   * & @UTexasMigrateStandardPageDestination.
   */
  public $sharedFields = [
    'field_wysiwyg_a' => 'field_flex_page_wysiwyg_a',
    'field_wysiwyg_b' => 'field_flex_page_wysiwyg_b',
  ];

  /**
   * Helper method to add SQL query joins.
   *
   * This is called by UTexasMigrateLandingPageSource and
   * UTexasMigratestandardPageSource.
   */
  public function setSharedQueryJoins($query) {
    foreach ($this->sharedFields as $source => $destination) {
      $query->leftJoin('field_data_' . $source, $source, $source . '.entity_id = n.nid');
      $query->fields($source);
    }
    return $query;
  }

  /**
   * Helper method to populate source from Standard Page & Landing Page.
   *
   * Each field is listed sequentially,
   * since their value/format structure may differ.
   */
  public function setSharedSourceProperties($row) {
    $default_format = 'flex_html';

    // WYSIWYG A.
    $row->setSourceProperty('field_wysiwyg_a', [
      'value' => $row->getSourceProperty('field_wysiwyg_a_value'),
      'format' => $default_format,
    ]);

    // WYSIWYG B.
    $row->setSourceProperty('field_wysiwyg_b', [
      'value' => $row->getSourceProperty('field_wysiwyg_b_value'),
      'format' => $default_format,
    ]);
  }

}
