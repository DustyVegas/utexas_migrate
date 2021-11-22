<?php

namespace Drupal\utexas_migrate\Plugin\migrate\process;

use Drupal\block_content\Entity\BlockContent;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\utexas_migrate\CustomWidgets\BackgroundAccent;
use Drupal\utexas_migrate\CustomWidgets\BasicBlock;
use Drupal\utexas_migrate\CustomWidgets\EntityReference;
use Drupal\utexas_migrate\CustomWidgets\FlexContentArea;
use Drupal\utexas_migrate\CustomWidgets\FeaturedHighlight;
use Drupal\utexas_migrate\CustomWidgets\Hero;
use Drupal\utexas_migrate\CustomWidgets\ImageLink;
use Drupal\utexas_migrate\CustomWidgets\MenuBlock;
use Drupal\utexas_migrate\CustomWidgets\Newsreel;
use Drupal\utexas_migrate\CustomWidgets\PhotoContentArea;
use Drupal\utexas_migrate\CustomWidgets\PromoLists;
use Drupal\utexas_migrate\CustomWidgets\PromoUnits;
use Drupal\utexas_migrate\CustomWidgets\QuickLinks;
use Drupal\utexas_migrate\CustomWidgets\Resource;
use Drupal\utexas_migrate\CustomWidgets\SocialLinks;
use Drupal\utexas_migrate\CustomWidgets\ViewsBlock;
use Drupal\utexas_migrate\MigrateHelper;

/**
 * Layouts Processor.
 *
 * This plugin takes care of processing a D7 "Page Layout"
 * into something consumable buy D8 "Layout Builder".
 *
 * @MigrateProcessPlugin(
 *   id = "utexas_process_layout"
 * )
 * @see doc/decisions/0004-migrate-standard-page-and-landing-page-to-flex-page.md
 */
class Layouts extends ProcessPluginBase {

  /**
   * The main function.
   *
   * Given a row that contains a destination node ID and
   * the source context for all of the D7 fields, build an array of inline data,
   * organized by Drupal 8 section components, then save that to the node's
   * layout (node__layout_builder__layout).
   */
  public function transform($layout, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // 1. Get the template name (e.g., "Featured Highlight")
    // and the destination ID (e.g., "1")
    $template = $row->getSourceProperty('template');
    $nid = $row->getDestinationProperty('temp_nid');

    // This contains all inline field data as well as layout structure,
    // in a single array.
    $section_data = self::buildSectionsArray($layout, $template, $nid, $row);
    // @breakpoint recommendation.
    // 2. Put those array elements into D8 section objects.
    $sections = [];
    foreach ($section_data as $section) {
      $d8_components = [];
      if (!empty($section['components'])) {
        foreach ($section['components'] as $component) {
          $d8_component = self::createD8SectionComponent($component);
          if (is_array($d8_component)) {
            foreach ($d8_component as $c) {
              $d8_components[] = $c;
            }
          }
        }
        if (!empty($d8_components) && !empty($section['layout'])) {
          if (!isset($section['layoutSettings'])) {
            $section['layoutSettings'] = [];
          }
          $section = self::createD8Section($section['layout'], $section['layoutSettings'], $d8_components);
          $sections[] = $section;
        }

      }
    }
    return $sections;
  }

