<?php

namespace Drupal\utexas_migrate;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\utexas_migrate\CustomWidgets\BasicBlock;
use Drupal\utexas_migrate\CustomWidgets\FlexContentArea;
use Drupal\utexas_migrate\CustomWidgets\FeaturedHighlight;
use Drupal\utexas_migrate\CustomWidgets\Hero;
use Drupal\utexas_migrate\CustomWidgets\ImageLink;
use Drupal\utexas_migrate\CustomWidgets\Newsreel;
use Drupal\utexas_migrate\CustomWidgets\PhotoContentArea;
use Drupal\utexas_migrate\CustomWidgets\PromoLists;
use Drupal\utexas_migrate\CustomWidgets\PromoUnits;
use Drupal\utexas_migrate\CustomWidgets\QuickLinks;
use Drupal\utexas_migrate\CustomWidgets\Resource;
use Drupal\utexas_migrate\CustomWidgets\SocialLinks;
use Drupal\utexas_migrate\CustomWidgets\ViewsBlock;
use Drupal\Core\Url;

/**
 * Helper functions for migration.
 */
class MigrateHelper {

  /**
   * Helper function for DB queries.
   *
   * @return array
   *   The unserialized value.
   */
  public static function getVariable($name) {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    $query = $source_db->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('name', $name, '=')
      ->execute()
      ->fetch();
    if (empty($query->value)) {
      return NULL;
    }
    return unserialize($query->value);
  }

  /**
   * Retrieve a media entity ID for an equivalent D7 file from migration map.
   *
   * @param int $fid
   *   The file ID from the D7 site.
   *
   * @return mixed
   *   Returns the matching media entity ID or FALSE.
   */
  public static function getDestinationMid($fid) {
    $destination_db = Database::getConnection('default', 'default');
    $mid = $destination_db->select('migrate_map_utexas_media_image')
      ->fields('migrate_map_utexas_media_image', ['destid1'])
      ->condition('sourceid1', $fid, '=')
      ->execute()
      ->fetchField();
    // Try the video map.
    if (!$mid) {
      $mid = $destination_db->select('migrate_map_utexas_media_video')
        ->fields('migrate_map_utexas_media_video', ['destid1'])
        ->condition('sourceid1', $fid, '=')
        ->execute()
        ->fetchField();
    }
    // Try the document map.
    if (!$mid) {
      $mid = $destination_db->select('migrate_map_utexas_document')
        ->fields('migrate_map_utexas_document', ['destid1'])
        ->condition('sourceid1', $fid, '=')
        ->execute()
        ->fetchField();
    }
    return $mid;
  }

  /**
   * Retrieve a user entity ID from the migration map.
   *
   * @param int $uid
   *   A user entity ID from the source site.
   *
   * @return mixed
   *   Returns the matching media entity ID or FALSE.
   */
  public static function getDestinationUid($uid) {
    $destination_db = Database::getConnection('default', 'default');
    $table = 'migrate_map_utexas_users';
    $destination_id = $destination_db->select($table)
      ->fields($table, ['destid1'])
      ->condition('sourceid1', $uid, '=')
      ->execute()
      ->fetchField();
    return $destination_id;
  }

  /**
   * Given a source nid, return a destination nid if there is one.
   *
   * @param int $source_nid
   *   The NID from the D7 site.
   *
   * @return mixed
   *   Returns the node ID or FALSE
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
    $destination_db = Database::getConnection('default', 'default');
    foreach ($tables_to_query as $table) {
      if ($destination_db->schema()->tableExists($table)) {
        $destination_nid = $destination_db->select($table, 'n')
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
   * Given an source menu, return the destination menu.
   *
   * @param string $menu
   *   The source menu (e.g., 'main-menu')
   *
   * @return string
   *   The destination menu (e.g., 'main')
   */
  public static function getMappedMenuName($menu) {
    // Some default Drupal menu names changed between Drupal 7 and 8. For those, we map them to the new name. For others,
    // we leave them as-is.
    $menu_map = [
      'menu-footer' => 'footer',
      'menu-header' => 'header',
      'main-menu' => 'main',
      'management' => 'admin',
      'navigation' => 'tools',
      'user-menu:' => 'account',
    ];
    if (in_array($menu, array_keys($menu_map))) {
      return $menu_map[$menu];
    }
    return $menu;
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
    // @todo: Add coverage for taxonomy ID mapping.
    if (strpos($source, 'node/') === 0) {
      $source_nid = substr($source, 5);
      $destination_nid = self::getDestinationNid($source_nid);
      if ($destination_nid) {
        return '/node/' . $destination_nid;
      }
    }
    elseif (strpos($source, 'file/') === 0) {
      $source_fid = substr($source, 5);
      $destination_fid = self::getDestinationMid($source_fid);
      if ($destination_fid) {
        return '/media/' . $destination_fid;
      }
    }
    elseif (strpos($source, 'user/') === 0) {
      $source_uid = substr($source, 5);
      $destination_uid = self::getDestinationUid($source_uid);
      if ($destination_uid) {
        return '/user/' . $destination_uid;
      }
    }
    return $source;
  }

