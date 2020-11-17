<?php

namespace Drupal\utexas_migrate\CustomWidgets;

use Drupal\Core\Database\Database;

/**
 * Convert D7 Views block to D8 Inline blocks.
 */
class ViewsBlock {

  /**
   * Convert D7 data to D8 structure.
   *
   * @param string $views_block
   *   Whether the Views block ID as defined in $includedViewsBlocks.
   *
   * @return array
   *   Returns an array of field data for the widget.
   */
  public static function getBlockData($views_block) {
    $source_data = self::getSourceData($views_block);
    return $source_data;
  }

  /**
   * Query the source database for data.
   *
   * @param string $views_block
   *   Whether the Views block ID as defined in $includedViewsBlocks.
   *
   * @return array
   *   Returns contextual data about the views block being used.
   */
  public static function getSourceData($views_block) {
    $data = [];
    // All views blocks in D7 displayed their label.
    $data['label'] = TRUE;

    switch ($views_block) {
      case 'views-news-news_with_thumbnails':
        $data['thumbnails'] = TRUE;
        $data['dates'] = TRUE;
        $data['summaries'] = FALSE;
        $data['count'] = self::getVariable('utexas_news_number_items_thumbnails');
        $data['title'] = self::getVariable('utexas_news_thumbnails_view_title');
        break;

      case 'views-news-news_titles_only':
        $data['thumbnails'] = FALSE;
        $data['dates'] = TRUE;
        $data['summaries'] = FALSE;
        $data['count'] = self::getVariable('utexas_news_number_items_titles');
        $data['title'] = self::getVariable('utexas_news_titles_view_title');
        break;
    }
    return $data;
  }

  /**
   * Helper function for DB queries.
   *
   * @return array
   *   The unserialized value.
   */
  public static function getVariable($name) {
    Database::setActiveConnection('utexas_migrate');
    $query = Database::getConnection()->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('name', $name, '=')
      ->execute()
      ->fetch();
    return unserialize($query->value);
  }

  /**
   * Prepare an array for saving a block.
   *
   * @param array $data
   *   The D7 fields.
   *
   * @return array
   *   D8 block format.
   */
  public static function createBlockDefinition(array $data) {
    $block_definition = [
      'type' => $data['block_type'],
      'info' => $data['field_identifier'],
      'reusable' => FALSE,
    ];
    switch ($data['block_type']) {
      case 'utnews_article_listing':
        // If no source variable has been defined, use the D7 defaults.
        $block_definition['field_utnews_display_count']['value'] = $data['block_data']['count'] ?? 4;
        $block_definition['field_utnews_display_dates']['value'] = $data['block_data']['dates'];
        $block_definition['field_utnews_display_summaries']['value'] = $data['block_data']['summaries'];
        $block_definition['field_utnews_display_thumbnails']['value'] = $data['block_data']['thumbnails'];
        break;

    }
    return $block_definition;
  }

}
