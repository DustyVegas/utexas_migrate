<?php

namespace Drupal\utexas_migrate\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;
use Drupal\migrate\Row;

/**
 * Custom user source from database.
 *
 * @MigrateSource(
 *   id = "d7_user",
 *   source_module = "user"
 * )
 */
class UserSource extends User {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $uid = $row->getSourceProperty('uid');
    $roles = $this->select('users_roles', 'ur')
      ->fields('ur', ['rid'])
      ->condition('ur.uid', $uid)
      ->execute()
      ->fetchCol();
    if (!empty($roles)) {
      $rolenames = $this->getD7RoleNames($roles);
      $row->setDestinationProperty('roles', $this->mapRoles($rolenames));
    }
    return parent::prepareRow($row);
  }

  /**
   * Derive v3 role names from v2 rid values.
   *
   * @param array $d7_roles
   *   The rid values from D7.
   *
   * @return array
   *   The D8 roles of the user.
   */
  protected function mapRoles(array $d7_roles) {
    $map = [
      'Announcement Editor' => 'utexas_site_manager',
      'Events Editor' => 'utexas_content_editor',
      'Landing Page Editor' => 'utexas_content_editor',
      'News Editor' => 'utexas_content_editor',
      'Site Builder' => 'utexas_content_editor',
      'Site Manager' => 'utexas_site_manager',
      'Standard Page Editor' => 'utexas_content_editor',
      'Team Member Editor' => 'utexas_content_editor',
    ];
    foreach ($d7_roles as $label) {
      if (!in_array($label, array_keys($map))) {
        continue;
      }
      $d8_roles[] = $map[$label];
    }
    return array_unique($d8_roles);
  }

  /**
   * Helper function to get role names from D7.
   */
  public function getD7RoleNames($rids) {
    // Query for article-specific fields.
    $roles = $this->select('role', 'r')
      ->fields('r', ['name'])
      ->condition('rid', $rids, 'IN')
      ->execute()
      ->fetchCol();
    return $roles;
  }

}
