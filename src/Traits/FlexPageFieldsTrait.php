<?php

namespace Drupal\utexas_migrate\Traits;

/**
 * Defines query and row elements shared between Standard Page & Landing Page.
 */
trait FlexPageFieldsTrait {

  /**
   * The text format that most fields should be migrated with.
   *
   * @var defaultTextFormat
   */
  protected $defaultTextFormat = 'flex_html';

  /**
   * The machine names of the fields mapping the source to destination.
   *
   * @var flexPageFields
   *
   * @see FlexPageSource
   * @see FlexPageDestination
   */
  protected $flexPageFields = [
    'field_wysiwyg_a' => 'field_flex_page_wysiwyg_a',
    'field_wysiwyg_b' => 'field_flex_page_wysiwyg_b',
  ];

}
