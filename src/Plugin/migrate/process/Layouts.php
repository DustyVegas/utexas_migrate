<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\Entity\Node;
use Drupal\utexas_migrate\CustomWidgets\FeaturedHighlight;
use Drupal\utexas_migrate\CustomWidgets\PromoLists;
use Drupal\utexas_migrate\CustomWidgets\PromoUnits;

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

  protected static $excludedFieldblocks = [
    'fieldblock-bb03b0e9fbf84510ab65cbb066d872fc' => 'Standard Page Twitter Widget',
    'fieldblock-bb03b0e9fbf84510ab65cbb066d872fc' => 'Landing Page Twitter Widget',
    'fieldblock-d83c2a95384186e375ab37cbf1430bf5' => 'Landing Page Contact Info',
    'fieldblock-38205d43426b33bd0fe595ff8ca61ffd' => 'Standard Page Contact Info',
    'fieldblock-d41b4a03ee9d7b1084986f74b617921c' => 'Landing Page UT Newsreel',
    'fieldblock-8e85c2c89f0ccf26e9e4d0378250bf17' => 'Standard Page UT Newsreel',
  ];

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
          $d8_component = self::createD8SectionComponent($d7_component['type'], md5($id), $id, $d7_component['region'], $d7_component['weight'], $d7_component['formatter']);
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
   * Get Drupal 7 layout data into a traversable format.
   *
   * @param string $value
   *   A serialized array of layout data from the "context" table.
   * @param string $template
   *   The D7 template associated with this page.
   * @param int $nid
   *   The destination NID.
   * @param Drupal\migrate\Row $row
   *   Other entity source data related to this specific entity migration.
   */
  protected static function formatD7Data($value, $template, $nid, Row $row) {
    // @todo retrieve which D7 layout this is from $row.
    $layout = unserialize($value);
    $blocks = $layout['block']['blocks'];
    if (!$blocks) {
      return [];
    }
    // Look up presence of "locked" fields & add them programmatically
    // as blocks, potentially adjusting weight of other blocks.
    $blocks = self::addLockedFieldsAsBlocks($blocks, $template, $nid, $row);

    // Build up the D8 sections based on known information about the D7 layout:
    $sections = self::getD8SectionsfromD7Layout($template);
    foreach ($blocks as $id => $settings) {
      $found = FALSE;
      if (in_array($id, array_keys(self::$excludedFieldblocks))) {
        // Skip "excluded" fieldblocks, like Twitter Widget, Contact Info,
        // since UTDK8 doesn't currently have a location for these.
        continue;
      }
      elseif (in_array($id, array_keys(self::$map))) {
        $d8_field = self::$map[$id];
        $found = TRUE;
      }
      elseif ($settings['region'] == 'social_links') {
        // The above eliminates fieldblocks not yet converted to UUIDs.
        // @todo: look up standard blocks' block UUIDs in FlexPageLayoutsSource.php
        // This code may need to be refactored to further disambiguate.
        // This is not a fieldblock (e.g., Social Links). Use the block ID.
        $d8_field = $id;
        $found = TRUE;
      }
      if ($found) {
        // Now that we know we have a field, check for a D7 display setting,
        // and if so, pass an equivalent view_mode to the D8 field formatter.
        $formatter = self::retrieveFieldDisplaySetting($d8_field, $row);

        $sections = self::placeFieldinSection($sections, $d8_field, $settings, $template, $formatter);
      }
    }
    return $sections;
  }

  /**
   * Get Drupal 7 layout data into a traversable format.
   *
   * @param string $d8_field
   *   The Drupal 8 field name (e.g., field_flex_page_fh).
   * @param Drupal\migrate\Row $row
   *   Other entity source data related to this specific entity migration.
   */
  protected static function retrieveFieldDisplaySetting($d8_field, Row $row) {
    $nid = $row->getSourceProperty('nid');
    $formatter = [];
    switch ($d8_field) {
      case 'field_flex_page_fh':
        $style_map = [
          'light' => 'default',
          'navy' => 'utexas_featured_highlight_2',
          'dark' => 'utexas_featured_highlight_3',
        ];
        $source = FeaturedHighlight::getSourceData($nid);
        if (!empty($source[0]['style'])) {
          $style = $source[0]['style'];
          $formatter = [
            'label' => 'hidden',
            'type' => $style_map[$style],
          ];
        }
        break;

      case 'field_flex_page_pu':
        $style_map = [
          'utexas_promo_unit_landscape_image' => 'default',
          'utexas_promo_unit_portrait_image' => 'utexas_promo_unit_2',
          'utexas_promo_unit_square_image' => 'utexas_promo_unit_3',
          'utexas_promo_unit_no_image' => 'default',
        ];
        $source = PromoUnits::getSourceData($nid);
        if (!empty($source[0]['size_option'])) {
          $style = $source[0]['size_option'];
          $formatter = [
            'label' => 'hidden',
            'type' => $style_map[$style],
          ];
        }
        break;

      case 'field_flex_page_pl':
        $style_map = [
          'Single list full (1 item per row)' => 'default',
          'Single list responsive (2 items per row)' => 'utexas_promo_list_2',
          'Two lists, side-by-side' => 'utexas_promo_list_3',
        ];
        $source = PromoLists::getSourceData($nid);
        if (!empty($source[0]['style'])) {
          $style = $source[0]['style'];
          $formatter = [
            'label' => 'hidden',
            'type' => $style_map[$style],
          ];
        }
        break;

    }
    return $formatter;
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
   * @param Drupal\migrate\Row $row
   *   Other entity source data related to this specific entity migration.
   */
  protected static function addLockedFieldsAsBlocks(array $blocks, $template, $nid, Row $row) {
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
   * @param string[] $formatter
   *   The field view mode, if defined.
   */
  protected static function placeFieldinSection(array $sections, $d8_field, array $settings, $template, array $formatter) {
    switch ($template) {
      case 'Featured Highlight':
        switch ($settings['region']) {
          case 'main_content_top_left':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'left',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'featured_highlight':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'main_content_top_right':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'right',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_bottom':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'social_links':
            $sections[2]['components']['block_content:' . $d8_field] = [
              'type' => 'block_content',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'sidebar_second':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
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
              'formatter' => $formatter,
            ];
            break;

          case 'sidebar_second':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
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
              'formatter' => $formatter,
            ];
            break;

          case 'content_top_right':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_bottom':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'sidebar_second':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
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
              'formatter' => $formatter,
            ];
            break;

          case 'content_bottom':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
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
              'formatter' => $formatter,
            ];
            break;
        }
        break;

      case 'Landing Page Template 1':
        switch ($settings['region']) {
          case 'hero_image':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_top_left':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_top_right':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'featured_highlight':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_bottom':
            $sections[3]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'sidebar_second':
            $sections[3]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;
        }
        break;

      case 'Landing Page Template 2':
      case 'Landing Page Template 3':
        switch ($settings['region']) {
          case 'hero_image':
            $sections[0]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_top_three_pillars':
          case 'content_top_four_pillars':
            $sections[1]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'featured_highlight':
            $sections[2]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'content_bottom':
            $sections[3]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'main',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
            ];
            break;

          case 'sidebar_second':
            $sections[3]['components']['field_block:node:utexas_flex_page:' . $d8_field] = [
              'type' => 'field_block',
              'region' => 'sidebar',
              'weight' => $settings['weight'],
              'formatter' => $formatter,
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
   * @param string[] $formatter
   *   The view mode that the field uses, if any.
   */
  protected function createD8SectionComponent($type, $uuid, $id, $region, $weight, array $formatter) {
    switch ($type) {
      case 'field_block':
        $component = new SectionComponent($uuid, $region, [
          'id' => $id,
          'formatter' => $formatter,
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