  /**
   * Get layout data into a traversable format.
   *
   * @param string $layout
   *   A serialized array of layout data from the "context" table.
   * @param string $template
   *   The D7 template associated with this page.
   * @param int $nid
   *   The destination NID.
   * @param Drupal\migrate\Row $row
   *   Other entity source data related to this specific entity migration.
   */
  protected static function buildSectionsArray($layout, $template, $nid, Row $row) {
    $layout_data = unserialize($layout);
    // Extract the blocks in the layout from the 'context' table.
    $blocks = $layout_data['block']['blocks'];
    if (!$blocks) {
      return [];
    }
    // Look up presence of "locked" fields & add them programmatically
    // as blocks, potentially adjusting weight of other blocks.
    $blocks = self::addLockedFieldsAsBlocks($blocks, $template, $nid, $row);

    // Build up the D8 sections based on known information about the D7 layout:
    $sections = self::getD8SectionsfromD7Layout($template, $nid, $row);

    // Loop through all known blocks, building the D8 section components.
    foreach ($blocks as $id => $settings) {
      $found = FALSE;
      if (in_array($id, array_keys(MigrateHelper::$includedFieldBlocks))) {
        $field_name = MigrateHelper::$includedFieldBlocks[$id];
        $found = TRUE;
      }
      elseif (in_array($id, array_keys(MigrateHelper::$includedReusableBlocks))) {
        $field_name = MigrateHelper::$includedReusableBlocks[$id];
        // This will provide `twitter_widget` or `contact_info` or `social_sharing`.
        $found = TRUE;
      }
      elseif ($field_name = MigrateHelper::isSupportedViewsBlock($id)) {
        // Sets field name like `views-news-news_with_thumbnails`.
        $found = TRUE;
      }
      elseif ($field_name = MigrateHelper::isMenuBlock($id)) {
        // Sets field to `menu_block`.
        $found = TRUE;
      }
      elseif ($field_name = MigrateHelper::isBasicBlock($id)) {
        // Sets field to `basic_block-<id>`.
        $found = TRUE;
      }
      elseif ($settings['region'] == 'social_links') {
        $field_name = 'social_links';
        $found = TRUE;
      }
      if ($found) {
        // Now that we know we have a field, check for a D7 display setting,
        // and if so, pass an equivalent view_mode to the D8 field formatter.
        $field_data = self::retrieveFieldData($field_name, $row);
        $sections = self::placeFieldinSection($sections, $field_data, $settings, $template);
      }
    }
    return $sections;
  }

