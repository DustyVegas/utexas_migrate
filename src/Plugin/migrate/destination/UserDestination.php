<?php

namespace Drupal\utexas_migrate\Plugin\migrate\destination;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\Row;
use Drupal\user\Plugin\migrate\destination\EntityUser;

/**
 * Provides a 'user' destination plugin. The id MUST end in the entity name.
 *
 * @MigrateDestination(
 *   id = "utexas:user"
 * )
 */
class UserDestination extends EntityUser {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Do not overwrite the root account password.
    if ($row->getSourceProperty('uid') == 1) {
      $row->removeDestinationProperty('pass');
      $row->setDestinationProperty('uid', 1);
    }
    if ($row->getSourceProperty('uid') == 0) {
      $row->setDestinationProperty('uid', 0);
      // Do not import the anonymous user, but make it available for future mappings.
      return [0];
    }
    return parent::import($row, $old_destination_id_values);
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    $id = parent::save($entity, $old_destination_id_values);
    self::mapAuthmapValue($id[0], $entity->getAccountName());
    return $id;
  }

  /**
   * Helper function to map externalauth values, if present.
   *
   * @param int $uid
   *   The destination uid.
   * @param string $username
   *   The username.
   */
  protected static function mapAuthmapValue($uid, $username) {
    $source_db = Database::getConnection('default', 'utexas_migrate');
    if ($source_db->schema()->tableExists('authmap')) {
      $query = $source_db->select('authmap', 'v')
        ->fields('v', ['authname'])
        ->condition('authname', $username, '=')
        ->execute()
        ->fetch();
      if (!empty($query->authname)) {
        $destination_db = Database::getConnection('default', 'default');
        if ($destination_db->schema()->tableExists('authmap')) {
          $destination_db->merge('authmap')
            ->key(['uid' => $uid, 'provider' => 'simplesamlphp_auth'])
            ->fields([
              'authname' => $username,
              'data' => serialize(NULL),
            ])
            ->execute();
        }
      }
    }
  }

}
