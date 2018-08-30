<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\Entity\Node;

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

  protected static $map = [
    'fieldblock-fda604d130a57f15015895c8268f20d2' => 'field_flex_page_wysiwyg_a',
    'fieldblock-bf40687156268eaa30437ed84189f13e' => 'field_flex_page_wysiwyg_b',
    'fieldblock-9c079efa827f76dea650869c5d2631e6' => 'field_flex_page_fca_a',
    'fieldblock-2c880c8461bc3ce5a6ac19b2e7791346' => 'field_flex_page_fca_a',
    'fieldblock-208a521aa519bc1ed37d8992aeffae83' => 'field_flex_page_pu',
    'fieldblock-f4361d99a73eca8a4329c07d0724a554' => 'field_flex_page_hi',
    'fieldblock-6986914623a8e5646904aca42f9f452e' => 'field_flex_page_il_a',
    'fieldblock-738c0498378ce2c32ba571a0a69457dc' => 'field_flex_page_il_b',
    'fieldblock-669a6a1f32566fa73ea7974696027184' => 'field_flex_page_ql',
    'fieldblock-c4c10ae36665adf0e722e7e3f4be74d4' => 'field_flex_page_pl',
    'fieldblock-553096d7ea242fc7edcddc53f719d074' => 'field_flex_page_fh',
    'fieldblock-29dbb1cb2c1033fdddae49c21ad4a9f5' => 'field_flex_page_pca',
    'fieldblock-e01ea87c2dadf3edda4cc61011b33637' => 'field_flex_page_resource',
    'fieldblock-6f3b85225f51542463a88e53104f8753' => 'field_flex_page_wysiwyg_a',
    'fieldblock-9a6760fa853859ac84ff3a273ab79869' => 'field_flex_page_wysiwyg_b',
    'fieldblock-1a9dd8685785a44b58d5e24ed3f8996d' => 'field_flex_page_fca_a',
    'fieldblock-171f57c2269e221c96b732a464bae2e0' => 'field_flex_page_fca_a',
    'fieldblock-9bcf52bbed6b2a3ea84b55a58fdd9c55' => 'field_flex_page_pu',
    'fieldblock-8af3bd2d3cab537c77dbfbb55146ab7b' => 'field_flex_page_hi',
    'fieldblock-05826976d27bc7abbc4f0475ba10cb58' => 'field_flex_page_il_a',
    'fieldblock-21808b5e6c396dac8670f322f5c9e197' => 'field_flex_page_il_b',
    'fieldblock-eab8c417f7d28e9571473905cfebbd5b' => 'field_flex_page_ql',
    'fieldblock-1f11b5247df5b10da980b5681b637d17' => 'field_flex_page_pl',
    'fieldblock-205723da13bdadd816a716421b436a92' => 'field_flex_page_fh',
    'fieldblock-f28dec811f29578f018fae1a8458c9b4' => 'field_flex_page_pca',
    'fieldblock-75a75df6422c87166c75aa079ca98c3c' => 'field_flex_page_resource',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // First, manipulate the D7 context into a usable array.
    $template = $row->getSourceProperty('template');
    $nid = $row->getDestinationProperty('temp_nid');
    $d7_data = self::formatD7Data($value, $template, $nid, $row);

    // Next, put those array elements into D8 section objects.
    $sections = [];
    foreach ($d7_data as $d7_section) {
      $d8_components = [];
      if (!empty($d7_section['components'])) {
        foreach ($d7_section['components'] as $id => $d7_component) {
          $d8_component = self::createD8SectionComponent($d7_component['type'], md5($id), $id, $d7_component['region'], $d7_component['weight']);
          if ($d8_component) {
            $d8_components[] = $d8_component;
          }
        }
        if (!empty($d8_components)) {
          $section = self::createD8Section($d7_section['layout'], $d8_components);
          $sections[] = $section;
        }

      }
    }
    return $sections;
  }

  /**
   * Build the sections that will comprise this page's layout.
   *
   * @param string $template
   *   The D7 template associated with this page.
   */
  protected static function getD8SectionsfromD7Layout($template) {
    $sections = [];
    switch ($template) {
      case 'Featured Highlight':
        $sections[0]['layout'] = 'layout_utexas_50_50';
        $sections[1]['layout'] = 'layout_utexas_fullwidth';
        $sections[2]['layout'] = 'layout_utexas_66_33';
        break;

      case 'Hero Image & Sidebars':
      case 'Header with Content & Sidebars':
        $sections[0]['layout'] = 'layout_utexas_66_33';
        $sections[1]['layout'] = 'layout_utexas_66_33';
        break;

      case 'Full Content Page & Sidebar':
      case 'Promotional Page & Sidebar':
        $sections[0]['layout'] = 'layout_utexas_66_33';
        break;

      case 'Full Width Content Page & Title':
      case 'Full Width Content Page':
      case 'Open Text Page':
        $sections[0]['layout'] = 'layout_utexas_fullwidth';
        $sections[1]['layout'] = 'layout_utexas_fullwidth';
        break;

      case 'Landing Page Template 1':
        $sections[0]['layout'] = 'layout_utexas_fullwidth';
        $sections[1]['layout'] = 'layout_utexas_66_33';
        $sections[2]['layout'] = 'layout_utexas_fullwidth';
        $sections[3]['layout'] = 'layout_utexas_66_33';
        break;

      case 'Landing Page Template 2':
        $sections[0]['layout'] = 'layout_utexas_fullwidth';
        $sections[1]['layout'] = 'layout_utexas_fullwidth';
        $sections[2]['layout'] = 'layout_utexas_fullwidth';
        $sections[3]['layout'] = 'layout_utexas_fullwidth';
        break;

      case 'Landing Page Template 3':
        $sections[0]['layout'] = 'layout_utexas_fullwidth';
        $sections[1]['layout'] = 'layout_utexas_fullwidth';
        $sections[2]['layout'] = 'layout_utexas_fullwidth';
        $sections[3]['layout'] = 'layout_utexas_66_33';
        break;
    }
    return $sections;
  }

  /**
   * Get Drupal 7 layout data into a traversable format.
   *
   * @param string $value
   *   A serialized array of layout data from the "context" table.
   * @param string $template
   *   The D7 template associated with this page.
   * @param int $nid
   *   The destination NID.
   */
  protected static function formatD7Data($value, $template, $nid, $row) {
    // @todo retrieve which D7 layout this is from $row.
    $layout = unserialize($value);
    $blocks = $layout['block']['blocks'];

    // Look up presence of "locked" fields & add them programmatically
    // as blocks, potentially adjusting weight of other blocks.
    $blocks = self::addLockedFieldsAsBlocks($blocks, $template, $nid, $row);

    // Build up the D8 sections based on known information about the D7 layout:
    $sections = self::getD8SectionsfromD7Layout($template);
    foreach ($blocks as $id => $settings) {
      if (in_array($id, array_keys(self::$map))) {
        $d8_field = self::$map[$id];
      }
      elseif (strpos($id, 'fieldblock-') !== 0) {
        // The above eliminates fieldblocks that should not be mapped (e.g., Contact Info).
        // This is not a fieldblock (e.g., Social Links). Just pass the block ID.
        $d8_field = $id;
      }
      $sections = self::placeFieldinSection($sections, $d8_field, $settings, $template);
    }
    return $sections;
  }

  /**
   * Add Drupal 7 "locked" fields to D7 data.
   *
   * @param array $blocks
   *   The D7 block data for this given node.
   * @param string $template
   *   The D7 template associated with this page.
   * @param int $nid
   *   The destination NID.
   */
  protected static function addLockedFieldsAsBlocks(array $blocks, $template, $nid, $row) {
    $node = Node::load($nid);
    if ($social_link_id = $row->getSourceProperty('social_link_id')) {
      // Make a fake D7 block ID that can be identified later on.
      $blocks[$social_link_id] = [
        'region' => 'social_links',
        'weight' => '-1',
      ];
    }
    if ($hi = $node->field_flex_page_hi->getValue()) {
      $region = FALSE;
      switch ($template) {
        case 'Hero Image & Sidebars':
          $region = 'content_top_left';
          $id = 'fieldblock-f4361d99a73eca8a4329c07d0724a554';
          break;

        case 'Promotional Page & Sidebar':
          $region = 'content';
          $id = 'fieldblock-f4361d99a73eca8a4329c07d0724a554';
          break;

        case 'Landing Page Template 1':
        case 'Landing Page Template 2':
        case 'Landing Page Template 3':
          $region = 'hero_image';
          $id = 'fieldblock-8af3bd2d3cab537c77dbfbb55146ab7b';
          break;
      }
      if ($region) {
        // Enforce that hero image is above other content.
        $blocks[$id] = [
          'region' => $region,
          'weight' => '-1',
        ];
      }
    }
    if ($fh = $node->field_flex_page_fh->getValue()) {
      $region = FALSE;
      switch ($template) {
        case 'Featured Highlight':
          $region = 'featured_highlight';
          $id = 'fieldblock-553096d7ea242fc7edcddc53f719d074';
          break;

        case 'Landing Page Template 1':
        case 'Landing Page Template 2':
        case 'Landing Page Template 3':
          $region = 'featured_highlight';
          $id = 'fieldblock-205723da13bdadd816a716421b436a92';
          break;
      }
      if ($region) {
        // Enforce that this locked field is above other content.
        $blocks[$id] = [
          'region' => $region,
          'weight' => '-1',
        ];
      }
    }
    if ($w = $node->field_flex_page_wysiwyg_a->getValue()) {
      $region = FALSE;
      switch ($template) {
        case 'Open Text Page':
          $region = 'content';
          break;
      }
      if ($region) {
        // Enforce that this locked field is above other content.
        $blocks['fieldblock-fda604d130a57f15015895c8268f20d2'] = [
          'region' => $region,
          'weight' => '-1',
        ];
      }
    }
    return $blocks;
  }

  /**
   * Given a D7 field setting & template, place it in the equivalent D8 section.
   *
   * @param array $sections
   *   The sections as defined in the D8 equivalent layout from D7..
   * @param string $d8_field
   *   The machine name of the field.
   * @param array $settings
   *   Field settings, namely region & weight.
   * @param string $template
   *   The D7 template name.
   */
  protected static function placeFieldinSection(array $sections, $d8_field, array $settings, $template) {
    switch ($template) {
      case 'Featured Highlight':
        switch ($settings['region']) {
          case 'main_content_top_left':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'left',
              'weight' => $settings['weight'],
            ];
            break;

          case 'featured_highlight':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'main_content_top_right':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'right',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_bottom':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'social_links':
            $sections[2]['components']['block_content:' . $d8_field] = [
              'type' => 'block_content',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;

          case 'sidebar_second':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;
        }
        break;

      case 'Full Content Page & Sidebar':
      case 'Promotional Page & Sidebar':
        switch ($settings['region']) {
          case 'content':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'sidebar_second':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;
        }
        break;

      case 'Hero Image & Sidebars':
      case 'Header with Content & Sidebars':
        switch ($settings['region']) {
          case 'content_top_left':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_top_right':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_bottom':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'sidebar_second':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;
        }
        break;

      case 'Full Width Content Page & Title':
      case 'Full Width Content Page':
        switch ($settings['region']) {
          case 'content_top':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_bottom':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;
        }
        break;

      case 'Open Text Page';
        switch ($settings['region']) {
          case 'content':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;
        }
        break;

      case 'Landing Page Template 1':
      case 'Landing Page Template 2':
      case 'Landing Page Template 3':
        switch ($settings['region']) {
          case 'hero_image':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_top_three_pillars':
          case 'content_top_four_pillars':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_top_left':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_top_right':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;

          case 'featured_highlight':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'content_bottom':
            $sections[3]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
            ];
            break;

          case 'sidebar_second':
            $sections[3]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
            ];
            break;
        }
        break;

      default:
        break;
    }

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
  protected static function createD8Section($layout, array $components) {
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

      case 'block_content':
        $component = new SectionComponent($uuid, $region, [
          'id' => $id,
          'provider' => 'block_content',
          'context_mapping' => [],
        ]);
        $component->setWeight($weight);
        break;
    }
    if (isset($component)) {
      return $component;
    }
    return FALSE;
  }

}