  /**
   * Get Drupal 7 layout data into a traversable format.
   *
   * @param string $field_name
   *   The Drupal 8 field name (e.g., field_flex_page_fh).
   * @param Drupal\migrate\Row $row
   *   Other entity source data related to this specific entity migration.
   */
  protected static function retrieveFieldData($field_name, Row $row) {
    $nid = $row->getSourceProperty('nid');
    // Below, component types can define any block-level formatters that should
    // be applied.
    $formatter = [
      'label' => 'hidden',
    ];
    switch ($field_name) {

      case 'featured_highlight':
        $block_type = 'utexas_featured_highlight';
        $source = FeaturedHighlight::getFromNid($nid);
        break;

      case 'flex_content_area_a':
      case 'flex_content_area_b':
        $block_type = 'utexas_flex_content_area';
        $source = FlexContentArea::getFromNid($field_name, $nid);
        break;

      case 'hero':
        $block_type = 'utexas_hero';
        $source = Hero::getFromNid('hero_photo', $nid);
        break;

      case 'image_link_a':
      case 'image_link_b':
        $block_type = 'utexas_image_link';
        $source = ImageLink::getFromNid($field_name, $nid);
        break;

      case 'newsreel':
        $source = Newsreel::getFromNid($nid);
        $block_type = 'feed_block';
        break;

      case 'photo_content_area':
        $block_type = 'utexas_photo_content_area';
        $source = PhotoContentArea::getFromNid($nid);
        break;

      case 'promo_unit':
        $block_type = 'utexas_promo_unit';
        $source = PromoUnits::getFromNid($nid);
        break;

      case 'promo_list':
        $block_type = 'utexas_promo_list';
        $source = PromoLists::getFromNid($nid);
        break;

      case 'quick_links':
        $block_type = 'utexas_quick_links';
        $source = QuickLinks::getFromNid($nid);
        break;

      case 'resource':
        $source = Resource::getFromNid($nid);
        $block_type = 'utexas_resources';
        break;

      case 'social_links':
        $source = SocialLinks::getFromNid($nid);
        $block_type = 'social_links';
        break;

      case 'social_sharing':
        $source = [];
        $block_type = 'addtoany_block';
        break;

      case 'wysiwyg_a':
      case 'wysiwyg_b':
        $block_type = 'basic';
        $source = BasicBlock::getFromNid($field_name, $nid);
        break;

      case 'twitter_widget':
        $block_type = $field_name;
        $source = EntityReference::getFromNid('utexas_' . $field_name, $nid);
        break;

      case 'contact_info':
        $block_type = $field_name;
        $source = EntityReference::getFromNid('utexas_' . $field_name, $nid);
        break;

      case 'views-news-news_with_thumbnails':
      case 'views-news-news_titles_only':
      case 'views-events-block_1':
      case 'views-events-block_2':
      case 'views-events-block_3':
      case 'views-events-block_4':
      case 'views-team_members-block_1':
        $source = ViewsBlock::getBlockData($field_name);
        $block_type = $source['block_type'];
        break;
    }
    // Menu block IDs are incremental, so we handle them outside the
    // main switch statement.
    if (MigrateHelper::isMenuBlock($field_name)) {
      $block_type = 'menu_block';
      $source = MenuBlock::getBlockData($field_name);
    }
    // Basic block IDs are incremental, so we handle them outside the
    // main switch statement.
    if (MigrateHelper::isBasicBlock($field_name)) {
      $block_type = 'basic_reusable';
      $source = BasicBlock::getFromBid($field_name);
    }

    return [
      'field_name' => $field_name,
      'block_type' => $block_type,
      'data' => $source,
      'format' => $formatter,
    ];
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
    // Check if a social link exists on the source node.
    if ($social_link = SocialLinks::getRawSourceData($row->getSourceProperty('nid'))) {
      // Make a fake D7 block ID that can be identified later on.
      $blocks['inline_social_links'] = [
        'type' => 'social_links',
        'region' => 'social_links',
        'weight' => '-1',
      ];
    }
    if ($hi = Hero::getRawSourceData('hero_photo', $row->getSourceProperty('nid'))) {
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

    if ($fh = FeaturedHighlight::getRawSourceData($row->getSourceProperty('nid'))) {
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
        // Enforce that this locked field is above other content,
        // and is above Quick Links, if present.
        $blocks[$id] = [
          'region' => $region,
          'weight' => '-2',
        ];
      }
    }

    if ($ql = QuickLinks::getRawSourceData($row->getSourceProperty('nid'))) {
      $region = FALSE;
      switch ($template) {
        case 'Landing Page Template 2':
          $region = 'quick_links';
          $id = 'fieldblock-669a6a1f32566fa73ea7974696027184';
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

    if ($w = BasicBlock::getFromNid('wysiwyg_a', $nid)) {
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
   * @param int $nid
   *   The destination NID.
   * @param Drupal\migrate\Row $row
   *   Other entity source data related to this specific entity migration.
   */
  protected static function getD8SectionsfromD7Layout($template, $nid, Row $row) {
    $sections = [];
    $onecol_readable_width = [
      'layout' => 'layout_utexas_onecol',
      'layoutSettings' => [
        'section_width' => 'readable',
      ],
    ];
    $onecol_container_width = [
      'layout' => 'layout_utexas_onecol',
      'layoutSettings' => [
        'section_width' => 'container',
      ],
    ];
    $onecol_full_width = [
      'layout' => 'layout_utexas_onecol',
      'layoutSettings' => [
        'layout_builder_styles_style' => [
          'utexas_no_padding',
        ],
        'section_width' => 'container-fluid',
      ],
    ];
    $fifty_fifty = [
      'layout' => 'layout_utexas_twocol',
      'layoutSettings' => [
        'column_widths' => '50-50',
      ],
    ];
    $sixty_six_thirty_three = [
      'layout' => 'layout_utexas_twocol',
      'layoutSettings' => [
        'column_widths' => '67-33',
      ],
    ];
    switch ($template) {
      case 'Featured Highlight':
        $sections[0] = $fifty_fifty;
        $sections[1] = $onecol_full_width;
        $sections[2] = $sixty_six_thirty_three;
        break;

      case 'Hero Image & Sidebars':
      case 'Header with Content & Sidebars':
        $sections[0] = $sixty_six_thirty_three;
        $sections[1] = $sixty_six_thirty_three;
        break;

      case 'Full Content Page & Sidebar':
      case 'Promotional Page & Sidebar':
        $sections[0] = $sixty_six_thirty_three;
        break;

      case 'Full Width Content Page & Title':
      case 'Full Width Content Page':
      case 'Open Text Page':
        $sections[0] = $onecol_readable_width;
        $sections[1] = $onecol_readable_width;
        break;

      case 'Landing Page Template 1':
        // First section is always hero photo.
        $sections[0] = $onecol_full_width;
        $sections[1] = $sixty_six_thirty_three;
        // Third section is always Featured Highlight.
        $sections[2] = $onecol_full_width;
        $sections[3] = $sixty_six_thirty_three;
        break;

      case 'Landing Page Template 2':
        // First section is always hero photo.
        $sections[0] = $onecol_full_width;
        $sections[1] = $onecol_container_width;
        // Third section is always Featured Highlight + Quick Links.
        $sections[2] = $onecol_full_width;
        $sections[3] = $onecol_container_width;
        break;

      case 'Landing Page Template 3':
        // First section is always hero photo.
        $sections[0] = $onecol_full_width;
        $sections[1] = $onecol_container_width;
        // Third section is always Featured Highlight.
        $sections[2] = $onecol_full_width;
        $sections[3] = $sixty_six_thirty_three;
        break;
    }

    switch ($template) {
      case 'Landing Page Template 1':
      case 'Landing Page Template 2':
      case 'Landing Page Template 3':
        // Add a background accent, if present.
        $background_accent = BackgroundAccent::getFromNid($row->getSourceProperty('nid'));
        if (!empty($background_accent)) {
          $sections[2]['layoutSettings']['blur'] = $background_accent['blur'];
          $sections[2]['layoutSettings']['background-accent'] = $background_accent['image'];
        }
        break;
    }
    return $sections;
  }

  /**
   * Given a D7 field setting & template, place it in the equivalent D8 section.
   *
   * @param array $sections
   *   The sections as defined in the D8 equivalent layout from D7..
   * @param string $field_data
   *   The field data.
   * @param array $settings
   *   Field settings, namely region & weight.
   * @param string $template
   *   The D7 template name.
   */
  protected static function placeFieldinSection(array $sections, $field_data, array $settings, $template) {
    // In v2, Standard page sidebar regions apply a border w/ background style
    // to blocks.
    $layout_override = [];
    $layout_builder_styles = [];
    $three_col = [
      'layout_builder_styles_style' => [
        'utexas_threecol' => 'utexas_threecol',
      ],
    ];
    $four_col = [
      'layout_builder_styles_style' => [
        'utexas_fourcol' => 'utexas_fourcol',
      ],
    ];
    $field = $field_data['field_name'];
    switch ($template) {
      case 'Featured Highlight':
        switch ($settings['region']) {
          case 'main_content_top_left':
            $delta = 0;
            $region = 'first';
            break;

          case 'main_content_top_right':
            $delta = 0;
            $region = 'second';
            break;

          case 'featured_highlight':
            $delta = 1;
            $region = 'main';
            break;

          case 'content_bottom':
            $delta = 2;
            $region = 'first';
            break;

          case 'social_links':
            $delta = 2;
            $region = 'second';
            break;

          case 'sidebar_second':
            $delta = 2;
            $region = 'second';
            break;
        }
        break;

      case 'Full Content Page & Sidebar':
      case 'Promotional Page & Sidebar':
        switch ($settings['region']) {
          case 'content':
            $delta = 0;
            $region = 'first';
            break;

          case 'sidebar_second':
            $delta = 0;
            $region = 'second';
            break;
        }
        break;

      case 'Hero Image & Sidebars':
      case 'Header with Content & Sidebars':
        switch ($settings['region']) {
          case 'content_top_left':
            $delta = 0;
            $region = 'first';
            break;

          case 'content_top_right':
            $delta = 0;
            $region = 'second';
            break;

          case 'content_bottom':
            $delta = 1;
            $region = 'first';
            break;

          case 'content':
            $delta = 1;
            $region = 'first';
            break;

          case 'sidebar_second':
            $delta = 1;
            $region = 'second';
            break;
        }
        break;

      case 'Full Width Content Page & Title':
      case 'Full Width Content Page':
        switch ($settings['region']) {
          case 'content_top':
            $delta = 0;
            $region = 'main';
            break;

          case 'content_bottom':
            $delta = 1;
            $region = 'main';
            break;
        }
        break;

      case 'Open Text Page';
        switch ($settings['region']) {
          case 'content':
            $delta = 0;
            $region = 'main';
            break;
        }
        break;

      case 'Landing Page Template 1':
        switch ($settings['region']) {
          case 'hero_image':
            $delta = 0;
            $region = 'main';
            break;

          case 'content_top_left':
            $delta = 1;
            $region = 'first';
            break;

          case 'content_top_right':
            $delta = 1;
            $region = 'second';
            break;

          case 'featured_highlight':
            $delta = 2;
            $region = 'main';
            break;

          case 'content_bottom':
            $delta = 3;
            $region = 'first';
            break;

          case 'sidebar_second':
            $delta = 3;
            $region = 'second';
            break;
        }
        break;

      case 'Landing Page Template 2':
      case 'Landing Page Template 3':
        switch ($settings['region']) {
          case 'hero_image':
            $delta = 0;
            $region = 'main';
            break;

          case 'content_top_three_pillars':
            if ($template === 'Landing Page Template 2') {
              if (in_array($field, ['flex_content_area_a', 'flex_content_area_b'])) {
                // Special case: FCA in content_top_three_pillars is 3-columns.
                $layout_builder_styles = $three_col;
              }
              $delta = 1;
              $region = 'main';
            }
            break;

          case 'content_top_four_pillars':
            if ($template === 'Landing Page Template 3') {
              if (in_array($field, ['flex_content_area_a', 'flex_content_area_b'])) {
                // Special case: FCA in content_top_four_pillars 4-columns.
                $layout_builder_styles = $four_col;
              }
              $delta = 1;
              $region = 'main';
            }
            break;

          case 'featured_highlight':
            $delta = 2;
            $region = 'main';
            break;

          case 'quick_links':
            $delta = 2;
            $region = 'main';
            break;

          case 'content_bottom':
            $delta = 3;
            $region = $template == "Landing Page Template 2" ? 'main' : 'first';
            break;

          case 'sidebar_second':
            $delta = 3;
            $region = 'second';
            break;
        }
        break;

      default:
        break;
    }
    // Above, *sections* have had a chance to set *block*-level Layout Builder
    // Styles (to preserve v2 Layout theming).
    // Next, check if *blocks* specify any Layout Builder Styles & add it.
    if (in_array($field, ['flex_content_area_a', 'flex_content_area_b']) && in_array($template, ['Landing Page Template 2', 'Landing Page Template 3'])) {
      // Do not allow field defaults to override the layout behavior.
    }
    if (in_array($field, ['quick_links']) && in_array($settings['region'], ['content_top_four_pillars'])) {
      // Do not allow field defaults to override the layout behavior.
    }
    elseif ($field_data['data']['additional']['layout_builder_styles_style']) {
      // Append field-specific styles (e.g., Promo Unit Limit to 1 item per row)
      // into those specified by the v2 section design (e.g., in v2,
      // all items placed in Standard Page sidebar second received a border
      // w/ background).
      foreach ((array) $field_data['data']['additional']['layout_builder_styles_style'] as $key => $value) {
        $layout_builder_styles['layout_builder_styles_style'][$key] = $value;
      }
    }
    // Get possible view mode specifications from field info (only Promo List)
    if (isset($field_data['data']['view_mode'])) {
      $view_mode = $field_data['data']['view_mode'];
      unset($field_data['data']['view_mode']);
    }
    if (in_array($field_data['data'][0]['view_mode'], ['utexas_hero_4', 'utexas_hero_5'])) {
      $sections[$delta]['layoutSettings']['section_width'] = 'container';
    }

    if ($region) {
      $sections[$delta]['components'][$field] = [
        'field_identifier' => $field,
        'block_data' => $field_data['data'],
        'block_type' => $field_data['block_type'],
        'block_format' => $field_data['format'],
        'region' => $region,
        'additional' => $layout_builder_styles,
        'weight' => $settings['weight'],
        'view_mode' => $view_mode,
      ];
    }
    return $sections;
  }

  /**
   * Helper function to create a section.
   *
   * @param string $layout
   *   The D8 machine name of the layout to be used.
   * @param array $layout_settings
   *   Any layout-level settings (full width, percentages, etc.).
   * @param array $components
   *   An array of sectionComponents (i.e., fields)
   */
  protected static function createD8Section($layout, array $layout_settings, array $components) {
    // Each section is stored in its own array.
    $section = new Section($layout, $layout_settings, $components);
    return $section;
  }

  /**
   * Helper method to take field data & create a SectionComponent object.
   *
   * @param array $component_data
   *   The data/context of the component (e.g., region, weight, view_mode)
   *
   * @return mixed
   *   The component object or FALSE.
   */
  protected static function createD8SectionComponent(array $component_data) {
    // Add view mode fallback after layout regions have had a chance to set it.
    $component_view_mode = $component_data['block_data'][0]['view_mode'] ?? 'full';
    // Switch creation between inline & reusable block components.
    switch ($component_data['block_type']) {
      case 'addtoany_block':
        $component = new SectionComponent(md5($component_data['field_identifier']), $component_data['region'], [
          'id' => 'addtoany_block',
          'label' => 'Share this content',
          'provider' => 'addtoany',
          'label_display' => 'visible',
          'status' => TRUE,
          'view_mode' => '',
        ]);
        break;

      case 'twitter_widget':
      case 'contact_info':
      case 'basic_reusable':
        $bid = EntityReference::getDestinationBlockId($component_data);
        $uuid = EntityReference::getDestinationUuid($bid);
        if (!$uuid) {
          return FALSE;
        }
        $block = BlockContent::load($bid);
        $label = $component_data['field_identifier'];
        if ($block) {
          $label = $block->label();
        }
        if ($component_data['block_type'] === 'contact_info') {
          $label = EntityReference::getContactInfoName($component_data['block_data'][0]['target_id']) ?? $label;
        }
        if ($component_data['block_type'] === 'basic_reusable') {
          $label = BasicBlock::getBlockTitle($component_data['block_data'][0]['target_id']) ?? $label;
        }
        $component = new SectionComponent(md5($component_data['field_identifier']), $component_data['region'], [
          'id' => 'block_content:' . $uuid,
          'label' => $label,
          'provider' => 'layout_builder',
          'label_display' => $component_data['block_data']['display_title'],
          'status' => TRUE,
          'view_mode' => $component_data['view_mode'] ?? $component_view_mode,
        ]);
        break;

      case 'menu_block':
        $component = new SectionComponent(md5($component_data['field_identifier']), $component_data['region'], [
          'id' => $component_data['block_data']['menu_block_id'],
          'label' => $component_data['block_data']['admin_title'],
          'provider' => 'menu_block',
          'label_display' => $component_data['block_data']['display_title'],
          'follow' => $component_data['block_data']['follow'],
          'follow_parent' => $component_data['block_data']['follow_parent'],
          'child' => '',
          'label_type' => $component_data['block_data']['label_type'],
          'label_link' => $component_data['block_data']['label_link'],
          'level' => $component_data['block_data']['level'],
          'depth' => $component_data['block_data']['depth'],
          'expand_all_items' => $component_data['block_data']['expanded'],
          'parent' => $component_data['block_data']['parent'],
          'status' => TRUE,
        ]);
        break;

      case 'utprof_profile_listing':
        $components = [];
        // Team Member block migrates from one to many blocks, so we need to
        // create multiple section components.
        foreach ($component_data['block_data']['blocks'] as $i) {
          $additional = [];
          if ($i['field_utprof_view_mode'] === 'node.utexas_prominent') {
            $additional = [
              'layout_builder_styles_style' => [
                'utexas_onecol' => 'utexas_onecol',
              ],
            ];
          }
          $block = MigrateHelper::createInlineBlock($i);
          // Important: 'id' value must be "inline_block:" + valid block type.
          $component = new SectionComponent(md5($i['field_identifier'] . $i['title']), $component_data['region'], [
            'id' => 'inline_block:' . $i['block_type'],
            'label' => $i['title'] ?? $i['field_identifier'],
            'provider' => 'layout_builder',
            'label_display' => 'visible',
            'block_revision_id' => $block->id(),
          ]);
          if ($component) {
            $component->setWeight($component_data['weight']);
            $component->set('additional', $additional);
            $components[] = $component;
          }
        }
        return $components;

      default:
        // All other block types are migrated as inline blocks.
        $block = MigrateHelper::createInlineBlock($component_data);
        if ($block) {
          // Important: the 'id' value must be "inline_block:" + valid block type.
          $component = new SectionComponent(md5($component_data['field_identifier'] . $component_data['block_data']['title']), $component_data['region'], [
            'id' => 'inline_block:' . $component_data['block_type'],
            'label' => $component_data['block_data']['title'] ?? $component_data['field_identifier'],
            'provider' => 'layout_builder',
            'label_display' => $component_data['block_data']['label'] ?? 0,
            'view_mode' => $component_data['view_mode'] ?? $component_view_mode,
            'block_revision_id' => $block->id(),
          ]);
        }
        break;
    }

    if ($component) {
      $component->setWeight($component_data['weight']);

      // Add additional component styles, like Layout Builder Styles settings.
      // Ensure data type of array passed to component's `additional` element.
      $existing_additional = is_array($component_data['additional']) ? $component_data['additional'] : [];
      $component->set('additional', $existing_additional);
      return [$component];
    }
    return FALSE;
  }

}
