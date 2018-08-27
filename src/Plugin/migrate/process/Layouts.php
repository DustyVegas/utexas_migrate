<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Layouts Processor.
 *
 * This plugin takes care of processing a D7 "Page Layout"
 * setting into something consumable buy a D8 "Layout Builder"
 * setting.
 *
 * @MigrateProcessPlugin(
 *   id = "utexas_process_layout"
 * )
 */
class Layouts extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $layout = unserialize($value);
    $blocks = $layout['block']['blocks'];

    // @todo: mapping.

    // Output (example only).
    $fields['field_block:node:utexas_flex_page:field_flex_page_pu'] = ['region' => 'left', 'weight' => 0];
    $fields['field_block:node:utexas_flex_page:field_flex_page_wysiwyg_b'] = ['region' => 'left', 'weight' => 1];
    $fields['field_block:node:utexas_flex_page:field_flex_page_pl'] = ['region' => 'right', 'weight' => 0];
    $components = [];

    // Loop through each field, defined above, to create each section component.
    foreach ($fields as $id => $settings) {
      $component = new SectionComponent(random_int(0, 1000), $settings['region'], [
        'id' => $id,
        'context_mapping' => ['entity' => 'layout_builder.entity'],
      ]);
      $component->setWeight($settings['weight']);
      $components[] = $component;
    }
    // Each section is stored in its own array.
    $section = [
      [
        'section' => new Section(
          'layout_utexas_50_50',
          [],
          $components
        ),
      ],
    ];
    return $section;
  }

}
