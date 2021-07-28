<?php

namespace Drupal\utnews_migrate\Event;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\utexas_migrate\MigrateHelper;

use Drupal\utnews\Permissions;

/**
 * Event subscriber to set sites to allow for media-based URLs.
 */
class MigrateEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['postImport'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function postImport(MigrateImportEvent $event) {
    if ($event->getMigration()->getPluginId() === 'utnews_nodes') {
      Permissions::assignPermissions('editor', 'utexas_content_editor');
      Permissions::assignPermissions('manager', 'utexas_site_manager');
      $listing_page_title = MigrateHelper::getVariable('utexas_news_all_articles_title') ?? 'Latest News';
      $config = \Drupal::service('config.factory')->getEditable('utnews_view_listing_page.config');
      $config->set('page_title', $listing_page_title);
      $config->save();
    }
  }

}
