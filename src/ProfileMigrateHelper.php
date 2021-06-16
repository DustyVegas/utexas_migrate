<?php

namespace Drupal\utexas_migrate;

use Drupal\Core\Database\Database;

/**
 * Helper functions for migration.
 */
class ProfileMigrateHelper {

  /**
   * Build multiple Profile listing block data for the "All" Team Members page.
   *
   * @return array
   *   Returns an array of Profile listing block data.
   */
  public static function generateListings() {
    $vocabularies = self::getVocabularyData();
    // [20] => Array
    //     (
    //         [name] => Leadership
    //         [display] => utexas_prominent
    //         [nids] => Array
    //             (
    //                 [0] => 228
    //                 [1] => 229
    //                 [2] => 230
    //                 [3] => 245
    //                 [4] => 267
    $weights = self::getNodeOrder();
    foreach (array_values($vocabularies) as $data) {
      if (empty($data['nids'])) {
        continue;
      }
      $members = array_intersect($weights, $data['nids']);
      $output[] = [
        'title' => $data['name'],
        'field_identifier' => 'views-team_members-block_1',
        'block_type' => 'utprof_profile_listing',
        'field_utprof_view_mode' => $data['display'],
        'field_utprof_list_method' => 'pick',
        'field_utprof_specific_profiles' => array_values($members),
      ];
    }
    // [5] => Array
    //     (
    //         [title] => IT Development Team
    //         [field_identifier] => views-team_members-block_1
    //         [block_type] => utprof_profile_listing
    //         [field_utprof_view_mode] => node.utexas_basic
    //         [field_utprof_list_method] => pick
    //         [field_utprof_specific_profiles] => Array
    //             (
    //                 242
    //                 268
    //                 243
    //                 241
    //                 244
    //             )
    //     )
    return $output;
  }

  /**
   * Generate a sort order (draggable views if present, otherwise alphabetical).
   *
   * @return array
   *   Returns an array of node IDs by the preferred sort order.
   */
  private static function getNodeOrder() {
    Database::setActiveConnection('default');
    $items = [];
    $map = [];
    $map_values = Database::getConnection()->select('migrate_map_utprof_nodes', 'm')
      // Load & inspectdraggableviews_structure, else, sort by name.
      ->fields('m')
      ->execute()
      ->fetchAll();
    foreach ($map_values as $m) {
      $map[$m->sourceid1] = $m->destid1;
    }
    Database::setActiveConnection('utexas_migrate');
    // Map source/destination Taxonomy IDs for Profile groups.
    $weights = Database::getConnection()->select('draggableviews_structure', 'v')
      ->fields('v')
      ->condition('view_name', 'team_members')
      ->execute()
      ->fetchAll();
    if (!empty($weights)) {
      // Sort by draggable view weight.
      foreach (array_values($weights) as $values) {
        $items[$map[$values->entity_id]] = $values->weight;
      }
      asort($items);
      return array_keys($items);
    }
    else {
      Database::setActiveConnection('default');
      $surnames = Database::getConnection()->select('node__field_utprof_surname', 'm')
        ->fields('m')
        ->execute()
        ->fetchAll();
      // Sort alpha by surname.
      usort($surnames, function ($item1, $item2) {
        return $item1->field_utprof_surname_value > $item2->field_utprof_surname_value;
      });
      foreach ($surnames as $s) {
        $items[] = $s->entity_id;
      }
      return $items;
    }
  }

  private static function getVocabularyData() {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    // Map source/destination Taxonomy IDs for Profile groups.
    $vid = $source_db->select('taxonomy_vocabulary', 'v')
      ->fields('v')
      ->condition('machine_name', 'team_member_group')
      ->execute()
      ->fetchField();
    $source_tids = $source_db->select('taxonomy_term_data', 't')
      ->fields('t')
      ->condition('vid', $vid)
      ->execute()
      ->fetchAll();
    // Sort team member groups by vocabulary weight.
    usort($source_tids, function ($item1, $item2) {
      return $item1->weight > $item2->weight;
    });
    // Add vocabulary display setting, creating a source TID-keyed array.
    $display = $source_db->select('field_data_field_utexas_team_member_display', 'd')
      ->fields('d')
      ->execute()
      ->fetchAllAssoc('entity_id');
    foreach (array_values($source_tids) as $data) {
      $vocab_data[$data->tid] = [
        'name' => $data->name,
        'display' => self::$displayMap[$display[$data->tid]->field_utexas_team_member_display_value],
      ];
    }
    // Map source/destination IDs.
    $destination_db = Database::getConnection('default', 'default');
    $destination_tids = $destination_db->select('taxonomy_term_field_data', 't')
      ->fields('t')
      ->condition('vid', 'utprof_groups')
      ->execute()
      ->fetchAll();
    $map = [];
    // Create an array with the correct vocabulary sort order.
    foreach (array_values($vocab_data) as $data) {
      foreach (array_values($destination_tids) as $dest) {
        if ($dest->name === $data['name']) {
          $map[$dest->tid] = $data;
        }
      }
    }
    // Get nodes that have been assigned Profile group vocabularies.
    $destination_nids = $destination_db->select('node__field_utprof_profile_groups', 'n')
      ->fields('n')
      ->execute()
      ->fetchAllAssoc('entity_id');
    // Assign node IDs to each Profile group.
    foreach (array_values($destination_nids) as $node) {
      $target_id = $node->field_utprof_profile_groups_target_id;
      if (in_array($target_id, array_keys($map))) {
        $map[$target_id]['nids'][] = $node->entity_id;
      }
    }
    // [20] => Array
    //     (
    //         [name] => Leadership
    //         [display] => utexas_prominent
    //         [nids] => Array
    //             (
    //                 [0] => 228
    //                 [1] => 229
    //                 [2] => 230
    //                 [3] => 245
    //                 [4] => 267
    return $map;
  }

  private static $displayMap = [
    'prominent' => 'node.utexas_prominent',
    'basic' => 'node.utexas_basic',
    'name-only' => 'node.utexas_name_only',
  ];

}
