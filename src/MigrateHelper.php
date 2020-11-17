<?php

namespace Drupal\utexas_migrate;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\utexas_migrate\CustomWidgets\BasicBlock;
use Drupal\utexas_migrate\CustomWidgets\FlexContentArea;
use Drupal\utexas_migrate\CustomWidgets\FeaturedHighlight;
use Drupal\utexas_migrate\CustomWidgets\Hero;
use Drupal\utexas_migrate\CustomWidgets\ImageLink;
use Drupal\utexas_migrate\CustomWidgets\PhotoContentArea;
use Drupal\utexas_migrate\CustomWidgets\PromoLists;
use Drupal\utexas_migrate\CustomWidgets\PromoUnits;
use Drupal\utexas_migrate\CustomWidgets\QuickLinks;
use Drupal\utexas_migrate\CustomWidgets\Resource;
use Drupal\utexas_migrate\CustomWidgets\SocialLinks;
use Drupal\utexas_migrate\CustomWidgets\ViewsBlock;

/**
 * Helper functions for migration.
 */
class MigrateHelper {

  /**
   * Retrieve a media entity ID for an equivalent D7 file from migration map.
   *
   * @param int $fid
   *   The file ID from the D7 site.
   *
   * @return int
   *   Returns the matching media entity ID imported to the D8 site.
   */
  public static function getMediaIdFromFid($fid) {
    $mid = 0;
    $mid = \Drupal::database()->select('migrate_map_utexas_media_image')
      ->fields('migrate_map_utexas_media_image', ['destid1'])
      ->condition('sourceid1', $fid, '=')
      ->execute()
      ->fetchField();
    // Try the video map.
    if (!$mid) {
      $mid = \Drupal::database()->select('migrate_map_utexas_media_video')
        ->fields('migrate_map_utexas_media_video', ['destid1'])
        ->condition('sourceid1', $fid, '=')
        ->execute()
        ->fetchField();
    }
    return $mid;
  }

  /**
   * Given a source nid, return a destination nid if there is one.
   *
   * @param int $source_nid
   *   The NID from the D7 site.
   *
   * @return int
   *   Returns the matching media entity ID imported to the D8 site.
   */
  public static function getDestinationNid($source_nid) {
    // Each node type migration must be queried individually,
    // since they have no relational shared field for joining.
    $tables_to_query = [
      'migrate_map_utexas_landing_page',
      'migrate_map_utexas_standard_page',
      'migrate_map_utexas_basic_page',
      'migrate_map_utexas_article',
      'migrate_map_utevent_nodes',
      'migrate_map_utprof_nodes',
      'migrate_map_utnews_nodes',
    ];
    $connection = \Drupal::database();
    foreach ($tables_to_query as $table) {
      if ($connection->schema()->tableExists($table)) {
        $destination_nid = \Drupal::database()->select($table, 'n')
          ->fields('n', ['destid1'])
          ->condition('n.sourceid1', $source_nid)
          ->execute()
          ->fetchField();
        if ($destination_nid) {
          return $destination_nid;
        }
      }
    }
    return FALSE;
  }

  /**
   * Given an source text format, return an available format.
   *
   * @param string $text_format
   *   The source format (e.g., 'filtered_html')
   *
   * @return string
   *   The destination format (e.g., 'flex_html')
   *
   * @see /doc/decisions/0003-map-text-formats-for-custom-components.md
   */
  public static function getDestinationTextFormat($text_format) {
    // As much as possible, we want to map the set text formats to their
    // respective D8 equivalents. If a D8 equivalent doesn't exist, fall back
    // to 'flex_html'.
    $destination_text_formats = [
      'flex_html',
      'basic_html',
      'full_html',
      'restricted_html',
      'plain_text',
    ];
    if (in_array($text_format, $destination_text_formats)) {
      return $text_format;
    }
    return 'flex_html';
  }

  /**
   * Given a source path, provide a destination path.
   *
   * @param string $source
   *   A source path, such as `node/4` or `file/2`.
   *
   * @return mixed
   *   A corresponding destination path, such as `node/8` or `media/6` or FALSE.
   */
  public static function getDestinationFromSource($source) {
    if (strpos($source, 'node/') === 0) {
      $source_nid = substr($source, 5);
      $destination_nid = self::getDestinationNid($source_nid);
      if (!$destination_nid) {
        return FALSE;
      }
      return 'node/' . $destination_nid;
    }
    elseif (strpos($source, 'file/') === 0) {
      $source_fid = substr($source, 5);
      $destination_fid = self::getMediaIdFromFid($source_fid);
      if (!$destination_fid) {
        return FALSE;
      }
      return 'media/' . $destination_fid;
    }
    return $source;
  }

