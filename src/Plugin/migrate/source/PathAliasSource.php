<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\utexas_migrate\CustomWidgets\FeaturedHighlight;
use Drupal\utexas_migrate\CustomWidgets\FlexContentArea;
use Drupal\utexas_migrate\CustomWidgets\HeroImage;
use Drupal\utexas_migrate\CustomWidgets\ImageLink;
use Drupal\utexas_migrate\CustomWidgets\PhotoContentArea;
use Drupal\utexas_migrate\CustomWidgets\PromoLists;
use Drupal\utexas_migrate\CustomWidgets\PromoUnits;
use Drupal\utexas_migrate\CustomWidgets\QuickLinks;
use Drupal\utexas_migrate\CustomWidgets\Resource;

/**
 * Query available fields in Drupal 7 database and prepare them..
 *
 * @MigrateSource(
 *   id = "utexas_path_alias_source",
 *   source_module = "utexas_migrate"
 * )
 */
class PathAliasSource extends NodeSource {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Inherit SQL joins from NodeSource.
    $query = parent::query();
    $query->leftJoin('pathauto_state', 'pathauto', 'pathauto.entity_id = n.nid');
    $query->fields('pathauto', ['pathauto']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_nid = $row->getSourceProperty('nid');
    $alias = $this->select('url_alias', 'ua')
      ->fields('ua', ['alias'])
      ->condition('source', 'node/' . $source_nid)
      ->execute()
      ->fetchField();
    $row->setSourceProperty('alias', '/' . $alias);
    return parent::prepareRow($row);
  }

}