  /**
   * Receive a Drupal 7 link & format it for Drupal 8.
   *
   * @param string $value
   *   A link, in string format.
   * @param string $source_path
   *   The source path that referenced this link.
   *
   * @return string
   *   The appropriate link for D8.
   */
  public static function prepareLink($value, $source_path = '') {
    // This processing is modeled on the Drupal core link_uri process plugin,
    // but is provided as a helper method so that its processing can be used in
    // contexts such as links in custom components and in WYSIWYG areas.
    $path = ltrim($value, '/');
    if (parse_url($path, PHP_URL_SCHEME) === NULL) {
      // Attempt to find entity mapping from source.
      $path = self::getDestinationFromSource($path);

      if ($path == '<front>') {
        $path = '';
      }
      elseif ($path == '<nolink>') {
        return 'route:<nolink>';
      }
      if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
      }
      $path = 'internal:' . $path;

      // Convert entity URIs to the entity scheme, if the path matches a route
      // of the form "entity.$entity_type_id.canonical".
      // @see \Drupal\Core\Url::fromEntityUri()
      $url = Url::fromUri($path);
      if ($url->isRouted()) {
        $route_name = $url->getRouteName();
        foreach (array_keys(\Drupal::entityTypeManager()->getDefinitions()) as $entity_type_id) {
          if ($route_name == "entity.{$entity_type_id}.canonical" && isset($url->getRouteParameters()[$entity_type_id])) {
            return "entity:{$entity_type_id}/" . $url->getRouteParameters()[$entity_type_id];
          }
        }
      }
      else {
        return $url->getUri();
      }
    }
    return $path;
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
        $d8_format = 'flex_html';
        break;

      case 'filtered_html_for_blocks':
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
   * Map of fieldblock IDs that should be migrated to reusable blocks.
   *
   * @var array
   */
  public static $includedReusableBlocks = [
    'fieldblock-bb03b0e9fbf84510ab65cbb066d872fc' => 'twitter_widget',
    'fieldblock-5e45b57e2023b0d28f5a9dc785ea12fa' => 'twitter_widget',
    'fieldblock-38205d43426b33bd0fe595ff8ca61ffd' => 'contact_info',
    'fieldblock-d83c2a95384186e375ab37cbf1430bf5' => 'contact_info',
  ];

  /**
   * Check if the ID identifies this as a menu block.
   *
   * @param string $d7_display_id
   *   The source block ID.
   *
   * @return mixed
   *   The menu block identifier or FALSE.
   */
  public static function isMenuBlock($d7_display_id) {
    if (strpos($d7_display_id, 'menu_block-') === 0) {
      return $d7_display_id;
    }
    return FALSE;
  }

  /**
   * Check if the field should receive a border.
   *
   * @param string $field
   *   The source block name.
   *
   * @return bool
   *   Whether or not the field should receive a border.
   */
  public static function shouldReceiveBorderWithBackground($field) {
    if (self::isMenuBlock($field)) {
      return FALSE;
    }
    if ($field === 'social_links') {
      return FALSE;
    }
    return TRUE;
  }

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

      case 'views-team_members-block_1':
        if ($moduleHandler->moduleExists('utprof_block_type_profile_listing')) {
          return $d7_display_id;
        }
        break;

      case 'views-events-block_1':
      case 'views-events-block_2':
      case 'views-events-block_3':
      case 'views-events-block_4':
        if ($moduleHandler->moduleExists('utevent_block_type_event_listing')) {
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
    'fieldblock-d41b4a03ee9d7b1084986f74b617921c' => 'newsreel',
    'fieldblock-8e85c2c89f0ccf26e9e4d0378250bf17' => 'newsreel',
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

      case 'newsreel':
        $block_definition = Newsreel::createBlockDefinition($component_data);
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
      case 'views-events-block_1':
      case 'views-events-block_2':
      case 'views-events-block_3':
      case 'views-events-block_4':
      case 'views-team_members-block_1':
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
