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
    // First, manipulate the D7 context into a usable array.
    $d7_data = self::formatD7Data($value);
    // Next, put those array elements into D8 section objects.
    foreach ($d7_data as $d7_section) {
      $d8_components = [];
      foreach ($d7_section['components'] as $id => $d7_component) {
        $d8_component = self::createD8SectionComponent($d7_component['type'], md5($id), $id, $d7_component['region'], $d7_component['weight']);
        $d8_components[] = $d8_component;
      }
      $section = self::createD8Section($d7_section['layout'], $d8_components);
      $sections[] = $section;
    }
    return $sections;
  }

  /**
   * Get Drupal 7 layout data into a traversable format.
   *
   * @param string $value
   *   A serialized array of layout data from the "context" table.
   */
  protected function formatD7Data($value) {
    $layout = unserialize($value);
    $blocks = $layout['block']['blocks'];

    // @todo: mapping.

    // Output (example only).
    $sections = [];
    $sections[] = [
      'layout' => 'layout_utexas_50_50',
      'components' => [
        'field_block:node:utexas_flex_page:field_flex_page_pu' => ['type' => 'field_block', 'region' => 'left', 'weight' => 0],
        'field_block:node:utexas_flex_page:field_flex_page_pl' => ['type' => 'field_block', 'region' => 'right', 'weight' => 0],
        'field_block:node:utexas_flex_page:field_flex_page_wysiwyg_b' => ['type' => 'field_block', 'region' => 'left', 'weight' => 1],
      ],
    ];
    $sections[] = [
      'layout' => 'layout_utexas_fullwidth',
      'components' => [
        'field_block:node:utexas_flex_page:field_flex_page_fh' => ['type' => 'field_block', 'region' => 'main', 'weight' => 0],
      ],
    ];
    return $sections;
  }

  /**
   * Helper function to create a section.
   *
   * @param string $layout
   *   The D8 machine name of the layout to be used.
   * @param array $components
   *   An array of sectionComponents (i.e., fields)
   */
  protected function createD8Section($layout, array $components) {
    // Each section is stored in its own array.
    $section = new Section($layout, [], $components);
    return $section;
  }

  /**
   * Helper method to take field data & create a SectionComponent object.
   *
   * @param string $type
   *   What type of field is this? Typically "field_block".
   * @param string $uuid
   *   The UUID as constructed from the D8 field name.
   * @param string $id
   *   The field id.
   * @param string $region
   *   The layout region that this component should be placed in.
   * @param int $weight
   *   The vertical order that this component should show in the region.
   */
  protected function createD8SectionComponent($type, $uuid, $id, $region, $weight) {
    switch ($type) {
      case 'field_block':
        $component = new SectionComponent($uuid, $region, [
          'id' => $id,
          'context_mapping' => ['entity' => 'layout_builder.entity'],
        ]);
        $component->setWeight($weight);
        break;
    }
    return $component;
  }

}