  /**
   * Receive a Drupal 7 link & format it for Drupal 8.
   *
   * @param string $link
   *   A link, in string format.
   * @param string $source_path
   *   The source path that referenced this link.
   *
   * @return string
   *   The appropriate link for D8.
   */
  public static function prepareLink($link, $source_path = '') {
    // Check for node/ links.
    // @todo: check for taxonomy/term/, file/, and other internal links (e.g., Views routes)
    if (strpos($link, 'node/') === 0) {
      $source_nid = substr($link, 5);
      if ($destination_nid = self::getDestinationNid($source_nid)) {
        return ('internal:/node/' . $destination_nid);
      }
      // The destination NID doesn't exist. Print a warning message.
      \Drupal::logger('utexas_migrate')->warning('* Source node %source contained link "@link". No equivalent destination node was found. Link replaced with link to homepage.', [
        '@link' => $link,
        '%source' => $source_path,
      ]);
      return 'internal:/';
    }

    // Handle <front>.
    if ($link == '<front>') {
      return 'internal:/';
    }
    if ($link == '<nolink>') {
      return 'internal:##';
    }
    return $link;
  }

  /**
   * Prepare a D7 text format for usage in D8.
   *
   * In D7, we had "Filtered HTML", "Filtered HTML for blocks",
   * and "Full HTML".
   * In D8, we only have "Flex HTML", and the Drupal provided "Restricted HTML"
   * and "Full HTML".
   */
  public static function prepareTextFormat($d7_format) {
    switch ($d7_format) {
      case 'filtered_html':
      case 'filtered_html_for_blocks':
        $d8_format = 'flex_html';
        break;

      case 'full_html':
        $d8_format = 'full_html';
        break;

      default:
        $d8_format = 'flex_html';
        break;
    }

    return $d8_format;
  }

  /**
   * Map of fieldblock IDs that should NOT be migrated right now.
   *
   * @var array
   */
  public static $excludedFieldblocks = [
    'fieldblock-d83c2a95384186e375ab37cbf1430bf5' => 'Landing Page Contact Info',
    'fieldblock-38205d43426b33bd0fe595ff8ca61ffd' => 'Standard Page Contact Info',
    'fieldblock-d41b4a03ee9d7b1084986f74b617921c' => 'Landing Page UT Newsreel',
    'fieldblock-8e85c2c89f0ccf26e9e4d0378250bf17' => 'Standard Page UT Newsreel',
  ];

  /**
   * Map of fieldblock IDs that should be migrated to reusable blocks.
   *
   * @var array
   */
  public static $includedReusableBlocks = [
    'fieldblock-bb03b0e9fbf84510ab65cbb066d872fc' => 'twitter_widget',
    'fieldblock-5e45b57e2023b0d28f5a9dc785ea12fa' => 'twitter_widget',
  ];

  /**
   * Check if Views block can be migrated to inline blocks.
   *
   * @param string $d7_display_id
   *   The source views block ID.
   *
   * @return mixed
   *   The view block identifier or FALSE.
   */
  public static function isSupportedViewsBlock($d7_display_id) {
    // We should only try to create D8 blocks if the modules that provide them
    // have been enabled.
    $moduleHandler = \Drupal::moduleHandler();

    switch ($d7_display_id) {
      case 'views-news-news_with_thumbnails':
      case 'views-news-news_titles_only':
        if ($moduleHandler->moduleExists('utnews_block_type_news_listing')) {
          return $d7_display_id;
        }
        break;
    }
    return FALSE;
  }

