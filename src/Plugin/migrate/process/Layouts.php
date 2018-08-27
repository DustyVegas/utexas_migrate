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
    $data = [
      [
        'section' => new Section(
          'layout_utexas_50_50',
          [],
          [
            '0' => new SectionComponent('ec93b42c-0668-4b92-ae60-d9091684440f', 'left', [
              'id' => 'field_block:node:utexas_flex_page:field_flex_page_pu',
              'context_mapping' => [
                'entity' => 'layout_builder.entity',
              ],
            ]),
            '1' => new SectionComponent('bar', 'right', [
              'id' => 'field_block:node:utexas_flex_page:field_flex_page_pl',
              'context_mapping' => [
                'entity' => 'layout_builder.entity',
              ],
            ]),
          ]
        ),
      ],
    ];
    return $data;
  }

}
