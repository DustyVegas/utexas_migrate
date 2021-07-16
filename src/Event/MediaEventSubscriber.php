<?php

namespace Drupal\utexas_migrate\Event;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to set sites to allow for media-based URLs.
 */
class MediaEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_IMPORT][] = ['preImport'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function preImport(MigrateImportEvent $event) {
    if ($event->getMigration()->getPluginId() === 'utexas_media_image') {
      // Allow standalone URLs for media URL backwards compatibility.
      // See `admin/config/media/media-settings`.
      if ($config = \Drupal::configFactory()->getEditable('media.settings')) {
        $config->set('standalone_url', TRUE);
        $config->save();
      }
    }
  }

}
