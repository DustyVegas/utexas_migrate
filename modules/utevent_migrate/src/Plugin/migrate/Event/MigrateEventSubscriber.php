<?php

namespace Drupal\utevent_migrate\Event;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\utevent\Permissions;

/**
 * Event subscriber to set sites to allow for media-based URLs.
 */
class MigrateEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_IMPORT][] = ['preImport'];
    $events[MigrateEvents::POST_IMPORT][] = ['postImport'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function postImport(MigrateImportEvent $event) {
    if ($event->getMigration()->getPluginId() === 'utevent_nodes') {
      Permissions::assignPermissions('editor', 'utexas_content_editor');
      Permissions::assignPermissions('manager', 'utexas_site_manager');
    }
  }
}
