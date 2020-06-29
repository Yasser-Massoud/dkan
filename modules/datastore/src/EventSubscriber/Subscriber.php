<?php

namespace Drupal\datastore\EventSubscriber;

use Dkan\Datastore\Resource;
use Drupal\common\LoggerTrait;
use Drupal\metastore\Events\Registration;
use Drupal\metastore\FileMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Subscriber implements EventSubscriberInterface {
  use LoggerTrait;

  public static function getSubscribedEvents() {
    $events = [];
    $events[FileMapper::EVENT_REGISTRATION][] = ['onRegistration'];
    return $events;
  }

  public function onRegistration(Registration $event) {
    $data = $event->getData();

    /* @var \Dkan\Datastore\Resource $resouce */
    $resource = $data->resource;

    if ($this->isDataStorable($resource)) {
      try {
        /* @var $datastoreService \Drupal\datastore\Service */
        $datastoreService = \Drupal::service('dkan.datastore.service');
        $datastoreService->import($resource->getId(), TRUE);
      }
      catch (\Exception $e) {
        $this->setLoggerFactory(\Drupal::service('logger.factory'));
        $this->log('datastore', $e->getMessage());
      }
    }

  }

  /**
   * Private.
   */
  private function isDataStorable(Resource $resource) : bool {
    return in_array($resource->getMimeType(), [
      'text/csv',
      'text/tab-separated-values',
    ]);
  }

}