  /**
   * Map of fieldblock IDs that SHOULD be migrated right now.
   *
   * @var array
   */
  public static $includedFieldBlocks = [
    'fieldblock-fda604d130a57f15015895c8268f20d2' => 'wysiwyg_a',
    'fieldblock-bf40687156268eaa30437ed84189f13e' => 'wysiwyg_b',
    'fieldblock-9c079efa827f76dea650869c5d2631e6' => 'flex_content_area_a',
    'fieldblock-2c880c8461bc3ce5a6ac19b2e7791346' => 'flex_content_area_b',
    'fieldblock-208a521aa519bc1ed37d8992aeffae83' => 'promo_unit',
    'fieldblock-f4361d99a73eca8a4329c07d0724a554' => 'hero',
    'fieldblock-6986914623a8e5646904aca42f9f452e' => 'image_link_a',
    'fieldblock-738c0498378ce2c32ba571a0a69457dc' => 'image_link_b',
    'fieldblock-669a6a1f32566fa73ea7974696027184' => 'quick_links',
    'fieldblock-c4c10ae36665adf0e722e7e3f4be74d4' => 'promo_list',
    'fieldblock-553096d7ea242fc7edcddc53f719d074' => 'featured_highlight',
    'fieldblock-29dbb1cb2c1033fdddae49c21ad4a9f5' => 'photo_content_area',
    'fieldblock-e01ea87c2dadf3edda4cc61011b33637' => 'resource',
    'fieldblock-6f3b85225f51542463a88e53104f8753' => 'wysiwyg_a',
    'fieldblock-9a6760fa853859ac84ff3a273ab79869' => 'wysiwyg_b',
    'fieldblock-1a9dd8685785a44b58d5e24ed3f8996d' => 'flex_content_area_a',
    'fieldblock-171f57c2269e221c96b732a464bae2e0' => 'flex_content_area_b',
    'fieldblock-9bcf52bbed6b2a3ea84b55a58fdd9c55' => 'promo_unit',
    'fieldblock-8af3bd2d3cab537c77dbfbb55146ab7b' => 'hero',
    'fieldblock-05826976d27bc7abbc4f0475ba10cb58' => 'image_link_a',
    'fieldblock-21808b5e6c396dac8670f322f5c9e197' => 'image_link_b',
    'fieldblock-eab8c417f7d28e9571473905cfebbd5b' => 'quick_links',
    'fieldblock-1f11b5247df5b10da980b5681b637d17' => 'promo_list',
    'fieldblock-205723da13bdadd816a716421b436a92' => 'featured_highlight',
    'fieldblock-f28dec811f29578f018fae1a8458c9b4' => 'photo_content_area',
    'fieldblock-75a75df6422c87166c75aa079ca98c3c' => 'resource',
  ];

  /**
   * Helper method to save the inline block.
   */
  public static function createInlineBlock($component_data) {
    switch ($component_data['field_identifier']) {

      case 'featured_highlight':
        $block_definition = FeaturedHighlight::createBlockDefinition($component_data);
        break;

      case 'flex_content_area_a':
      case 'flex_content_area_b':
        $block_definition = FlexContentArea::createBlockDefinition($component_data);
        break;

      case 'hero':
        $block_definition = Hero::createBlockDefinition($component_data);
        break;

      case 'image_link_a':
      case 'image_link_b':
        $block_definition = ImageLink::createBlockDefinition($component_data);
        break;

      case 'promo_list':
        $block_definition = PromoLists::createBlockDefinition($component_data);
        break;

      case 'promo_unit':
        $block_definition = PromoUnits::createBlockDefinition($component_data);
        break;

      case 'photo_content_area':
        $block_definition = PhotoContentArea::createBlockDefinition($component_data);
        break;

      case 'quick_links':
        $block_definition = QuickLinks::createBlockDefinition($component_data);
        break;

      case 'resource':
        $block_definition = Resource::createBlockDefinition($component_data);
        break;

      case 'social_links':
        $block_definition = SocialLinks::createBlockDefinition($component_data);
        break;

      case 'wysiwyg_a':
      case 'wysiwyg_b':
        $block_definition = BasicBlock::createBlockDefinition($component_data);
        break;

      case 'views-news-news_with_thumbnails':
      case 'views-news-news_titles_only':
        $block_definition = ViewsBlock::createBlockDefinition($component_data);
        break;
    }
    if (!isset($block_definition)) {
      return FALSE;
    }
    // For each block type to migrate, add a callback like the one above.
    try {
      $block = BlockContent::create($block_definition);
      $block->save();
      return $block;
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('utexas_migrate')->warning("Import of :block_type failed: :error - Code: :code", [
        ':block_type' => $component_data['block_type'],
        ':error' => $e->getMessage(),
        ':code' => $e->getCode(),
      ]);
    }
  }

}
